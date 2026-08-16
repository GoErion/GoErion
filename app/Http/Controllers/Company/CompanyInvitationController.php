<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\AcceptInvitation;
use App\Actions\Company\CreateInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyInvitationRequest;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class CompanyInvitationController extends Controller
{
    public function create(Company $company)
    {
        $roles = $company->roles()->get();

        return view('company.invitations.create',compact('company','roles',));
    }

    public function store(CompanyInvitationRequest $request,Company $company,CreateInvitation $action): RedirectResponse
    {
        $invitation = $action->handle($request->validated(),$company,$request->user());

        return redirect()
            ->route('home')
            ->with('success', 'Invitation sent successfully.');
    }
    public function accept(string $token)
    {
        $invitation = CompanyInvitation::query()
            ->with('company', 'role', 'invitedBy',)
            ->where('token', $token)
            ->firstOrFail();

        return view('company.invitations.accept', compact('invitation'));
    }
    public function confirm(Request $request,
        string $token,AcceptInvitation $action)
    {
        $invitation = CompanyInvitation::query()
                ->where('token', $token)
                ->firstOrFail();

        $action->handle($invitation,$request->user());

        return redirect()->route('home')
            ->with('success', 'You have successfully joined the company.');
    }
}
