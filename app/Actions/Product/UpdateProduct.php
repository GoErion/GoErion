<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Product;
use App\Services\Product\ProductInput;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProduct
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Product $product, User $actor, array $values): Product
    {
        return DB::transaction(function () use ($company, $product, $actor, $values): Product {
            $company = ProductMutation::lock($company, $actor, 'update');
            ProductMutation::authorize($company, $actor, 'viewAny');
            $product = $company->products()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $product->update(ProductInput::validateProfile($values, $company, $product));

            return $product;
        }, 3);
    }
}
