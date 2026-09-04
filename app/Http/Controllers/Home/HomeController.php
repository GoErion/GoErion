<?php

declare(strict_types=1);

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function home(Request $request, Company $company): View
    {
        Gate::authorize('view', $company);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('index', compact('companies', 'company'));
    }
}
