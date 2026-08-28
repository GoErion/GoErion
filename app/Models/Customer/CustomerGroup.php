<?php
declare(strict_types=1);

namespace App\Models\Customer;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id','name','code','description','is_active',])]
#[RouteKey('uuid')]
class CustomerGroup extends Model
{
    use HasUuid;
    protected $table = 'customer_groups';

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
