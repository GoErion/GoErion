<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'display_name', 'is_system'])]
#[RouteKey('uuid')]
class Role extends Model
{
    use HasUuid;

    protected $table = 'roles';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['company_id', 'assigned_by'])
            ->withTimestamps();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function isOwner(): bool
    {
        return $this->is_system && $this->name === 'owner';
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }

    public function isEditable(): bool
    {
        return ! $this->is_system;
    }

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }
}
