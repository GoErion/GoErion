<?php
declare(strict_types=1);

namespace App\Models\Product;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id','name','symbol',])]
#[RouteKey('uuid')]
class Unit extends Model
{
    use HasUuid;
    protected $table = 'units';
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
