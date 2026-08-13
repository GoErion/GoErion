<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Request\Company\CompanyRequest;

final class CompanyController extends Controller
{
    public function store(CompanyRequest $request)
    {
        $validated = $request->validated();
    }
}
