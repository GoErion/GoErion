<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveAuthenticatedDestination;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Company\InvitationClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class LoginController extends Controller
{
    #[Middleware('guest')]
    public function create(): View
    {
        return view('auth.login.login');
    }

    #[Middleware('guest')]
    public function store(LoginRequest $request, ResolveAuthenticatedDestination $destination): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $intendedUrl = $request->session()->pull('url.intended');

        if (is_string($intendedUrl) && $this->isSafeIntendedUrl($request, $intendedUrl)) {
            return redirect($intendedUrl);
        }

        return redirect($destination->handle($request->user()));
    }

    #[Middleware('auth')]
    public function destroy(Request $request): RedirectResponse
    {
        $key = InvitationClaim::SESSION_KEY;
        $claim = $request->boolean('return_to_invitation') ? $request->session()->get($key) : null;
        $validClaim = app(InvitationClaim::class)->find($claim);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($validClaim) {
            $request->session()->put($key, $claim);

            return redirect()->route('invitations.review');
        }

        return redirect()->route('marketing');
    }

    private function isSafeIntendedUrl(Request $request, string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return str_starts_with($url, $request->getSchemeAndHttpHost().'/');
    }
}
