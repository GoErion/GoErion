<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Password::defaults(function()
        {
            if ($this->app->environment('local'))
            {
                return Password::min(8);
            }
            return Password::min(8)
                ->mixedCase()
                ->uncompromised(2)
                ->letters()
                ->numbers()
                ->symbols();
        });
    }
}
