<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequest;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Password;

#[Middleware('guest')]
final class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password.forgot-password');
    }
    public function store(PasswordResetRequest $request)
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::ResetLinkSent 
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }
}