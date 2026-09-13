<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final readonly class CancelInvitation
{
    public function __construct(private InvitationRules $rules) {}

    public function handle(Company $company, CompanyInvitation $invitation, User $actor): void
    {
        DB::transaction(function () use ($company, $invitation, $actor): void {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            $actor = User::query()->findOrFail($actor->id);
            Gate::forUser($actor)->authorize('cancelInvitation', $company);
            $invitation = $company->invitations()->whereKey($invitation)->lockForUpdate()->firstOrFail();
            if (! $invitation->isPending()) {
                InvitationRules::unavailable();
            }
            if (! $invitation->update(['cancelled_at' => now('UTC'), 'cancelled_by' => $actor->id])) {
                throw new RuntimeException('Invitation could not be cancelled.');
            }
            $this->rules->record($invitation, $actor, CompanyAccessEventType::InvitationCancelled);
        }, 3);
    }
}
