<?php

declare(strict_types=1);

namespace App\Models\Supplier;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_id', 'name', 'email', 'phone', 'position', 'is_primary'])]
#[RouteKey('uuid')]
class SupplierContact extends Model
{
    use HasUuid;

    protected $table = 'supplier_contacts';

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
