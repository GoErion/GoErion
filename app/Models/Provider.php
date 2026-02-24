<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Provider extends Model
{
    use  HasUuid;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
