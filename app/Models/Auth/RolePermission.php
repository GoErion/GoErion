<?php
declare(strict_types=1);

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';
}
