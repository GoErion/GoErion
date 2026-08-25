<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Enums\FiscalYearStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id','name','year','starts_on','ends_on','status',])]
#[RouteKey('uuid')]
class FiscalYear extends Model
{
    use HasUuid;
    protected $table = 'fiscal_years';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => FiscalYearStatus::class,
        ];
    }
}
