<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Profile;

use App\Actions\Auth\DeleteAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Profile\ProfileRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

#[Middleware('auth')]
final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->string('email')->toString();

        if ($request->filled('password')) {
            Auth::logoutOtherDevices($request->string('current_password')->toString());
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('status', 'A verification link has been sent to your new email address.');
        }

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request, DeleteAccount $action): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);
        $user = $request->user();

        try {
            $action->handle($user);
        } catch (DomainException $exception) {
            return back()->withErrors(['password' => $exception->getMessage()], 'userDeletion');
        }

        /** The deleted identity must not be saved again by remember-token rotation. */
        $user->setRememberToken(null);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('marketing');
    }
}
