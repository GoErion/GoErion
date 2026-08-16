<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id','type','prefix','next_number'])]
#[RouteKey('uuid')]
class NumberSequence extends Model
{
    use HasUuid;
    protected $table = 'number_sequences';
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }
}
