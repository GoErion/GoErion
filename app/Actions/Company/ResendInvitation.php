<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use App\Services\Company\InvitationToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final readonly class ResendInvitation
{
    public function __construct(private InvitationToken $tokens, private InvitationRules $rules) {}

    public function handle(Company $company, CompanyInvitation $invitation, User $actor): InvitationDelivery
    {
        return DB::transaction(function () use ($company, $invitation, $actor): InvitationDelivery {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            $actor = User::query()->findOrFail($actor->id);
            Gate::forUser($actor)->authorize('inviteMembers', $company);
            $invitation = $company->invitations()->whereKey($invitation)->lockForUpdate()->firstOrFail();
            if (! $invitation->isPending()) {
                InvitationRules::unavailable();
            }
            $this->rules->rejectMember($company, $invitation->email);
            $this->rules->roles($company, $invitation->roles()->pluck('roles.id')->all());
            if (! $invitation->isPending()) {
                InvitationRules::unavailable();
            }
            $rawToken = $this->tokens->generate();
            if (! $invitation->update(['token_hash' => $this->tokens->digest($rawToken), 'expires_at' => $this->rules->expiresAt()])) {
                throw new RuntimeException('Invitation could not be resent.');
            }
            $this->rules->record($invitation, $actor, CompanyAccessEventType::InvitationResent);

            return new InvitationDelivery($invitation, $rawToken);
        }, 3);
    }
}
