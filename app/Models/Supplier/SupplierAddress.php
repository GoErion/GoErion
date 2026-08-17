<?php
declare(strict_types=1);

namespace App\Models\Supplier;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_id','type','line_1','line_2','city','postal_code','country','is_default',])]
#[RouteKey('uuid')]
class SupplierAddress extends Model
{
    use HasUuid;
    protected $table = 'supplier_addresses';

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
