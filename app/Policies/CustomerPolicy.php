<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CustomerPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Customer\Customer;

final class CustomerPolicy
{
    public function viewAny(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CustomerPermission::View);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->permitted($user, $customer->company, CustomerPermission::View);
    }

    public function create(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CustomerPermission::Create);
    }

    public function update(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CustomerPermission::Update);
    }

    public function archive(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CustomerPermission::Archive);
    }

    public function manageCredit(User $user, Company $company): bool
    {
        return $this->permitted($user, $company, CustomerPermission::CreditManage);
    }

    private function permitted(User $user, Company $company, CustomerPermission $permission): bool
    {
        return $user->hasVerifiedEmail()
            && $user->hasCompanyPermission($company, $permission->value);
    }
}
