<?php
declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Company\Company;
use App\Services\Company\NumberSequenceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class CreateProduct
{
    public function __construct(private readonly NumberSequenceService $sequence)
    {
    }
    public function handle(array $input,Company $company)
    {
        return DB::transaction(function() use($company,$input)
        {
            //Category must belong to this company.
            $categoryExists = $company->categories()->whereKey($input['category_id'])->exists();
            if (! $categoryExists)
            {
                throw new AuthorizationException(
                    'The selected category does not belong to this company.'
                );
            }
            //Unit must belong to this company.
            $unitExists = $company->units()
                ->whereKey($input['unit_id'])
                ->exists();

            if (! $unitExists)
            {
                throw new AuthorizationException(
                    'The selected unit does not belong to this company.'
                );
            }
            //Brand is optional. && If supplied, it must belong to this company.
            if (! empty($input['brand_id']))
            {

                $brandExists = $company->brands()
                    ->whereKey($input['brand_id'])
                    ->exists();

                if (! $brandExists)
                {
                    throw new AuthorizationException(
                        'The selected brand does not belong to this company.'
                    );
                }
            }
            // Generate SKU if user did not provide one.

            $sku = $input['sku'] ?? null;

            if (blank($sku))
            {

                $sequence = $company->numberSequences()
                    ->where('type', 'product')
                    ->first();

                if (! $sequence)
                {
                    $sequence = $company->numberSequences()->create([
                        'type' => 'product',
                        'prefix' => 'SKU',
                        'next_number' => 1,
                    ]);
                }

                $sku = $this->sequence->next($sequence);
            }
            //Tracking rules.
            if ( ($input['track_serial'] ?? false) && ! ($input['track_stock'] ?? true))
            {
                throw new \InvalidArgumentException(
                    'Serial tracking requires stock tracking.'
                );
            }
            if ( ($input['track_batch'] ?? false) && ! ($input['track_stock'] ?? true))
            {
                throw new \InvalidArgumentException(
                    'Batch tracking requires stock tracking.'
                );
            }
            //Create product through the company relationship.
            return $company->products()->create([
                'category_id' => $input['category_id'],
                'unit_id' => $input['unit_id'],
                'brand_id' => $input['brand_id'] ?? null,
                'sku' => strtoupper($sku),
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'product_type' => $input['product_type'],
                'track_stock' => $input['track_stock'] ?? true,
                'track_serial' => $input['track_serial'] ?? false,
                'track_batch' => $input['track_batch'] ?? false,
                'is_active' => true,
            ]);
        });
    }
}
