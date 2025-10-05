<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\utility\Captcha;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RegisterController extends Controller
{
    public function index($refid = null)
    {
        return view('auth.register')->with([
            'refid'=>$refid,
            'captcha'=>Captcha::Build(),
        ]);
    }
    /**
     * Try to complete SignUpRequest, if success redirect to mnemonic
     * if fail redirect back
     * @param RegisterRequest $request
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request)
    {
        try {
            $request->persist();
            return redirect()->route('mnemonic');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back();
        }
    }

    /**
     * If there is mnemonic_key in session, show it to user, if not
     * redirect to login page
     * @return Factory|RedirectResponse|View
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function showMnemonic() {
        if (!session()->has('mnemonic_key'))
            return redirect()->route('login');
        return view('auth.mnemonic')->with('mnemonic', session()->get('mnemonic_key'));
    }
}
