<?php

declare(strict_types=1);

use App\Actions\Company\CreateRole;
use App\Enums\ProductPermission;
use App\Models\Auth\Permission;
use Database\Factories\RoleFactory;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

it('grants exactly the canonical Product permissions idempotently to owners only', function () {
    [$owner,$company] = SupplierFixture::owner();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $names = array_column(ProductPermission::cases(), 'value');
    $migration = require glob(database_path('migrations/*_grant_product_permissions_to_owner_roles.php'))[0];
    $migration->up();
    $migration->up();
    $migration->down();
    $this->seed(PermissionSeeder::class);
    $this->seed(PermissionSeeder::class);
    expect(Permission::query()->whereIn('name', $names)->count())->toBe(9);
    expect($company->ownerRole->permissions()->whereIn('name', $names)->pluck('name')->all())->toEqualCanonicalizing($names);
    expect($role->permissions()->count())->toBe(0)->and(Permission::query()->assignableToCustomRoles()->whereIn('name', $names)->count())->toBe(9);
    expect(DB::table('role_permissions')->select('role_id', 'permission_id')->groupBy('role_id', 'permission_id')->havingRaw('COUNT(*) > 1')->count())->toBe(0);
});

it('allows all nine Product permissions in an authorized custom role', function () {
    [$owner, $company] = SupplierFixture::owner();
    $ids = Permission::query()->whereIn('name', array_column(ProductPermission::cases(), 'value'))->pluck('id')->all();
    $role = app(CreateRole::class)->handle($company, $owner, 'catalog-editor', null, $ids);
    expect($role->permissions()->pluck('name')->all())->toEqualCanonicalizing(array_column(ProductPermission::cases(), 'value'));
});
