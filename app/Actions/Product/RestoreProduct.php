<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Product;
use Illuminate\Support\Facades\DB;

final readonly class RestoreProduct
{
    public function handle(Company $company, Product $record, User $actor): Product
    {
        return DB::transaction(function () use ($company, $record, $actor): Product {
            $company = ProductMutation::lock($company, $actor, 'archive');
            ProductMutation::authorize($company, $actor, 'viewAny');
            $record = $company->products()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $record->update(['is_active' => true]);

            return $record;
        }, 3);
    }
}
