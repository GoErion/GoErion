<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class RemoveCompanyMember
{
    public function handle(Company $company, User $member, User $actor): void
    {
        DB::transaction(function () use ($company, $member, $actor): void {
            $lockedCompany = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageMembers', $lockedCompany);
            if (! $lockedCompany->users()->whereKey($member)->exists()) {
                throw new DomainException('The user is not a company member.');
            }
            $ownerRole = $lockedCompany->ownerRole()->firstOrFail();
            if (DB::table('user_roles')->where(['user_id' => $member->id, 'role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id])->exists()) {
                throw new DomainException('An owner must be demoted before removal.');
            }
            $roles = $member->rolesForCompany($lockedCompany)->pluck('uuid')->all();
            $lockedCompany->accessEvents()->create(['actor_user_id' => $actor->id, 'subject_user_id' => $member->id, 'event_type' => CompanyAccessEventType::MemberRemoved, 'metadata' => ['role_uuids' => $roles]]);
            $lockedCompany->users()->detach($member->id);
        }, 3);
    }
}
