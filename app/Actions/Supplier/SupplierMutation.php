<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use Illuminate\Support\Facades\Gate;

final class SupplierMutation
{
    /** Called only inside the mutation's transaction. Shared Company locks serialize
     * authority changes without preventing independent Supplier edits.
     */
    public static function lock(Company $company, Supplier $supplier, User $actor, string $ability): Supplier
    {
        $company = Company::query()->whereKey($company->getKey())->sharedLock()->firstOrFail();
        self::authorize($company, $actor, $ability);
        $supplier = $company->suppliers()->whereKey($supplier->getKey())->lockForUpdate()->firstOrFail();
        self::authorize($company, $actor, $ability);

        return $supplier;
    }

    public static function authorize(Company $company, User $actor, string $ability): void
    {
        $actor = User::query()->findOrFail($actor->getKey());
        Gate::forUser($actor)->authorize($ability, [Supplier::class, $company]);
    }
}
