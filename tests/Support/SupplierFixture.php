<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Actions\Company\CreateCompany;
use App\Models\Auth\Permission;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;

final class SupplierFixture
{
    /** @return array{User, Company} */
    public static function owner(): array
    {
        $owner = UserFactory::new()->create();

        return [$owner, app(CreateCompany::class)->handle(['company_name' => 'Supplier test company'], $owner)];
    }

    /** @param list<string> $permissions */
    public static function member(Company $company, array $permissions = []): User
    {
        $user = UserFactory::new()->create();
        $company->users()->attach($user);
        $role = RoleFactory::new()->create(['company_id' => $company->id]);
        $role->permissions()->attach(Permission::query()->whereIn('name', $permissions)->pluck('id'));
        $user->roles()->attach($role, ['company_id' => $company->id, 'assigned_by' => $user->id]);

        return $user;
    }
}
