<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SupplierPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;

final class SupplierPolicy
{
    public function viewAny(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, SupplierPermission::View->value);
    }

    public function create(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, SupplierPermission::Create->value);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, SupplierPermission::Update->value);
    }

    public function archive(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, SupplierPermission::Archive->value);
    }

    public function manageTerms(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, SupplierPermission::TermsManage->value);
    }
}
