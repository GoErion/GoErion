<?php

declare(strict_types=1);

use App\Enums\CompanyPermission;
use App\Models\Auth\Permission;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('enforces each company access ability against membership and same-company permissions', function (string $ability, CompanyPermission $permission) {
    $company = CompanyFactory::new()->create();
    $otherCompany = CompanyFactory::new()->create();
    $authorized = UserFactory::new()->create();
    $withoutPermission = UserFactory::new()->create();
    $crossCompany = UserFactory::new()->create();
    $nonMember = UserFactory::new()->create();
    $unverified = UserFactory::new()->unverified()->create();
    $company->users()->attach([$authorized->id, $withoutPermission->id, $crossCompany->id, $unverified->id]);
    $otherCompany->users()->attach($crossCompany);
    $permissionModel = Permission::query()->where('name', $permission->value)->sole();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $role->permissions()->attach($permissionModel);
    $authorized->roles()->attach($role, ['company_id' => $company->id]);
    $otherRole = RoleFactory::new()->create(['company_id' => $otherCompany->id]);
    $otherRole->permissions()->attach($permissionModel);
    $crossCompany->roles()->attach($otherRole, ['company_id' => $otherCompany->id]);
    if ($ability === 'manageOwnership') {
        $role->update(['name' => 'owner', 'is_system' => true]);
    }
    expect($authorized->can($ability, $company))->toBeTrue()->and($withoutPermission->can($ability, $company))->toBeFalse()
        ->and($crossCompany->can($ability, $company))->toBeFalse()->and($nonMember->can($ability, $company))->toBeFalse()->and($unverified->can($ability, $company))->toBeFalse();
})->with([
    ['viewMembers', CompanyPermission::MembersView], ['manageMembers', CompanyPermission::MembersManage],
    ['viewRoles', CompanyPermission::RolesView], ['manageRoles', CompanyPermission::RolesManage],
    ['inviteMembers', CompanyPermission::InvitationsManage], ['cancelInvitation', CompanyPermission::InvitationsManage],
    ['manageOwnership', CompanyPermission::OwnershipManage],
]);

it('allows only verified members to leave', function () {
    $company = CompanyFactory::new()->create();
    $member = UserFactory::new()->create();
    $unverified = UserFactory::new()->unverified()->create();
    $company->users()->attach([$member->id, $unverified->id]);
    expect($member->can('leave', $company))->toBeTrue()->and($unverified->can('leave', $company))->toBeFalse();
});
