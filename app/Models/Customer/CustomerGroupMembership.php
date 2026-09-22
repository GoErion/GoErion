<?php

declare(strict_types=1);

namespace App\Models\Customer;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'customer_group_id', 'company_id'])]
class CustomerGroupMembership extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'customer_id';

    protected $table = 'customer_group_memberships';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
