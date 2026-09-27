<?php

declare(strict_types=1);

namespace App\Models\Supplier;

use App\Models\Company\Company;
use App\Models\Product\Product;
use App\Models\Product\ProductSupplier;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['company_id', 'name', 'code', 'email', 'phone', 'payment_terms_days', 'is_active', 'legal_name', 'tax_pin', 'registration_number', 'website', 'currency', 'lead_time_days', 'notes'])]
#[RouteKey('uuid')]
class Supplier extends Model
{
    use HasUuid;

    protected $table = 'suppliers';

    public function company(): BelongsTo
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
        return $this->belongsToMany(Product::class, 'product_suppliers')->using(ProductSupplier::class)->whereColumn('products.company_id', 'product_suppliers.company_id')->withPivot(['company_id', 'supplier_sku', 'cost'])->withTimestamps();
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(SupplierContact::class)->where('is_primary', true);
    }

    public function scopeEligibleForAssociation(Builder $query, Company $company): Builder
    {
        return $query->where('suppliers.company_id', $company->getKey())->where('suppliers.is_active', true);
    }

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
            'lead_time_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
