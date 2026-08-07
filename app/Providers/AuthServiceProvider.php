<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('login', function (Request $request)
        {

            //login RateLimit
            $loginRateLimitResponse = function (Request $request)
            {
                if ($request->expectsJson())
                {
                    return response()->json(
                        [
                            'message' => 'Too many login attempts. please try again later.'
                        ],
                        429
                    );
                }
                return back()->withErrors([
                    'email' => 'Too many login attempts. please try again later.'
                ]);
            };
            return [
                Limit::perMinute(5)->by($request->ip())->response($loginRateLimitResponse),
                Limit::perMinute(4)->by($request->input('email'))->response($loginRateLimitResponse)
            ];
        });
    }
}
