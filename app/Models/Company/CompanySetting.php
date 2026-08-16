<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id','key','value'])]
#[RouteKey('uuid')]
class CompanySetting extends Model
{
    use HasUuid;
    protected $table = 'company_settings';
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
