<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Http\Requests\Company\WarehouseRequest;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['branch_id', 'warehouse_name', 'code', 'is_active'])]
#[RouteKey('uuid')]
class Warehouse extends Model
{
    use HasUuid;
    protected $table = 'warehouses';
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class);
    }
}
