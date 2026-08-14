<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Support\Facades\DB;

final readonly class CreateCompany
{
    public function handle(array $input, User $user)
    {
        return DB::transaction(function () use ($input, $user)
        {
            $company = Company::query()->create(
                [
                    'company_name' => $input['company_name'],
                    'legal_name' => $input['legal_name'] ?? null,
                    'registration_number' => $input['registration_number'] ?? null,
                    'tax_number' => $input['tax_number'] ?? null,
                    'email' => $input['email'] ?? null,
                    'phone' => $input['phone'] ?? null,
                    'address' => $input['address'] ?? null,
                ]
            );

            $user->companies()->attach($company->id);

            $role = $company->roles()->create(
                [
                    'name' => 'owner',
                    'display_name' => 'Owner',
                ]
            );

            $user->roles()->attach($role->id);

            return $company;
        }, 2);
    }
}
