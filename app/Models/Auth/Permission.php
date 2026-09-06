<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Enums\CompanyPermission;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'display_name', 'assignable_to_custom_roles'])]
#[RouteKey('uuid')]
class Permission extends Model
{
    use HasUuid;

    protected $table = 'permissions';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function scopeAssignableToCustomRoles(Builder $query): Builder
    {
        return $query
            ->where('assignable_to_custom_roles', true)
            ->whereIn('name', array_map(
                static fn (CompanyPermission $permission): string => $permission->value,
                CompanyPermission::assignable(),
            ));
    }

    protected function casts(): array
    {
        return ['assignable_to_custom_roles' => 'boolean'];
    }
}
