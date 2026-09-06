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

final readonly class CreateRole
{
    /** @param list<int> $permissionIds */
    public function handle(Company $company, User $actor, string $name, ?string $displayName, array $permissionIds): Role
    {
        return DB::transaction(function () use ($company, $actor, $name, $displayName, $permissionIds): Role {
            $company = Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageRoles', $company);
            $normalizedName = strtolower(trim($name));
            if ($normalizedName === 'owner' || $company->roles()->where('name', $normalizedName)->exists()) {
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
            $role = $company->roles()->create(['name' => $normalizedName, 'display_name' => $displayName, 'is_system' => false]);
            $role->permissions()->sync($permissions->modelKeys());
            $company->accessEvents()->create(['actor_user_id' => $actor->id, 'role_id' => $role->id, 'event_type' => CompanyAccessEventType::RoleCreated, 'metadata' => ['permissions' => $permissions->pluck('name')->all()]]);

            return $role->load('permissions');
        }, 3);
    }
}
