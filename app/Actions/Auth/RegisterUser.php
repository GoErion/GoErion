<?php
declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;

final readonly class RegisterUser
{
    public function handle(array $input)
    {
        return User::create(
            [
                'name'=>$input['name'],
                'email'=>$input['email'],
                'password'=>Hash::make($input['password'])
            ]);
    }
}
