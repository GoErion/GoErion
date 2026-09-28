<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Brand;
use Illuminate\Support\Facades\DB;

final readonly class ArchiveBrand
{
    public function handle(Company $company, Brand $brand, User $actor): Brand
    {
        return DB::transaction(function () use ($company, $brand, $actor): Brand {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $brand = $company->brands()->whereKey($brand->id)->lockForUpdate()->firstOrFail();
            $brand->update(['is_active' => false]);

            return $brand;
        }, 3);
    }
}
