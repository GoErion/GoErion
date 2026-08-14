<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['branch_name', 'code', 'email', 'phone', 'address','company_id'])]
#[RouteKey('uuid')]
class Branch extends Model
{
    use HasUuid;
    protected $table = 'branches';
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
