<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CancelInvitation;
use App\Actions\Company\CreateInvitation;
use App\Actions\Company\ResendInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyInvitationRequest;
use App\Http\Requests\Company\InvitationIndexRequest;
use App\Http\Requests\Company\ManageInvitationRequest;
use App\Models\Company\Company;
use App\Services\Company\SendCompanyInvitation;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CompanyInvitationController extends Controller
{
    public function index(InvitationIndexRequest $request, Company $company): View
    {
        $filters = $request->validated();
        $query = $company->invitations()->with(['roles', 'invitedBy'])->latest('id');
        if ($search = $filters['search'] ?? null) {
            $query->where('email', 'like', '%'.$search.'%');
        }
        $status = $filters['status'] ?? null;
        if ($status === 'accepted') {
            $query->whereNotNull('accepted_at');
        } elseif ($status === 'cancelled') {
            $query->whereNull('accepted_at')->whereNotNull('cancelled_at');
        } elseif (in_array($status, ['pending', 'expired'], true)) {
            $query->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', $status === 'pending' ? '>' : '<=', now('UTC'));
        }

        return view('company.invitations.index', [
            'company' => $company, 'companies' => $request->user()->companies()->get(),
            'invitations' => $query->paginate(12)->withQueryString(), 'filters' => $filters,
            'canInviteMembers' => $request->user()->can('inviteMembers', $company),
            'canCancelInvitations' => $request->user()->can('cancelInvitation', $company),
        ]);
    }

    public function create(ManageInvitationRequest $request, Company $company): View
    {
        return view('company.invitations.create', [
            'company' => $company, 'companies' => $request->user()->companies()->get(),
            'roles' => $company->roles()->where('is_system', false)->where('name', '!=', 'owner')->orderBy('display_name')->get(),
            'lifetimeDays' => config('invitations.lifetime_days'),
        ]);
    }

    public function store(CompanyInvitationRequest $request, Company $company, CreateInvitation $action, SendCompanyInvitation $sender): RedirectResponse
    {
        try {
            $delivery = $action->handle($company, $request->user(), $request->validated('email'), array_map(intval(...), $request->validated('role_ids')));
        } catch (DomainException) {
            return back()->withInput($request->only(['email', 'role_ids']))->withErrors(['email' => 'An invitation could not be created. Check the recipient and selected roles.']);
        }

        $sent = $sender->handle($delivery);

        return redirect()->route('company.invitations.index', $company)->with(
            $sent ? 'success' : 'error',
            $sent ? 'Invitation email sent.' : 'Invitation saved, but the email could not be sent. You can resend it.',
        );
    }

    public function resend(ManageInvitationRequest $request, Company $company, string $invitationUuid, ResendInvitation $action, SendCompanyInvitation $sender): RedirectResponse
    {
        $invitation = $company->invitations()->where('uuid', $invitationUuid)->firstOrFail();
        try {
            $delivery = $action->handle($company, $invitation, $request->user());
        } catch (DomainException) {
            return back()->with('error', 'This invitation is no longer available to resend.');
        }
        $sent = $sender->handle($delivery);

        return redirect()->route('company.invitations.index', $company)->with($sent ? 'success' : 'error', $sent ? 'A new invitation email was sent. Previous links are invalid.' : 'The previous link was invalidated, but the new email could not be sent. You can resend it.');
    }

    public function cancel(ManageInvitationRequest $request, Company $company, string $invitationUuid, CancelInvitation $action): RedirectResponse
    {
        $invitation = $company->invitations()->where('uuid', $invitationUuid)->firstOrFail();
        try {
            $action->handle($company, $invitation, $request->user());
        } catch (DomainException) {
            return back()->with('error', 'This invitation is no longer available to cancel.');
        }

        return redirect()->route('company.invitations.index', $company)->with('success', 'Invitation cancelled. Its link can no longer be used.');
    }
}
