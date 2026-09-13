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

final readonly class CreateInvitation
{
    public function __construct(private InvitationToken $tokens, private InvitationRules $rules) {}

    /** @param array<int, int> $roleIds */
    public function handle(Company $company, User $actor, string $email, array $roleIds): InvitationDelivery
    {
        return DB::transaction(function () use ($company, $actor, $email, $roleIds): InvitationDelivery {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            $actor = User::query()->findOrFail($actor->id);
            Gate::forUser($actor)->authorize('inviteMembers', $company);
            $email = CompanyInvitation::normalizeEmail($email);
            if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                InvitationRules::unavailable();
            }
            $this->rules->rejectMember($company, $email);
            $roles = $this->rules->roles($company, $roleIds);
            $key = CompanyInvitation::activeKey($company->id, $email);
            $existing = $company->invitations()->where('active_key', $key)->lockForUpdate()->first();
            if ($existing) {
                if ($existing->isPending()) {
                    InvitationRules::unavailable();
                }
                $company->invitations()->whereKey($existing)->update(['active_key' => null]);
            }
            $rawToken = $this->tokens->generate();
            $invitation = $company->invitations()->create([
                'email' => $email, 'invited_by' => $actor->id, 'token_hash' => $this->tokens->digest($rawToken),
                'expires_at' => $this->rules->expiresAt(),
            ]);
            $invitation->roles()->attach($roles->modelKeys(), ['company_id' => $company->id]);
            $this->rules->record($invitation, $actor, CompanyAccessEventType::InvitationCreated);

            return new InvitationDelivery($invitation, $rawToken);
        }, 3);
    }
}
