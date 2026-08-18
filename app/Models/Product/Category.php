<?php
declare(strict_types=1);

namespace App\Models\Product;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id','parent_id','name','code'])]
#[RouteKey('uuid')]
class Category extends Model
{
    use HasUuid;
    protected $table = 'categories';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function parent()
    {
        return $this->belongsTo(Category::class,'parent_id');
    }
    public function children(): HasMany
    {
        return $this->hasMany(Category::class,'parent_id');
    }
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
