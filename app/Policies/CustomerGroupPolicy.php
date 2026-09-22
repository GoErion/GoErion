<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CustomerPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;

final class CustomerGroupPolicy
{
    public function manage(User $user, Company $company): bool
    {
        return $user->hasVerifiedEmail()
            && $user->hasCompanyPermission($company, CustomerPermission::GroupsManage->value);
    }
}
