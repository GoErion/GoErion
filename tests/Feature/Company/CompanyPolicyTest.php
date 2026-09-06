<?php

declare(strict_types=1);

use App\Models\Auth\Permission;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only members to view a company', function () {
    $member = UserFactory::new()->create();
    $nonMember = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $member->companies()->attach($company);

    expect($member->can('view', $company))->toBeTrue()
        ->and($nonMember->can('view', $company))->toBeFalse();
});

it('requires a same-company role with company update permission', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $otherCompany = CompanyFactory::new()->create();
    $permission = Permission::query()->where('name', 'company.update')->sole();
    $otherCompanyRole = RoleFactory::new()->create(['company_id' => $otherCompany->id]);
    $otherCompanyRole->permissions()->attach($permission);
    $member->companies()->attach([$company->id, $otherCompany->id]);
    $member->roles()->attach($otherCompanyRole, ['company_id' => $otherCompany->id]);

    expect($member->can('update', $company))->toBeFalse();

    $companyRole = RoleFactory::new()->create(['company_id' => $company->id]);
    $companyRole->permissions()->attach($permission);
    $member->roles()->attach($companyRole, ['company_id' => $company->id]);

    expect($member->can('update', $company))->toBeTrue();
});

it('does not allow a non-member role to authorize an update', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $permission = Permission::query()->where('name', 'company.update')->sole();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $role->permissions()->attach($permission);
    expect(fn () => $user->roles()->attach($role, ['company_id' => $company->id]))->toThrow(QueryException::class);

    expect($user->can('update', $company))->toBeFalse();
});
