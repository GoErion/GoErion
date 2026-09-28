<?php

declare(strict_types=1);

namespace App\Models\Product;

use App\Enums\BarcodeType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'product_id', 'barcode', 'type'])]
#[RouteKey('uuid')]
class ProductBarcode extends Model
{
    use HasUuid;

    protected $table = 'product_barcodes';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'type' => BarcodeType::class,
        ];
    }
}
