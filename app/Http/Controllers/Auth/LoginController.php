<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

final class LoginController extends Controller
{
    #[Middleware('guest')]
    public function create()
    {
        return view('auth.login.login');
    }

    #[Middleware('throttle:login')]
    #[Middleware('guest')]    
    public function store(LoginRequest $request,Company $company)
    {
        //attempt to login
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember')))
        {
            $request->session()->regenerate();

            return redirect()->intended(route('home', absolute: false));
        }
        return redirect()->intended(route('home',$company))->withErrors([
            'email' => 'These credentials do not match our records.'
        ])->withInput($request->except('password'));
    }
    
    //logout action
    #[Middleware('auth')]
    public function destroy(Request $request)
    {
      Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('marketing');
    }
}
