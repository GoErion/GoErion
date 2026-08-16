<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['warehouse_id','name','code','is_active',])]
#[RouteKey('uuid')]
class WarehouseLocation extends Model
{
    use HasUuid;
    protected $table = 'warehouse_locations';
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
