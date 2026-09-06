<?php

declare(strict_types=1);

use App\Enums\CompanyPermission;
use App\Models\Auth\Permission;
use App\Models\Company\CompanyAccessEvent;
use Database\Factories\CompanyFactory;
use Database\Factories\PermissionFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function configureLegacyUserRolesConnection(): string
{
    $originalConnection = DB::getDefaultConnection();
    config()->set('database.connections.phase2b1_legacy', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    DB::purge('phase2b1_legacy');
    DB::setDefaultConnection('phase2b1_legacy');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('companies', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('company_user', function (Blueprint $table): void {
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->primary(['company_id', 'user_id']);
        $table->unique(['user_id', 'company_id']);
    });
    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    });
    Schema::create('user_roles', function (Blueprint $table): void {
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('role_id')->constrained()->cascadeOnDelete();
        $table->primary(['user_id', 'role_id']);
    });

    return $originalConnection;
}

function restoreDefaultTestConnection(string $connection): void
{
    DB::setDefaultConnection($connection);
    DB::purge('phase2b1_legacy');
}

it('backfills a valid legacy assignment through the real migration', function () {
    $this->freezeTime();
    $originalConnection = configureLegacyUserRolesConnection();

    try {
        DB::table('users')->insert(['id' => 10]);
        DB::table('companies')->insert([['id' => 20], ['id' => 21]]);
        DB::table('company_user')->insert([['company_id' => 20, 'user_id' => 10], ['company_id' => 21, 'user_id' => 10]]);
        DB::table('roles')->insert([['id' => 30, 'company_id' => 20], ['id' => 31, 'company_id' => 21]]);
        DB::table('user_roles')->insert([['user_id' => 10, 'role_id' => 30], ['user_id' => 10, 'role_id' => 31]]);

        $migration = require database_path('migrations/2026_09_04_193918_strengthen_user_roles_for_company_membership.php');
        $migration->up();

        $assignment = DB::table('user_roles')->where(['user_id' => 10, 'role_id' => 30])->sole();
        expect($assignment->company_id)->toBe(20)
            ->and($assignment->assigned_by)->toBeNull()
            ->and($assignment->created_at)->toBe(now()->toDateTimeString())
            ->and($assignment->updated_at)->toBe(now()->toDateTimeString())
            ->and(DB::table('company_user')->where(['company_id' => 20, 'user_id' => 10])->count())->toBe(1)
            ->and(DB::table('user_roles')->count())->toBe(2)
            ->and(DB::table('user_roles')->where(['user_id' => 10, 'role_id' => 31])->sole()->company_id)->toBe(21);
    } finally {
        restoreDefaultTestConnection($originalConnection);
    }
});

it('fails the real migration before schema mutation for an invalid legacy assignment', function () {
    $originalConnection = configureLegacyUserRolesConnection();

    try {
        DB::table('users')->insert(['id' => 11]);
        DB::table('companies')->insert(['id' => 21]);
        DB::table('roles')->insert(['id' => 31, 'company_id' => 21]);
        DB::table('user_roles')->insert(['user_id' => 11, 'role_id' => 31]);
        $migration = require database_path('migrations/2026_09_04_193918_strengthen_user_roles_for_company_membership.php');

        $schemaBefore = DB::select('SELECT type, name, tbl_name, sql FROM sqlite_master ORDER BY type, name');
        expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Cannot strengthen user_roles');
        expect(DB::select('SELECT type, name, tbl_name, sql FROM sqlite_master ORDER BY type, name'))->toEqual($schemaBefore);

        expect(Schema::hasColumn('user_roles', 'company_id'))->toBeFalse()
            ->and(Schema::hasColumn('user_roles', 'assigned_by'))->toBeFalse()
            ->and(DB::table('company_user')->count())->toBe(0)
            ->and(DB::table('user_roles')->where(['user_id' => 11, 'role_id' => 31])->count())->toBe(1)
            ->and(collect(Schema::getIndexes('roles'))->pluck('name')->contains('roles_id_company_id_unique'))->toBeFalse();
    } finally {
        restoreDefaultTestConnection($originalConnection);
    }
});

it('enforces company membership and matching role company in user roles', function () {
    $company = CompanyFactory::new()->create();
    $otherCompany = CompanyFactory::new()->create();
    $user = UserFactory::new()->create();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    expect(fn () => DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $role->id, 'company_id' => $company->id]))->toThrow(QueryException::class);
    $company->users()->attach($user);
    expect(fn () => DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $role->id, 'company_id' => $otherCompany->id]))->toThrow(QueryException::class);
});

it('keeps the canonical permission migration idempotent and preserves unrelated permissions', function () {
    $unrelated = PermissionFactory::new()->create(['name' => 'unrelated.permission']);
    $migration = require database_path('migrations/2026_09_04_193919_add_system_roles_and_company_access_permissions.php');
    $migration->up();
    $migration->up();
    expect(Permission::query()->whereIn('name', array_column(CompanyPermission::cases(), 'value'))->count())->toBe(7)
        ->and(Permission::query()->whereKey($unrelated)->exists())->toBeTrue()
        ->and(Permission::query()->where('name', CompanyPermission::OwnershipManage->value)->sole()->assignable_to_custom_roles)->toBeFalse();
});

it('keeps access events append-only', function () {
    $event = CompanyAccessEvent::factory()->create();
    expect(fn () => $event->update(['metadata' => ['changed' => true]]))->toThrow(LogicException::class)
        ->and(fn () => $event->delete())->toThrow(LogicException::class);
});
