<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateCompany;
use App\Actions\Company\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function create(): View
    {
        return view('company.create');
    }

    public function store(StoreCompanyRequest $request, CreateCompany $action): RedirectResponse
    {
        $company = $action->handle($request->validated(), $request->user());

        return redirect()->route('home', $company)->with('success', 'Company created successfully.');
    }

    public function edit(Request $request, Company $company): View
    {
        Gate::authorize('update', $company);

        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.edit', compact('company', 'companies'));
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): RedirectResponse
    {
        $action->handle($company, $request->validated());

        return redirect()->route('home', $company)->with('success', 'Company profile updated successfully.');
    }
}
