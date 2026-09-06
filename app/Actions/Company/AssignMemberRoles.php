<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class AssignMemberRoles
{
    /** @param list<int> $roleIds */
    public function handle(Company $company, User $member, User $actor, array $roleIds): void
    {
        DB::transaction(function () use ($company, $member, $actor, $roleIds): void {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageMembers', $company);
            if (! $company->users()->whereKey($member)->exists()) {
                throw new DomainException('The user is not a company member.');
            }
            $roles = $company->roles()->whereKey($roleIds)->where('is_system', false)->get();
            if ($roles->count() !== count(array_unique($roleIds))) {
                throw new DomainException('One or more roles cannot be assigned.');
            }
            $before = $member->rolesForCompany($company)->where('is_system', false)->pluck('uuid')->all();
            DB::table('user_roles')->where('user_id', $member->id)->where('company_id', $company->id)
                ->whereIn('role_id', $company->roles()->where('is_system', false)->select('id'))->delete();
            $timestamp = now();
            foreach ($roles as $role) {
                DB::table('user_roles')->insertOrIgnore(['user_id' => $member->id, 'role_id' => $role->id, 'company_id' => $company->id, 'assigned_by' => $actor->id, 'created_at' => $timestamp, 'updated_at' => $timestamp]);
            }
            $company->accessEvents()->create(['actor_user_id' => $actor->id, 'subject_user_id' => $member->id, 'event_type' => CompanyAccessEventType::MemberRolesUpdated, 'metadata' => ['before_role_uuids' => $before, 'after_role_uuids' => $roles->pluck('uuid')->all()]]);
        }, 3);
    }
}
