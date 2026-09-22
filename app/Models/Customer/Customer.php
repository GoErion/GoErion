<?php

declare(strict_types=1);

namespace App\Models\Customer;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Fillable(['company_id', 'name', 'code', 'email', 'phone', 'tax_pin', 'credit_limit', 'payment_terms_days', 'is_active'])]
#[RouteKey('uuid')]
class Customer extends Model
{
    use HasUuid;

    protected $table = 'customers';

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function groupMembership(): HasOne
    {
        return $this->hasOne(CustomerGroupMembership::class);
    }

    public function group(): HasOneThrough
    {
        return $this->hasOneThrough(
            CustomerGroup::class,
            CustomerGroupMembership::class,
            'customer_id',
            'id',
            'id',
            'customer_group_id',
        );
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }
}
