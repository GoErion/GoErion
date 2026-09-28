<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Product;
use App\Services\Company\NumberSequenceService;
use App\Services\Product\ProductInput;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class CreateProduct
{
    public function __construct(private NumberSequenceService $numbers) {}

    /** @param array<string, mixed> $values */
    public function handle(Company $company, User $actor, array $values): Product
    {
        return DB::transaction(function () use ($company, $actor, $values): Product {
            $company = ProductMutation::lock($company, $actor, 'create');
            $values = ProductInput::validate($values, $company);
            if (! empty($values['prices'])) {
                ProductMutation::authorize($company, $actor, 'managePrices');
            }
            if (! empty($values['barcodes'])) {
                ProductMutation::authorize($company, $actor, 'manageBarcodes');
            }
            $sequence = $company->numberSequences()->firstOrCreate(['type' => 'product'], ['prefix' => 'SKU', 'next_number' => 1]);
            if (! is_string($sequence->prefix) || trim($sequence->prefix) === '' || $sequence->next_number < 1 || $sequence->next_number >= 2147483647) {
                throw new DomainException('Product sequence is invalid or exhausted.');
            }
            $sku = $this->numbers->next($sequence);
            if ($company->products()->where('sku', $sku)->exists()) {
                throw new DomainException('Product sequence collides with a legacy SKU; allocation rolled back.');
            }
            $prices = $values['prices'] ?? [];
            $barcodes = $values['barcodes'] ?? [];
            unset($values['prices'], $values['barcodes']);
            $product = new Product([...$values, 'is_active' => true]);
            $product->company()->associate($company);
            $product->sku = $sku;
            $product->save();
            foreach ($prices as $price) {
                $product->prices()->create([...$price, 'company_id' => $company->id]);
            }
            foreach ($barcodes as $barcode) {
                $product->barcodes()->create([...$barcode, 'company_id' => $company->id]);
            }

            return $product;
        }, 3);
    }
}
