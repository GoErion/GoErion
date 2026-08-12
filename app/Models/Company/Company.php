<?php
declare(strict_types=1);

namespace App\Models\Company;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_name', 'legal_name', 'registration_number', 'tax_number', 'email', 'phone', 'address',])]
#[RouteKey('uuid')]
class Company extends Model
{
    use HasUuid;
    protected $table = 'companies';
   
}
