<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteRole
{
    public function handle(Company $company, Role $role, User $actor): void
    {
        DB::transaction(function () use ($company, $role, $actor): void {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageRoles', $company);
            $role = $company->roles()->whereKey($role)->lockForUpdate()->firstOrFail();
            if (! $role->isEditable()) {
                throw new DomainException('System roles cannot be deleted.');
            }
            if ($role->users()->exists()) {
                throw new DomainException('Remove this role from all members before deleting it.');
            }
            if ($role->invitations()->where('company_invitations.company_id', $company->id)->wherePivot('company_id', $company->id)
                ->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '>', now('UTC'))->exists()) {
                throw new DomainException('Cancel pending invitations using this role before deleting it.');
            }
            $metadata = ['role_uuid' => $role->uuid, 'name' => $role->name];
            $company->accessEvents()->create(['actor_user_id' => $actor->id, 'role_id' => $role->id, 'event_type' => CompanyAccessEventType::RoleDeleted, 'metadata' => $metadata]);
            $role->delete();
        }, 3);
    }
}
