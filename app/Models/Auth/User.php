<?php

namespace App\Models\Auth;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[RouteKey('uuid')]
class User extends Authenticatable
{
    use Notifiable;
    use HasUuid;

    protected $table = 'users';

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => strtolower($value),
        );
    }
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => ucwords($value),
        );
    }
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
