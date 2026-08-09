<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Profile\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

#[Middleware('auth')]
final class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.profile.edit', ['user' => $request->user(),]);
    }
    public function update(ProfileRequest $request)
    {
        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password'))
        {
            $user->password = Hash::make($request->password);
        }

        if ($user->isDirty('email'))
        {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }
    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password']
        ]);
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
