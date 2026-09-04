<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveAuthenticatedDestination;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final class VerifyEmailController extends Controller
{
    public function __invoke(
        EmailVerificationRequest $request,
        ResolveAuthenticatedDestination $destination,
    ): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect($destination->handle($request->user()))
            ->with('status', __('Your email address has been verified.'));
    }
}
