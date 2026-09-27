<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\CreateSupplierAddress;
use App\Actions\Supplier\RemoveSupplierAddress;
use App\Actions\Supplier\SetDefaultSupplierAddress;
use App\Actions\Supplier\UpdateSupplierAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierAddressRequest;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class SupplierAddressController extends Controller
{
    public function create(Request $request, Company $company, string $supplier): View
    {
        $supplier = $this->supplier($company, $supplier);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.address.create', compact('company', 'companies', 'supplier'));
    }

    public function store(SupplierAddressRequest $request, Company $company, string $supplier, CreateSupplierAddress $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Address added.');
    }

    public function edit(Request $request, Company $company, string $supplier, string $address): View
    {
        $supplier = $this->supplier($company, $supplier);
        $address = $this->address($supplier, $address);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.address.edit', compact('company', 'companies', 'supplier', 'address'));
    }

    public function update(SupplierAddressRequest $request, Company $company, string $supplier, string $address, UpdateSupplierAddress $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $this->address($supplier, $address), $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Address updated.');
    }

    public function destroy(Request $request, Company $company, string $supplier, string $address, RemoveSupplierAddress $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $this->address($supplier, $address), $request->user());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Address removed.');
    }

    public function default(Request $request, Company $company, string $supplier, string $address, SetDefaultSupplierAddress $action): RedirectResponse
    {
        $supplier = $this->supplier($company, $supplier);
        $action->handle($company, $supplier, $this->address($supplier, $address), $request->user());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Default address updated.');
    }

    private function supplier(Company $company, string $uuid): Supplier
    {
        Gate::authorize('update', [Supplier::class, $company]);

        return $company->suppliers()->where('uuid', $uuid)->firstOrFail();
    }

    private function address(Supplier $supplier, string $uuid): SupplierAddress
    {
        return $supplier->addresses()->where('uuid', $uuid)->firstOrFail();
    }
}
