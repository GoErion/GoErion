<?php
declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\Company\Company;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name','display_name'])]
class Role extends Model
{
    use HasUuid;
    protected $table = 'roles';
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
