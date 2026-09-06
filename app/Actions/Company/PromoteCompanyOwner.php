<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class PromoteCompanyOwner
{
    public function handle(Company $company, User $member, User $actor): void
    {
        DB::transaction(function () use ($company, $member, $actor): void {
            $lockedCompany = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageOwnership', $lockedCompany);
            if (! $lockedCompany->users()->whereKey($member)->exists()) {
                throw new DomainException('The user is not a company member.');
            }
            $ownerRole = $lockedCompany->ownerRole()->firstOrFail();
            if (DB::table('user_roles')->where(['user_id' => $member->id, 'role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id])->exists()) {
                return;
            }
            DB::table('user_roles')->insert(['user_id' => $member->id, 'role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id, 'assigned_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            $lockedCompany->accessEvents()->create(['actor_user_id' => $actor->id, 'subject_user_id' => $member->id, 'role_id' => $ownerRole->id, 'event_type' => CompanyAccessEventType::OwnerPromoted, 'metadata' => ['before' => ['is_owner' => false], 'after' => ['is_owner' => true]]]);
        }, 3);
    }
}
