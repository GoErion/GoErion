<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\CreateSupplierContact;
use App\Actions\Supplier\RemoveSupplierContact;
use App\Actions\Supplier\SetPrimarySupplierContact;
use App\Actions\Supplier\UpdateSupplierContact;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierContactRequest;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class SupplierContactController extends Controller
{
    public function create(Request $request, Company $company, string $supplier): View
    {
        $supplier = $this->supplier($company, $supplier);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.contact.create', compact('company', 'companies', 'supplier'));
    }

    public function store(SupplierContactRequest $request, Company $company, string $supplier, CreateSupplierContact $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Contact added.');
    }

    public function edit(Request $request, Company $company, string $supplier, string $contact): View
    {
        $supplier = $this->supplier($company, $supplier);
        $contact = $this->contact($supplier, $contact);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.contact.edit', compact('company', 'companies', 'supplier', 'contact'));
    }

    public function update(SupplierContactRequest $request, Company $company, string $supplier, string $contact, UpdateSupplierContact $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $this->contact($supplier, $contact), $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Contact updated.');
    }

    public function destroy(Request $request, Company $company, string $supplier, string $contact, RemoveSupplierContact $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $this->contact($supplier, $contact), $request->user());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Contact removed.');
    }

    public function primary(Request $request, Company $company, string $supplier, string $contact, SetPrimarySupplierContact $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $this->contact($supplier, $contact), $request->user());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Primary contact updated.');
    }

    private function supplier(Company $company, string $uuid): Supplier
    {
        Gate::authorize('update', [Supplier::class, $company]);

        return $company->suppliers()->where('uuid', $uuid)->firstOrFail();
    }

    private function contact(Supplier $supplier, string $uuid): SupplierContact
    {
        return $supplier->contacts()->where('uuid', $uuid)->firstOrFail();
    }
}
