<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerContact;
use App\Actions\Customer\RemoveCustomerContact;
use App\Actions\Customer\SetPrimaryCustomerContact;
use App\Actions\Customer\UpdateCustomerContact;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerContactRequest;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CustomerContactController extends Controller
{
    public function create(Request $request, Company $company, string $customer): View
    {
        $customer = $this->customer($company, $customer);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.contact.create', compact('company', 'companies', 'customer'));
    }

    public function store(CustomerContactRequest $request, Company $company, string $customer, CreateCustomerContact $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Contact added.');
    }

    public function edit(Request $request, Company $company, string $customer, string $contact): View
    {
        $customer = $this->customer($company, $customer);
        $contact = $this->contact($customer, $contact);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('customer.contact.edit', compact('company', 'companies', 'customer', 'contact'));
    }

    public function update(CustomerContactRequest $request, Company $company, string $customer, string $contact, UpdateCustomerContact $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $this->contact($customer, $contact), $request->validated());

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Contact updated.');
    }

    public function destroy(Request $request, Company $company, string $customer, string $contact, RemoveCustomerContact $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $this->contact($customer, $contact));

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Contact removed.');
    }

    public function primary(Request $request, Company $company, string $customer, string $contact, SetPrimaryCustomerContact $action): RedirectResponse
    {
        $customer = $this->customer($company, $customer);
        $action->handle($company, $customer, $this->contact($customer, $contact));

        return redirect()->route('company.customers.show', [$company, $customer])->with('success', 'Primary contact updated.');
    }

    private function customer(Company $company, string $uuid): Customer
    {
        Gate::authorize('update', [Customer::class, $company]);

        return $company->customers()->where('uuid', $uuid)->firstOrFail();
    }

    private function contact(Customer $customer, string $uuid): CustomerContact
    {
        return $customer->contacts()->where('uuid', $uuid)->firstOrFail();
    }
}
