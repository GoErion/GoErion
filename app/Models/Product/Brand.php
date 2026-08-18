<?php
declare(strict_types=1);

namespace App\Models\Product;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id','name','code',])]
#[RouteKey('uuid')]
class Brand extends Model
{
    use HasUuid;
    protected $table = 'brands';
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
