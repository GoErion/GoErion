<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webpatser\LaravelUuid\HasUuids;

class Product extends Model
{
    use HasUuids;

    public function category()
    {
        return $this->hasOne(Category::class,'id','category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
