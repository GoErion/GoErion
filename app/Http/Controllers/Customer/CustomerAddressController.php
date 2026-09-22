<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerAddress;
use App\Actions\Customer\RemoveCustomerAddress;
use App\Actions\Customer\SetDefaultCustomerAddress;
use App\Actions\Customer\UpdateCustomerAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerAddressRequest;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CustomerAddressController extends Controller
{
    public function create(Request $request, Company $company, string $customer): View
    {
        $customer = $this->customer($company, $customer);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.address.create', compact('company', 'companies', 'customer'));
    }

    public function store(CustomerAddressRequest $request, Company $company, string $customer, CreateCustomerAddress $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Address added.');
    }

    public function edit(Request $request, Company $company, string $customer, string $address): View
    {
        $customer = $this->customer($company, $customer);
        $address = $this->address($customer, $address);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.address.edit', compact('company', 'companies', 'customer', 'address'));
    }

    public function update(CustomerAddressRequest $request, Company $company, string $customer, string $address, UpdateCustomerAddress $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $this->address($customer, $address), $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Address updated.');
    }

    public function destroy(Request $request, Company $company, string $customer, string $address, RemoveCustomerAddress $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $this->address($customer, $address));

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Address removed.');
    }

    public function default(Request $request, Company $company, string $customer, string $address, SetDefaultCustomerAddress $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $this->address($customer, $address));

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Default address updated.');
    }

    private function customer(Company $company, string $uuid): Customer
    {
        Gate::authorize('update', [Customer::class, $company]);

        return $company->customers()->where('uuid', $uuid)->firstOrFail();
    }

    private function address(Customer $customer, string $uuid): CustomerAddress
    {
        return $customer->addresses()->where('uuid', $uuid)->firstOrFail();
    }
}
