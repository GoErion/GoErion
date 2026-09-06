<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class DemoteCompanyOwner
{
    public function handle(Company $company, User $member, User $actor): void
    {
        DB::transaction(function () use ($company, $member, $actor): void {
            $lockedCompany = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageOwnership', $lockedCompany);
            $ownerRole = $lockedCompany->ownerRole()->firstOrFail();
            $assignment = DB::table('user_roles')->where(['user_id' => $member->id, 'role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id]);
            if (! $assignment->exists()) {
                throw new DomainException('The member is not an owner.');
            }
            if (DB::table('user_roles')->where(['role_id' => $ownerRole->id, 'company_id' => $lockedCompany->id])->count() <= 1) {
                throw new DomainException('The last owner cannot be demoted.');
            }
            $assignment->delete();
            $lockedCompany->accessEvents()->create(['actor_user_id' => $actor->id, 'subject_user_id' => $member->id, 'role_id' => $ownerRole->id, 'event_type' => CompanyAccessEventType::OwnerDemoted, 'metadata' => ['before' => ['is_owner' => true], 'after' => ['is_owner' => false]]]);
        }, 3);
    }
}
