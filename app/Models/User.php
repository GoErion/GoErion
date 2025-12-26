<?php

declare(strict_types=1);


namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Webpatser\LaravelUuid\HasUuids;

/**
 * @method static create(array $array)
 */
class User extends Authenticatable
{
    use HasUuids;
    protected $fillable = [
        'username',
        'pin',
        'password',
        'mnemonic',
        'msg_private_key',
        'msg_public_key',
        'msg_private_nonce',
        'msg_private_salt'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function products()
    {
        return $this->hasMany(Product::class,'user_id')
            ->where('active',true)
            ->orderByDesc('created_at');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
