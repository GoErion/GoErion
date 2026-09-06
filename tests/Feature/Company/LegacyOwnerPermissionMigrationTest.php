<?php

declare(strict_types=1);

use App\Models\Auth\Permission;
use Database\Factories\CompanyFactory;
use Database\Factories\PermissionFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

uses(RefreshDatabase::class);

it('upgrades legacy owner roles without changing other role permissions', function () {
    $company = CompanyFactory::new()->create();
    $legacyOwner = UserFactory::new()->create();
    $legacyOwner->companies()->attach($company);

    $ownerRole = RoleFactory::new()->create([
        'company_id' => $company->id,
        'name' => 'owner',
        'display_name' => 'Owner',
    ]);
    $nonOwnerRole = RoleFactory::new()->create([
        'company_id' => $company->id,
        'name' => 'accountant',
        'display_name' => 'Accountant',
    ]);
    $unrelatedPermission = PermissionFactory::new()->create([
        'name' => 'reports.view',
        'display_name' => 'View Reports',
    ]);
    $ownerRole->permissions()->attach($unrelatedPermission);
    $legacyOwner->roles()->attach($ownerRole, ['company_id' => $company->id]);

    Permission::query()->where('name', 'company.update')->delete();

    expect(Permission::query()->where('name', 'company.update')->exists())->toBeFalse()
        ->and($legacyOwner->can('update', $company))->toBeFalse();

    $migration = require database_path('migrations/2026_09_04_132602_grant_company_update_permission_to_existing_owner_roles.php');
    $migration->up();
    $createdPermission = Permission::query()->where('name', 'company.update')->sole();
    $migration->up();

    $companyUpdatePermission = Permission::query()->where('name', 'company.update')->sole();

    expect($companyUpdatePermission->is($createdPermission))->toBeTrue()
        ->and($companyUpdatePermission->uuid)->toBe($createdPermission->uuid)
        ->and($ownerRole->permissions()->whereKey($companyUpdatePermission)->exists())->toBeTrue()
        ->and($nonOwnerRole->permissions()->whereKey($companyUpdatePermission)->exists())->toBeFalse()
        ->and($ownerRole->permissions()->whereKey($unrelatedPermission)->exists())->toBeTrue()
        ->and($legacyOwner->can('update', $company))->toBeTrue()
        ->and(Uuid::fromString($companyUpdatePermission->uuid)->getVersion())->toBe(7)
        ->and(Permission::query()->where('name', 'company.update')->count())->toBe(1)
        ->and(DB::table('role_permissions')->where([
            'role_id' => $ownerRole->id,
            'permission_id' => $companyUpdatePermission->id,
        ])->count())->toBe(1);
});
