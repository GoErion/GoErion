<?php
declare(strict_types=1);

namespace App\Models\Supplier;

use App\Models\Company\Company;
use App\Models\Product\Product;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id','name','code','email','phone','payment_terms_days','is_active',])]
#[RouteKey('uuid')]
class Supplier extends Model
{
    use HasUuid;
    protected $table = 'suppliers';
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class);
    }
    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class,'product_suppliers')->withPivot(['supplier_sku','cost',])    ->withTimestamps();
    }
    protected function casts()
    {
        return [
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
