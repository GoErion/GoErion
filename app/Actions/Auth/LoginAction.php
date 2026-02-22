<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;

class LoginAction
{
    public function handle(array $credentials)
    {
        $throttles = Str::lower($credentials['email']);

        if (RateLimiter::tooManyAttempts($throttles,5))
        {
            throw  \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in a few minutes.',
            ]);
        }
        if (! Auth::attempt($credentials))
        {
            RateLimiter::hit($credentials);
        }
    }
}
