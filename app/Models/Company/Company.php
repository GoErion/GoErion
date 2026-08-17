<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Customer\Customer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['company_name', 'legal_name', 'registration_number', 'tax_number', 'email', 'phone', 'address','currency','status'])]
#[RouteKey('uuid')]
class Company extends Model
{
    use HasUuid;
    protected $table = 'companies';
   public function users(): BelongsToMany
   {
        return $this->belongsToMany(User::class,'company_user');
   }
   public function roles()
   {
     return $this->hasMany(Role::class);
   }
   public function branches()
   {
      return $this->hasMany(Branch::class);
   }
   public function settings()
   {
      return $this->hasMany(CompanySetting::class);
   }
   public function setting(string $key, mixed $default = null): mixed
   {
      return $this->settings
         ->firstWhere('key', $key)
         ?->value ?? $default;
   }
   public function numberSequences()
   {
      return $this->hasMany(NumberSequence::class);
   }
   public function invitations()
   {
      return $this->hasMany(CompanyInvitation::class);
   }
   public function customers()
   {
      return $this->hasMany(Customer::class);
   }
}
