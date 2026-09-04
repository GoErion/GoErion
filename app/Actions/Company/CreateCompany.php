<?php

declare(strict_types=1);

namespace App\Actions\Company;

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
            $company = Company::query()->create($values);
            $creator->companies()->attach($company);

            $ownerRole = $company->roles()->firstOrCreate(
                ['name' => 'owner'],
                ['display_name' => 'Owner'],
            );
            $companyUpdatePermission = Permission::query()->firstOrCreate(
                ['name' => 'company.update'],
                ['display_name' => 'Update Company'],
            );

            $ownerRole->permissions()->sync([$companyUpdatePermission->getKey()]);
            $creator->roles()->syncWithoutDetaching([$ownerRole->getKey()]);

            return $company;
        }, 2);
    }
}
