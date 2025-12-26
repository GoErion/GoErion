<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Random\RandomException;
use SodiumException;

final class RegisterController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     */
    public function store(RegisterRequest $request, RegisterAction $registerAction)
    {
        try {
            $validated = $request->validated();

            $result = $registerAction->handle($validated);
            //flash mnemonic once for user to view in the next request
            session()->flash('mnemonic_key', encrypt($result['mnemonic']));

            return redirect()->route('mnemonic.show');
        }
        catch (\Exception $exception)
        {
            Log::error($exception);
            return redirect()->back();
        }
    }

    public function showMnemonic()
    {
        if (!session()->has('mnemonic_key'))
        {
            return redirect()->route('login');
        }
        $mnemonic = decrypt(session('mnemonic_key'));
        return view('auth.mnemonic')->with('mnemonic', $mnemonic);
    }
}
