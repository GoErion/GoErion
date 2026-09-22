<?php

declare(strict_types=1);

use App\Enums\CustomerPermission;
use App\Models\Auth\Permission;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Seeders\PermissionSeeder;
use Tests\Support\RefreshCustomerDatabase;

uses(RefreshCustomerDatabase::class);

it('upgrades only existing system owners and makes Customer permissions assignable', function () {
    app(PermissionSeeder::class)->run();
    $company = CompanyFactory::new()->create();
    $owner = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'owner', 'is_system' => true]);
    $custom = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'sales']);
    $migration = require database_path('migrations/2026_09_21_183947_grant_customer_permissions_to_owner_roles.php');

    $migration->up();
    $migration->up();

    expect($owner->permissions()->whereIn('name', array_column(CustomerPermission::cases(), 'value'))->count())->toBe(6)
        ->and($custom->permissions()->count())->toBe(0)
        ->and(Permission::query()->whereIn('name', array_column(CustomerPermission::cases(), 'value'))->where('assignable_to_custom_roles', true)->count())->toBe(6)
        ->and(Permission::query()->where('name', 'customers.delete')->sole()->assignable_to_custom_roles)->toBeFalse();
});

it('keeps the Customer permission seeder idempotent', function () {
    $seeder = app(PermissionSeeder::class);
    $seeder->run();
    $seeder->run();

    expect(Permission::query()->whereIn('name', array_column(CustomerPermission::cases(), 'value'))->count())->toBe(6)
        ->and(Permission::query()->whereIn('name', array_column(CustomerPermission::cases(), 'value'))->where('assignable_to_custom_roles', true)->count())->toBe(6)
        ->and(Permission::query()->where('name', 'customers.delete')->sole()->assignable_to_custom_roles)->toBeFalse();
});
