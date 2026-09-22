<?php

declare(strict_types=1);

use Database\Factories\CompanyFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\RefreshCustomerDatabase;

uses(RefreshCustomerDatabase::class);

function membershipFixture(int $companyId, string $code): array
{
    $groupId = DB::table('customer_groups')->insertGetId([
        'uuid' => (string) Str::uuid(), 'company_id' => $companyId,
        'name' => "Group {$code}", 'code' => $code, 'is_active' => true,
    ]);
    $customerId = DB::table('customers')->insertGetId([
        'uuid' => (string) Str::uuid(), 'company_id' => $companyId,
        'name' => "Customer {$code}", 'code' => $code,
        'credit_limit' => '0.0000', 'payment_terms_days' => 0, 'is_active' => true,
    ]);

    return [$customerId, $groupId];
}

it('enforces same-company single membership and preserves customers on group deletion', function () {
    $company = CompanyFactory::new()->create();
    $foreignCompany = CompanyFactory::new()->create();
    [$customerId, $groupId] = membershipFixture($company->id, 'ONE');
    [, $foreignGroupId] = membershipFixture($foreignCompany->id, 'TWO');

    DB::table('customer_group_memberships')->insert([
        'customer_id' => $customerId, 'customer_group_id' => $groupId, 'company_id' => $company->id,
    ]);

    expect(fn () => DB::table('customer_group_memberships')->insert([
        'customer_id' => $customerId, 'customer_group_id' => $foreignGroupId, 'company_id' => $company->id,
    ]))->toThrow(QueryException::class);
    expect(fn () => DB::table('customer_group_memberships')->insert([
        'customer_id' => $customerId, 'customer_group_id' => $foreignGroupId, 'company_id' => $foreignCompany->id,
    ]))->toThrow(QueryException::class);

    DB::table('customer_groups')->where('id', $groupId)->delete();
    expect(DB::table('customers')->where('id', $customerId)->exists())->toBeTrue()
        ->and(DB::table('customer_group_memberships')->where('customer_id', $customerId)->exists())->toBeFalse();
});

it('allows existing company cascade deletion with grouped customers', function () {
    $company = CompanyFactory::new()->create();
    [$customerId, $groupId] = membershipFixture($company->id, 'THREE');
    DB::table('customer_group_memberships')->insert([
        'customer_id' => $customerId, 'customer_group_id' => $groupId, 'company_id' => $company->id,
    ]);

    $company->delete();

    expect(DB::table('customers')->where('id', $customerId)->exists())->toBeFalse()
        ->and(DB::table('customer_groups')->where('id', $groupId)->exists())->toBeFalse()
        ->and(DB::table('customer_group_memberships')->where('customer_id', $customerId)->exists())->toBeFalse();
});

it('restores legacy assignments on rollback and preserves them on reapply', function () {
    $previous = DB::getDefaultConnection();
    config()->set('database.connections.customer_migration', [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]);
    DB::purge('customer_migration');
    DB::setDefaultConnection('customer_migration');

    try {
        Schema::create('companies', fn (Blueprint $table) => $table->id());
        foreach ([
            '2026_08_17_144700_create_customer_groups_table.php',
            '2026_08_17_144800_create_customers_table.php',
            '2026_08_17_154242_create_customer_addresses_table.php',
            '2026_08_17_161754_create_customer_contacts_table.php',
        ] as $filename) {
            (require database_path('migrations/'.$filename))->up();
        }
        DB::table('companies')->insert(['id' => 1]);
        [$customerId, $groupId] = membershipFixture(1, 'ROUNDTRIP');
        DB::table('customers')->where('id', $customerId)->update(['customer_group_id' => $groupId]);
        DB::table('customer_contacts')->insert([
            'uuid' => (string) Str::uuid(), 'customer_id' => $customerId, 'name' => 'Retained contact',
        ]);
        DB::table('customer_addresses')->insert([
            'uuid' => (string) Str::uuid(), 'customer_id' => $customerId,
            'type' => 'billing', 'line_1' => 'Retained address',
        ]);

        $migration = require database_path('migrations/2026_09_21_175427_move_customer_groups_to_memberships.php');
        $migration->up();
        expect(DB::table('customer_group_memberships')->where('customer_id', $customerId)->value('customer_group_id'))->toBe($groupId)
            ->and(DB::select('PRAGMA foreign_key_check'))->toBe([])
            ->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);

        $migration->down();
        expect(DB::table('customers')->where('id', $customerId)->value('customer_group_id'))->toBe($groupId)
            ->and(DB::table('customer_contacts')->where('customer_id', $customerId)->count())->toBe(1)
            ->and(DB::table('customer_addresses')->where('customer_id', $customerId)->count())->toBe(1)
            ->and(DB::select('PRAGMA foreign_key_check'))->toBe([])
            ->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
    } finally {
        DB::setDefaultConnection($previous);
        DB::purge('customer_migration');
    }
});

it('rolls back an SQLite integrity failure and restores the original foreign-key state', function () {
    $previous = DB::getDefaultConnection();
    config()->set('database.connections.customer_migration_failure', [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]);
    DB::purge('customer_migration_failure');
    DB::setDefaultConnection('customer_migration_failure');

    try {
        Schema::create('companies', fn (Blueprint $table) => $table->id());
        (require database_path('migrations/2026_08_17_144700_create_customer_groups_table.php'))->up();
        (require database_path('migrations/2026_08_17_144800_create_customers_table.php'))->up();
        DB::table('companies')->insert(['id' => 1]);
        [$customerId, $groupId] = membershipFixture(1, 'FAILURE');
        DB::table('customers')->where('id', $customerId)->update(['customer_group_id' => $groupId]);
        Schema::create('invalid_refs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
        });
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('invalid_refs')->insert(['id' => 1, 'company_id' => 999]);
        DB::statement('PRAGMA foreign_keys = ON');

        $schemaBefore = DB::select('SELECT type, name, tbl_name, sql FROM sqlite_master ORDER BY type, name');
        $migration = require database_path('migrations/2026_09_21_175427_move_customer_groups_to_memberships.php');

        expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'foreign_key_check');
        expect(DB::select('SELECT type, name, tbl_name, sql FROM sqlite_master ORDER BY type, name'))->toEqual($schemaBefore)
            ->and(DB::table('customers')->where('id', $customerId)->value('customer_group_id'))->toBe($groupId)
            ->and(Schema::hasTable('customer_group_memberships'))->toBeFalse()
            ->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
    } finally {
        DB::setDefaultConnection($previous);
        DB::purge('customer_migration_failure');
    }
});

it('rejects invalid legacy cross-company assignments before SQLite schema changes', function () {
    $previous = DB::getDefaultConnection();
    config()->set('database.connections.customer_migration_preflight', [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]);
    DB::purge('customer_migration_preflight');
    DB::setDefaultConnection('customer_migration_preflight');

    try {
        Schema::create('companies', fn (Blueprint $table) => $table->id());
        (require database_path('migrations/2026_08_17_144700_create_customer_groups_table.php'))->up();
        (require database_path('migrations/2026_08_17_144800_create_customers_table.php'))->up();
        DB::table('companies')->insert([['id' => 1], ['id' => 2]]);
        [$customerId] = membershipFixture(1, 'PREFLIGHT');
        [, $foreignGroupId] = membershipFixture(2, 'FOREIGN');
        DB::table('customers')->where('id', $customerId)->update(['customer_group_id' => $foreignGroupId]);
        $schemaBefore = DB::select('SELECT type, name, tbl_name, sql FROM sqlite_master ORDER BY type, name');
        $migration = require database_path('migrations/2026_09_21_175427_move_customer_groups_to_memberships.php');

        expect(fn () => $migration->up())->toThrow(RuntimeException::class, '1 cross-company assignments');
        expect(DB::select('SELECT type, name, tbl_name, sql FROM sqlite_master ORDER BY type, name'))->toEqual($schemaBefore)
            ->and(DB::table('customers')->where('id', $customerId)->value('customer_group_id'))->toBe($foreignGroupId)
            ->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
    } finally {
        DB::setDefaultConnection($previous);
        DB::purge('customer_migration_preflight');
    }
});

it('preserves MySQL legacy assignments through both migration directions', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Covered by direct disposable MySQL test execution.');
    }

    $company = CompanyFactory::new()->create();
    [$customerId, $groupId] = membershipFixture($company->id, 'MYSQL');
    $migration = require database_path('migrations/2026_09_21_175427_move_customer_groups_to_memberships.php');

    $migration->down();
    DB::table('customers')->where('id', $customerId)->update(['customer_group_id' => $groupId]);
    $migration->up();
    expect(DB::table('customer_group_memberships')->where('customer_id', $customerId)->value('customer_group_id'))->toBe($groupId);

    $migration->down();
    expect(DB::table('customers')->where('id', $customerId)->value('customer_group_id'))->toBe($groupId);
    $migration->up();
    expect(DB::table('customer_group_memberships')->where('customer_id', $customerId)->value('customer_group_id'))->toBe($groupId);
});
