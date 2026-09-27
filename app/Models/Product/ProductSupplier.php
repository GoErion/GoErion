<?php

declare(strict_types=1);

namespace App\Models\Product;

use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['company_id', 'supplier_sku', 'cost'])]
class ProductSupplier extends Pivot
{
    protected $table = 'product_suppliers';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
        ];
    }
}
