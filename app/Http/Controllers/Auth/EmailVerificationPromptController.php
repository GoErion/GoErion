<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveAuthenticatedDestination;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request, ResolveAuthenticatedDestination $destination): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($destination->handle($request->user()));
        }

        return view('auth.verify-email');
    }
}
