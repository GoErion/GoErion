<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProductPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;

final class ProductPolicy
{
    public function viewAny(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::View->value);
    }

    public function create(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::Create->value);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::Update->value);
    }

    public function archive(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::Archive->value);
    }

    public function managePrices(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::PricesManage->value);
    }

    public function manageBarcodes(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::BarcodesManage->value);
    }

    public function manageSuppliers(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::SuppliersManage->value);
    }

    public function viewCost(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::CostView->value);
    }

    public function manageClassifications(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail() && $user->hasCompanyPermission($company, ProductPermission::ClassificationsManage->value);
    }
}
