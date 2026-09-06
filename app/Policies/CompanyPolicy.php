<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CompanyPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;

final class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail()
            && $user->companies()->whereKey($company->getKey())->exists();
    }

    public function update(User $user, Company $company): bool
    {
        if (! $this->view($user, $company)) {
            return false;
        }

        return $user->hasCompanyPermission($company, CompanyPermission::Update->value);
    }

    public function viewMembers(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CompanyPermission::MembersView);
    }

    public function manageMembers(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CompanyPermission::MembersManage);
    }

    public function viewRoles(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CompanyPermission::RolesView);
    }

    public function manageRoles(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CompanyPermission::RolesManage);
    }

    public function inviteMembers(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CompanyPermission::InvitationsManage);
    }

    public function cancelInvitation(User $user, Company $company): bool
    {
        return $this->inviteMembers($user, $company);
    }

    public function leave(User $user, Company $company): bool
    {
        return $this->view($user, $company);
    }

    public function manageOwnership(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CompanyPermission::OwnershipManage)
            && $user->rolesForCompany($company)->where('roles.name', 'owner')->where('roles.is_system', true)->exists();
    }

    private function permitted(User $user, Company $company, CompanyPermission $permission): bool
    {
        return $this->view($user, $company) && $user->hasCompanyPermission($company, $permission->value);
    }
}
