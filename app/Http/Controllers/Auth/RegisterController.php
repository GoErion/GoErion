<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

#[Middleware('guest')]
final class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register.register');
    }

    public function store(RegisterRequest $request, RegisterUser $action): RedirectResponse
    {
        $user = $action->handle($request->validated());
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
use Illuminate\View\View;
