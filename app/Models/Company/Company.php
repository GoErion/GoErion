<?php

declare(strict_types=1);

namespace App\Models\Company;

use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerGroup;
use App\Models\Product\Brand;
use App\Models\Product\Category;
use App\Models\Product\Product;
use App\Models\Product\Unit;
use App\Models\Supplier\Supplier;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['company_name', 'legal_name', 'registration_number', 'tax_number', 'email', 'phone', 'address'])]
#[RouteKey('uuid')]
class Company extends Model
{
    use HasUuid;

    protected $table = 'companies';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function ownerRole(): HasOne
    {
        return $this->hasOne(Role::class)->where('name', 'owner')->where('is_system', true);
    }

    public function accessEvents(): HasMany
    {
        return $this->hasMany(CompanyAccessEvent::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings
            ->firstWhere('key', $key)
            ?->value ?? $default;
    }

    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function customerGroups(): HasMany
    {
        return $this->hasMany(CustomerGroup::class);
    }
}
