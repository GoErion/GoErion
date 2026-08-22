<?php
declare(strict_types=1);

namespace App\Models\Product;

use App\Enums\PriceType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id','price_type','price','currency',])]
#[RouteKey('uuid')]
class ProductPrice extends Model
{
    use HasUuid;
    protected $table = 'product_prices';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'price' => 'decimal:4',
        ];
    }
}
