<?php
declare(strict_types=1);

namespace App\Models\Customer;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['customer_id','name','email','phone','position',])]
#[RouteKey('uuid')]
class CustomerContact extends Model
{
    use HasUuid;
    protected $table = 'customer_contacts';
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
