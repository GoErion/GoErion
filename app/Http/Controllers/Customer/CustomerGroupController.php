<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerGroup;
use App\Actions\Customer\SetCustomerGroupActive;
use App\Actions\Customer\UpdateCustomerGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerGroupRequest;
use App\Models\Company\Company;
use App\Models\Customer\CustomerGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CustomerGroupController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        Gate::authorize('manage', [CustomerGroup::class, $company]);
        $filters = $request->validate(['status' => ['nullable', 'in:active,archived,all']]);
        $groups = $company->customerGroups()->withCount('customers')
            ->when($filters['status'] ?? 'active', function ($query, $status): void {
                if ($status !== 'all') {
                    $query->where('is_active', $status === 'active');
                }
            })->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.groups.index', compact('company', 'companies', 'groups', 'filters'));
    }

    public function create(Request $request, Company $company): View
    {
        Gate::authorize('manage', [CustomerGroup::class, $company]);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.groups.create', compact('company', 'companies'));
    }

    public function store(CustomerGroupRequest $request, Company $company, CreateCustomerGroup $action): RedirectResponse
    {
        $action->handle($company, $request->validated());

        return redirect()->route('company.customer-groups.index', $company)->with('success', 'Customer group created.');
    }

    public function edit(Request $request, Company $company, string $group): View
    {
        Gate::authorize('manage', [CustomerGroup::class, $company]);
        $group = $this->group($company, $group);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.groups.edit', compact('company', 'companies', 'group'));
    }

    public function update(CustomerGroupRequest $request, Company $company, string $group, UpdateCustomerGroup $action): RedirectResponse
    {
        $action->handle($company, $this->group($company, $group), $request->validated());

        return redirect()->route('company.customer-groups.index', $company)->with('success', 'Customer group updated.');
    }

    public function archive(Request $request, Company $company, string $group, SetCustomerGroupActive $action): RedirectResponse
    {
        Gate::authorize('manage', [CustomerGroup::class, $company]);
        $action->handle($company, $this->group($company, $group), false);

        return redirect()->route('company.customer-groups.index', [$company, 'status' => 'all'])->with('success', 'Customer group archived.');
    }

    public function restore(Request $request, Company $company, string $group, SetCustomerGroupActive $action): RedirectResponse
    {
        Gate::authorize('manage', [CustomerGroup::class, $company]);
        $action->handle($company, $this->group($company, $group), true);

        return redirect()->route('company.customer-groups.index', $company)->with('success', 'Customer group restored.');
    }

    private function group(Company $company, string $uuid): CustomerGroup
    {
        Gate::authorize('view', $company);

        return $company->customerGroups()->where('uuid', $uuid)->firstOrFail();
    }
}
