<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Actions\Company\InvitationRules;
use App\Models\Company\CompanyInvitation;

final class InvitationClaim
{
    public const string SESSION_KEY = 'company_invitation_digest';

    public function find(#[\SensitiveParameter] mixed $digest): ?CompanyInvitation
    {
        if (! is_string($digest) || ! preg_match('/\A[a-f0-9]{64}\z/', $digest)) {
            return null;
        }

        $invitation = CompanyInvitation::query()->with(['company', 'roles', 'invitedBy'])->where('token_hash', $digest)->first();

        if (! $invitation?->isPending() || $invitation->roles->isEmpty()
            || $invitation->roles->contains(fn ($role): bool => $role->company_id !== $invitation->company_id || $role->isSystem() || $role->name === 'owner')) {
            return null;
        }
        try {
            app(InvitationRules::class)->rejectMember($invitation->company, $invitation->email);
        } catch (\DomainException) {
            return null;
        }

        return $invitation;
    }
}
