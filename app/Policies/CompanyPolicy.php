<?php

declare(strict_types=1);

namespace App\Policies;

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

        return $user->roles()
            ->where('roles.company_id', $company->getKey())
            ->whereHas('permissions', fn ($query) => $query->where('name', 'company.update'))
            ->exists();
    }
}
