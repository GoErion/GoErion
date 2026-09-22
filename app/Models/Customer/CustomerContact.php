<?php

declare(strict_types=1);

namespace App\Models\Customer;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'name', 'email', 'phone', 'position', 'is_primary'])]
#[RouteKey('uuid')]
class CustomerContact extends Model
{
    use HasUuid;

    protected $table = 'customer_contacts';

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
