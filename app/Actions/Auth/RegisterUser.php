<?php
declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Auth\User;

final readonly class RegisterUser
{
    /**
     * @param  array{name: string, email: string, password: string}  $input
     */
    public function handle(array $input): User
    {
        return User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
