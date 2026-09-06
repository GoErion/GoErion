<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\DemoteCompanyOwner;
use App\Actions\Company\PromoteCompanyOwner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\DemoteOwnerRequest;
use App\Http\Requests\Company\PromoteOwnerRequest;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CompanyOwnershipController extends Controller
{
    public function confirmPromotion(PromoteOwnerRequest $request, Company $company, string $memberUuid): View
    {
        $member = $this->member($company, $memberUuid);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.ownership.promote', compact('company', 'companies', 'member'));
    }

    public function store(PromoteOwnerRequest $request, Company $company, string $memberUuid, PromoteCompanyOwner $action): RedirectResponse
    {
        try {
            $action->handle($company, $this->member($company, $memberUuid), $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('company.members.index', $company)->with('success', 'Owner promoted.');
    }

    public function confirmDemotion(DemoteOwnerRequest $request, Company $company, string $memberUuid): View
    {
        $member = $this->member($company, $memberUuid);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.ownership.demote', compact('company', 'companies', 'member'));
    }

    public function destroy(DemoteOwnerRequest $request, Company $company, string $memberUuid, DemoteCompanyOwner $action): RedirectResponse
    {
        try {
            $action->handle($company, $this->member($company, $memberUuid), $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('company.members.index', $company)->with('success', 'Owner demoted.');
    }

    private function member(Company $company, string $uuid): User
    {
        return $company->users()->where('users.uuid', $uuid)->firstOrFail();
    }
}
