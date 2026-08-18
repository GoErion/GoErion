<?php
declare(strict_types=1);

namespace App\Models\Product;

use App\Enums\ProductType;
use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id','category_id','unit_id','brand_id','sku','name','description','product_type','track_stock','track_serial','track_batch','is_active',])]
#[RouteKey('uuid')]
class Product extends Model
{
    use HasUuid;
    protected $table = 'products';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'track_stock' => 'boolean',
            'track_serial' => 'boolean',
            'track_batch' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
