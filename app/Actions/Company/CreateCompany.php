<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyPermission;
use App\Enums\CustomerPermission;
use App\Enums\SupplierPermission;
use App\Models\Auth\Permission;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Support\Facades\DB;

final readonly class CreateCompany
{
    /** @param array{company_name: string, legal_name?: ?string, registration_number?: ?string, tax_number?: ?string, email?: ?string, phone?: ?string, address?: ?string} $values */
    public function handle(array $values, User $creator): Company
    {
        return DB::transaction(function () use ($values, $creator): Company {
            $creator = User::query()->whereKey($creator->getKey())->lockForUpdate()->firstOrFail();
            $company = Company::query()->create($values);
            $creator->companies()->attach($company);

            $ownerRole = $company->roles()->firstOrCreate(
                ['name' => 'owner'],
                ['display_name' => 'Owner', 'is_system' => true],
            );
            $permissionIds = collect(CompanyPermission::cases())->map(fn (CompanyPermission $permission) => Permission::query()->firstOrCreate(
                ['name' => $permission->value],
                ['display_name' => $permission->displayName(), 'assignable_to_custom_roles' => $permission->isAssignableToCustomRoles()],
            )->getKey());
            $permissionIds = $permissionIds->merge(collect([...CustomerPermission::cases(), ...SupplierPermission::cases()])->map(
                fn (CustomerPermission|SupplierPermission $permission) => Permission::query()->firstOrCreate(
                    ['name' => $permission->value],
                    ['display_name' => $permission->displayName(), 'assignable_to_custom_roles' => true],
                )->getKey(),
            ));
            $ownerRole->permissions()->syncWithoutDetaching($permissionIds);
            $creator->roles()->attach($ownerRole->getKey(), [
                'company_id' => $company->getKey(), 'assigned_by' => $creator->getKey(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $company;
        }, 2);
    }
}
