<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanySelectionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $companies = $request->user()
            ->companies()
            ->orderBy('company_name')
            ->get();

        return view('company.select', compact('companies'));
    }
}
