<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\ArchiveSupplier;
use App\Actions\Supplier\CreateSupplier;
use App\Actions\Supplier\RestoreSupplier;
use App\Actions\Supplier\UpdateSupplier;
use App\Actions\Supplier\UpdateSupplierCommercialTerms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\CommercialTermsRequest;
use App\Http\Requests\Supplier\SupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class SupplierController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        Gate::authorize('viewAny', [Supplier::class, $company]);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:active,archived,all']]);
        $suppliers = $company->suppliers()->with('primaryContact')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))
            ->when(($filters['status'] ?? 'active') !== 'all', fn ($query) => $query->where('is_active', ($filters['status'] ?? 'active') === 'active'))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.index', compact('company', 'companies', 'suppliers', 'filters'));
    }

    public function create(Request $request, Company $company): View
    {
        Gate::authorize('create', [Supplier::class, $company]);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.create', compact('company', 'companies'));
    }

    public function store(SupplierRequest $request, Company $company, CreateSupplier $action): RedirectResponse
    {
        $supplier = $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Supplier created.');
    }

    public function show(Request $request, Company $company, string $supplier): View
    {
        Gate::authorize('viewAny', [Supplier::class, $company]);
        $supplier = $this->supplier($company, $supplier)->load(['contacts' => fn ($query) => $query->orderBy('id'), 'addresses' => fn ($query) => $query->orderBy('id')]);
        $products = $supplier->products()->where('products.company_id', $company->getKey())->orderBy('products.name')->orderBy('products.id')->paginate(15, ['products.*'], 'products_page');
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.show', compact('company', 'companies', 'supplier', 'products'));
    }

    public function edit(Request $request, Company $company, string $supplier): View
    {
        Gate::authorize('update', [Supplier::class, $company]);
        $supplier = $this->supplier($company, $supplier);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('supplier.edit', compact('company', 'companies', 'supplier'));
    }

    public function update(UpdateSupplierRequest $request, Company $company, string $supplier, UpdateSupplier $action): RedirectResponse
    {
        $supplier = $action->handle($company, $this->supplier($company, $supplier), $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Supplier updated.');
    }

    public function updateCommercialTerms(CommercialTermsRequest $request, Company $company, string $supplier, UpdateSupplierCommercialTerms $action): RedirectResponse
    {
        $supplier = $action->handle($company, $this->supplier($company, $supplier), $request->user(), $request->validated());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Commercial terms updated.');
    }

    public function archive(Request $request, Company $company, string $supplier, ArchiveSupplier $action): RedirectResponse
    {
        Gate::authorize('archive', [Supplier::class, $company]);
        $supplier = $action->handle($company, $this->supplier($company, $supplier), $request->user());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Supplier archived.');
    }

    public function restore(Request $request, Company $company, string $supplier, RestoreSupplier $action): RedirectResponse
    {
        Gate::authorize('archive', [Supplier::class, $company]);
        $supplier = $action->handle($company, $this->supplier($company, $supplier), $request->user());

        return redirect()->route('company.suppliers.show', [$company, $supplier])->with('success', 'Supplier restored.');
    }

    private function supplier(Company $company, string $uuid): Supplier
    {
        Gate::authorize('view', $company);

        return $company->suppliers()->where('uuid', $uuid)->firstOrFail();
    }
}
