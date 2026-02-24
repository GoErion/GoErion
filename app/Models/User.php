<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @method static where(string $string, $identifier)
 * @method static firstOrCreate(array $data)
 * @method static findOrFail(string $string)
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasUuid;

    protected $fillable = [
        'role_id',
        'provider_id',
        'status',
        'name',
        'phone',
        'email',
        'password',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'status' => StatusEnum::class,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
