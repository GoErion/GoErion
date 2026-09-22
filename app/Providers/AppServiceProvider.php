<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerGroup;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerGroupPolicy;
use App\Policies\CustomerPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerGroup::class, CustomerGroupPolicy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (['invitation-create', 'invitation-resend'] as $name) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id.':'.(is_string($request->route('company')) ? $request->route('company') : $request->route('company')?->uuid)));
        }
        RateLimiter::for('invitation-entry', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('invitation-accept', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id.':'.$request->ip()));
    }
}
