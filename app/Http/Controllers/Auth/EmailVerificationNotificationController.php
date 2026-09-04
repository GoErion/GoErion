<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveAuthenticatedDestination;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmailVerificationNotificationController extends Controller
{
    public function __invoke(Request $request, ResolveAuthenticatedDestination $destination): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($destination->handle($request->user()));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', __('A new verification link has been sent to your email address.'));
    }
}
