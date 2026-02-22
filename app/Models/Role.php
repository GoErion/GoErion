<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string $value)
 * @method static firstOrCreate(array $array)
 */
class Role extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected $casts = [
        'name' => RoleEnum::class,
    ];
}
