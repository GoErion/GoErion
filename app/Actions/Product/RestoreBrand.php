<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Brand;
use Illuminate\Support\Facades\DB;

final readonly class RestoreBrand
{
    public function handle(Company $company, Brand $record, User $actor): Brand
    {
        return DB::transaction(function () use ($company, $record, $actor): Brand {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $record = $company->brands()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $record->update(['is_active' => true]);

            return $record;
        }, 3);
    }
}
