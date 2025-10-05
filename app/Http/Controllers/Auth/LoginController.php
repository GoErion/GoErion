<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\utility\Captcha;
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

    public function store(Request $request)
    {
        dd($request->all());
    }
}
