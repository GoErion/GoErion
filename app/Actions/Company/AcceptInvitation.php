<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use App\Services\Company\InvitationToken;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class AcceptInvitation
{
    public function __construct(private InvitationToken $tokens, private InvitationRules $rules) {}

    public function handle(#[\SensitiveParameter] string $rawToken, User $user): Company
    {
        $digest = $this->tokens->digest($rawToken);

        return DB::transaction(function () use ($digest, $user): Company {
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            if (! $user || ! $user->hasVerifiedEmail()) {
                InvitationRules::unavailable();
            }
            $candidate = CompanyInvitation::query()->where('token_hash', $digest)->first(['id', 'company_id']);
            if (! $candidate) {
                InvitationRules::unavailable();
            }
            $company = Company::query()->whereKey($candidate->company_id)->lockForUpdate()->first();
            $invitation = CompanyInvitation::query()->whereKey($candidate->id)->lockForUpdate()->first();
            if (! $company || ! $invitation || ! hash_equals((string) $invitation->token_hash, $digest)
                || CompanyInvitation::normalizeEmail($user->email) !== $invitation->email) {
                InvitationRules::unavailable();
            }
            $member = DB::table('company_user')->where(['company_id' => $company->id, 'user_id' => $user->id])->lockForUpdate()->first();
            if ($invitation->accepted_at && $invitation->accepted_by === $user->id && $member) {
                return $company;
            }
            if (! $invitation->isPending() || $member) {
                InvitationRules::unavailable();
            }
            $roleIds = DB::table('company_invitation_roles')->where(['invitation_id' => $invitation->id, 'company_id' => $company->id])->lockForUpdate()->pluck('role_id')->all();
            $roles = $this->rules->roles($company, $roleIds);
            if (! $invitation->isPending()) {
                InvitationRules::unavailable();
            }
            $company->users()->attach($user->id);
            $user->roles()->attach($roles->modelKeys(), ['company_id' => $company->id, 'assigned_by' => $invitation->invited_by]);
            if (! $invitation->update(['accepted_at' => now('UTC'), 'accepted_by' => $user->id])) {
                throw new RuntimeException('Invitation could not be accepted.');
            }
            $this->rules->record($invitation, $user, CompanyAccessEventType::InvitationAccepted);
            $this->rules->record($invitation, $user, CompanyAccessEventType::MemberJoined);

            return $company;
        }, 3);
    }
}
