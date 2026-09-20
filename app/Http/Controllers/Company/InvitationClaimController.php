<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Models\Company\CompanyInvitation;
use App\Services\Company\InvitationClaim;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InvitationClaimController extends Controller
{
    public function entry(Request $request, InvitationClaim $claims): RedirectResponse
    {
        $digest = $request->attributes->get('invitation_digest');
        $request->session()->forget(InvitationClaim::SESSION_KEY);
        if ($claims->find($digest)) {
            $request->session()->put(InvitationClaim::SESSION_KEY, $digest);
        }

        return redirect()->route('invitations.review');
    }

    public function review(Request $request, InvitationClaim $claims): View
    {
        $invitation = $claims->find($request->session()->get(InvitationClaim::SESSION_KEY));
        if (! $invitation) {
            $request->session()->forget([InvitationClaim::SESSION_KEY, 'url.intended']);
        } else {
            $request->session()->put('url.intended', route('invitations.review'));
        }
        $matches = $invitation && $request->user() && CompanyInvitation::normalizeEmail($request->user()->email) === $invitation->email;

        return view('company.invitations.review', compact('invitation', 'matches'));
    }

    public function accept(Request $request, AcceptInvitation $action): RedirectResponse
    {
        $digest = $request->session()->get(InvitationClaim::SESSION_KEY);
        try {
            $company = $action->handleClaim(is_string($digest) ? $digest : '', $request->user());
        } catch (DomainException) {
            $request->session()->forget([InvitationClaim::SESSION_KEY, 'url.intended']);

            return redirect()->route('invitations.review')->with('error', 'This invitation is not available. Ask the sender for a new invitation.');
        }
        $request->session()->forget([InvitationClaim::SESSION_KEY, 'url.intended']);

        return redirect()->route('home', $company)->with('success', 'Invitation accepted. Welcome to '.$company->company_name.'.');
    }
}
