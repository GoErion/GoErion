<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\InvitationStatus;
use App\Models\Auth\User;
use App\Models\Company\CompanyInvitation;
use Illuminate\Support\Facades\DB;

final readonly class AcceptInvitation
{
    public function handle(CompanyInvitation $invitation, User $user): void
    {
        DB::transaction(function () use ($invitation, $user): void {
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $invitation = CompanyInvitation::query()
                ->lockForUpdate()
                ->findOrFail($invitation->id);
            if ($invitation->status !== InvitationStatus::Pending) {
                throw new \DomainException(
                    'This invitation is no longer available.'
                );
            }
            if ($invitation->expires_at->isPast()) {
                $invitation->update([
                    'status' => InvitationStatus::Expired,
                ]);
                throw new \DomainException(
                    'This invitation has expired.'
                );
            }
            // Make sure the authenticated user is the person who was invited.
            if ($user->email !== $invitation->email) {
                throw new \DomainException(
                    'This invitation was sent to another email address.'
                );
            }
            // Prevent duplicate company membership.
            if ($invitation->company->users()->whereKey($user->id)->exists()) {
                throw new \DomainException(
                    'You are already a member of this company.'
                );
            }

            // Attach the user to the company.
            $invitation->company->users()->attach($user->id);

            // Assign the role belonging to this company.
            $invitation->role->users()->attach($user->id, [
                'company_id' => $invitation->company_id,
                'assigned_by' => $invitation->invited_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mark invitation as accepted.
            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'accepted_at' => now(),
            ]);
        });
    }
}
