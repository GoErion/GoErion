<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Enums\CompanyPermission;
use App\Enums\CustomerPermission;
use App\Enums\ProductPermission;
use App\Enums\SupplierPermission;
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
            ->whereIn('name', self::assignableNames());
    }

    /** @return list<string> */
    public static function assignableNames(): array
    {
        return array_merge(
            array_column(CompanyPermission::assignable(), 'value'),
            array_column(CustomerPermission::cases(), 'value'),
            array_column(SupplierPermission::cases(), 'value'),
            array_column(ProductPermission::cases(), 'value'),
        );
    }

    protected function casts(): array
    {
        return ['assignable_to_custom_roles' => 'boolean'];
    }
}
