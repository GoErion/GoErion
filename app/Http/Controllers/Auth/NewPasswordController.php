<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use App\Models\Auth\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;

#[Middleware('guest')]
final class NewPasswordController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.forgot-password.reset-password',compact('request'));
    }
    public function store(NewPasswordRequest $request)
    {
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'),
        function(User $user,string $password)
        {
            $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));

            $user->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
