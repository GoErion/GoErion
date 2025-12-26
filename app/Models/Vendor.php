<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\LaravelUuid\HasUuids;

class Vendor extends Model
{
    use HasUuids;

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
