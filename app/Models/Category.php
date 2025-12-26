<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\LaravelUuid\HasUuids;

class Category extends Model
{
    use HasUuids;

    public function products()
    {
        return $this->hasMany(Product::class,'category_id','id')
            ->where('active',true);
    }
}
