<?php
declare(strict_types=1);

namespace App\Models\Auth;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name','display_name'])]
#[RouteKey('uuid')]
class Permission extends Model
{
    use HasUuid;
    protected $table = 'permissions';
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
