<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Password;

#[Middleware('guest')]
final class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password.forgot-password');
    }
    public function store(PasswordResetRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->safe()->only(['email']));

        return back()->with('status', __('If an account exists for that email address, we have sent a password reset link.'));
    }
}
use Illuminate\View\View;
