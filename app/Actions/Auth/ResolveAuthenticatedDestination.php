<?php
declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Auth\User;

final readonly class ResolveAuthenticatedDestination
{
    public function handle(User $user): string
    {
        if (! $user->hasVerifiedEmail()) {
            return route('verification.notice');
        }

        $companies = $user->companies()
            ->select(['companies.id', 'companies.uuid'])
            ->limit(2)
            ->get();

        return match ($companies->count()) {
            0 => route('company.create'),
            1 => route('home', $companies->first()),
            default => route('companies.select'),
        };
    }
}
