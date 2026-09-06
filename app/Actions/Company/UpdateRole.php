<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateRole
{
    /** @param list<int> $permissionIds */
    public function handle(Company $company, Role $role, User $actor, string $name, ?string $displayName, array $permissionIds): Role
    {
        return DB::transaction(function () use ($company, $role, $actor, $name, $displayName, $permissionIds): Role {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageRoles', $company);
            if ($role->company_id !== $company->id || ! $role->isEditable()) {
                throw new DomainException('System roles cannot be edited.');
            }
            $normalizedName = strtolower(trim($name));
            if ($normalizedName === 'owner' || $company->roles()->where('name', $normalizedName)->whereKeyNot($role)->exists()) {
                throw new DomainException('That role name is unavailable.');
            }
            foreach ($permissionIds as $permissionId) {
                if ((! is_int($permissionId) && ! is_string($permissionId)) || filter_var($permissionId, FILTER_VALIDATE_INT) === false || (int) $permissionId < 1) {
                    throw new DomainException('One or more permissions are not assignable.');
                }
            }
            $permissions = Permission::query()->assignableToCustomRoles()->whereKey($permissionIds)->get();
            if ($permissions->count() !== count(array_unique($permissionIds))) {
                throw new DomainException('One or more permissions are not assignable.');
            }
            $before = ['name' => $role->name, 'display_name' => $role->display_name, 'permissions' => $role->permissions()->pluck('name')->all()];
            $role->update(['name' => $normalizedName, 'display_name' => $displayName]);
            $role->permissions()->sync($permissions->modelKeys());
            $company->accessEvents()->create(['actor_user_id' => $actor->id, 'role_id' => $role->id, 'event_type' => CompanyAccessEventType::RoleUpdated, 'metadata' => ['before' => $before, 'after' => ['name' => $role->name, 'display_name' => $role->display_name, 'permissions' => $permissions->pluck('name')->all()]]]);

            return $role->load('permissions');
        }, 3);
    }
}
