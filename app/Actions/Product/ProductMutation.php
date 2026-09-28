<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Gate;

final class ProductMutation
{
    /** Call inside the owning transaction, before child locks or mutations. */
    public static function lock(Company $company, User $actor, string $ability): Company
    {
        $company = Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();
        self::authorize($company, $actor, $ability);

        return $company;
    }

    public static function authorize(Company $company, User $actor, string $ability): void
    {
        Gate::forUser(User::query()->findOrFail($actor->getKey()))->authorize($ability, [Product::class, $company]);
    }
}
