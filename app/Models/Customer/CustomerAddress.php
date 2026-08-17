<?php
declare(strict_types=1);

namespace App\Models\Customer;

use App\Enums\CustomerAddressType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['customer_id','type','line_1','line_2','city','postal_code','country','is_default',])]
#[RouteKey('uuid')]
class CustomerAddress extends Model
{
    use HasUuid;
    protected $table = 'customer_addresses';

    protected function casts()
    {
        return [
            'is_default' => 'boolean',
            'type' => CustomerAddressType::class,
        ];
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
