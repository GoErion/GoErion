<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\RequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\utility\Captcha;
use Exception;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    //
    public function index()
    {
        return view('auth.login')->with([
            'captcha'=>Captcha::Build()
        ]);
    }

    /**
     * @throws Exception
     */
    public function store(LoginRequest $request)
    {
        try {
            return $request->persist();
        }catch (RequestException $e)
        {
            session()->flash('errormessage', $e->getMessage());
            return redirect()->back();
        }
    }

    public function logout()
    {
        auth()->logout();
        session()->flush();
        return redirect()->route('home')
            ->with('success_message', 'You have been logged out');
    }
}
