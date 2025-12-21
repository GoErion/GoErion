<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Illuminate\Http\Request;
use Random\RandomException;
use SodiumException;

final class RegisterController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * @throws SodiumException
     * @throws RandomException
     */
    public function store(RegisterRequest $request, RegisterAction $registerAction)
    {
        $validated = $request->validated();

        $user = $registerAction->handle($validated);
        return redirect()->route('');
    }
}
