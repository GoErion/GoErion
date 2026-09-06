<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Auth\ResolveAuthenticatedDestination;
use App\Actions\Company\LeaveCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\LeaveCompanyRequest;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CompanyMembershipController extends Controller
{
    public function confirm(LeaveCompanyRequest $request, Company $company): View
    {
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.members.leave', compact('company', 'companies'));
    }

    public function destroy(LeaveCompanyRequest $request, Company $company, LeaveCompany $action, ResolveAuthenticatedDestination $destination): RedirectResponse
    {
        try {
            $action->handle($company, $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->to($destination->handle($request->user()))->with('success', 'You left the company.');
    }
}
