<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomer;
use App\Actions\Customer\SetCustomerActive;
use App\Actions\Customer\UpdateCustomer;
use App\Actions\Customer\UpdateCustomerCommercialTerms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CommercialTermsRequest;
use App\Http\Requests\Customer\CustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CustomerController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        Gate::authorize('viewAny', [Customer::class, $company]);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,archived,all'],
            'group' => ['nullable', 'uuid'],
        ]);
        $group = isset($filters['group']) ? $company->customerGroups()->where('uuid', $filters['group'])->firstOrFail() : null;
        $customers = $company->customers()->with('group')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))
            ->when($filters['status'] ?? 'active', function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('is_active', $status === 'active');
                }
            })
            ->when($group, fn ($query) => $query->whereHas('groupMembership', fn ($query) => $query->where('customer_group_id', $group->id)))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        $groups = $company->customerGroups()->orderBy('name')->get();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.index', compact('company', 'companies', 'customers', 'groups', 'filters'));
    }

    public function create(Request $request, Company $company): View
    {
        Gate::authorize('create', [Customer::class, $company]);
        $groups = $company->customerGroups()->where('is_active', true)->orderBy('name')->get();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.create', compact('company', 'companies', 'groups'));
    }

    public function store(CustomerRequest $request, Company $company, CreateCustomer $action): RedirectResponse
    {
        $customer = $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Customer created.');
    }

    public function show(Request $request, Company $company, string $customer): View
    {
        Gate::authorize('viewAny', [Customer::class, $company]);
        $customer = $this->customer($company, $customer)->load(['group', 'contacts', 'addresses']);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.show', compact('company', 'companies', 'customer'));
    }

    public function edit(Request $request, Company $company, string $customer): View
    {
        Gate::authorize('update', [Customer::class, $company]);
        $customer = $this->customer($company, $customer)->load(['group', 'groupMembership']);
        $groups = $company->customerGroups()->where(fn ($query) => $query->where('is_active', true)
            ->when($customer->groupMembership, fn ($query) => $query->orWhereKey($customer->groupMembership->customer_group_id)))
            ->orderBy('name')->get();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.edit', compact('company', 'companies', 'customer', 'groups'));
    }

    public function update(UpdateCustomerRequest $request, Company $company, string $customer, UpdateCustomer $action): RedirectResponse
    {
        $customer = $action->handle($company, $this->customer($company, $customer), $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Customer updated.');
    }

    public function updateCommercialTerms(CommercialTermsRequest $request, Company $company, string $customer, UpdateCustomerCommercialTerms $action): RedirectResponse
    {
        $customer = $action->handle($company, $this->customer($company, $customer), $request->user(), $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Commercial terms updated.');
    }

    public function archive(Request $request, Company $company, string $customer, SetCustomerActive $action): RedirectResponse
    {
        Gate::authorize('archive', [Customer::class, $company]);
        $action->handle($company, $this->customer($company, $customer), false);

        return redirect()->route('company.customers.index', $company)->with('success', 'Customer archived.');
    }

    public function restore(Request $request, Company $company, string $customer, SetCustomerActive $action): RedirectResponse
    {
        Gate::authorize('archive', [Customer::class, $company]);
        $action->handle($company, $this->customer($company, $customer), true);

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Customer restored.');
    }

    private function customer(Company $company, string $uuid): Customer
    {
        Gate::authorize('view', $company);

        return $company->customers()->where('uuid', $uuid)->firstOrFail();
    }
}
