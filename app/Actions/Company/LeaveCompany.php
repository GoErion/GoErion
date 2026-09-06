<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class LeaveCompany
{
    public function handle(Company $company, User $member): void
    {
        DB::transaction(function () use ($company, $member): void {
            $lockedCompany = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            if (! $lockedCompany->users()->whereKey($member)->exists()) {
                throw new DomainException('You are not a company member.');
            }
            $ownerRole = $lockedCompany->ownerRole()->firstOrFail();
            $isOwner = DB::table('user_roles')->where(['user_id' => $member->id, 'role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id])->exists();
            if ($isOwner && DB::table('user_roles')->where(['role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id])->count() <= 1) {
                throw new DomainException('The last owner cannot leave the company.');
            }
            $roles = $member->rolesForCompany($lockedCompany)->pluck('uuid')->all();
            $lockedCompany->accessEvents()->create(['actor_user_id' => $member->id, 'subject_user_id' => $member->id, 'event_type' => CompanyAccessEventType::MemberLeft, 'metadata' => ['role_uuids' => $roles]]);
            $lockedCompany->users()->detach($member->id);
        }, 3);
    }
}
