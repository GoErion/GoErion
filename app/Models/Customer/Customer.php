<?php
declare(strict_types=1);

namespace App\Models\Customer;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id','name','code','email','phone','credit_limit','payment_terms_days','is_active'])]
#[RouteKey('uuid')]
class Customer extends Model
{
    use HasUuid;
    protected $table = 'customers';

    protected function casts()
    {
        return [
            'credit_limit' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }
}
