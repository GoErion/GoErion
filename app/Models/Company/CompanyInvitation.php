<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Enums\InvitationStatus;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id','role_id','email','token','status','expires_at','accepted_at', 'invited_by',])]
#[RouteKey('uuid')]
class CompanyInvitation extends Model
{
    use HasUuid;
    protected $table = 'company_invitations';

    protected function casts()
    {
        return [
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => strtolower($value),
        );
    }
    public function invitedBy()
    {
        return $this->belongsTo(User::class,'invited_by');
    }
}
