<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class ProductController extends Controller
{
    public function index(Request $request, Company $company)
    {
        abort_unless($request->user()->companies()->whereKey($company->getKey())->exists(), 403);

        $products = $company->products()->latest()->paginate(20);

        return view('product.index', compact('company', 'products'));
    }

    public function create(Company $company)
    {
        $categories = $company->categories()
            ->orderBy('name')
            ->get();
        $brands = $company->brands()
            ->orderBy('name')
            ->get();
        $units = $company->units()
            ->orderBy('name')
            ->get();

        return view('product.create', compact('company', 'categories', 'brands', 'units'));
    }

    public function store(ProductRequest $request, Company $company, CreateProduct $action)
    {
        $product = $action->handle($request->validated(), $company);

        return redirect()->route('product.index', $company)->with('success', 'product created successfully.');
    }

    public function show(Request $request, Company $company, string $product)
    {
        abort_unless($request->user()->companies()->whereKey($company->getKey())->exists(), 403);
        $canViewSuppliers = $request->user()->can('viewAny', [Supplier::class, $company]);
        $product = $company->products()->with(['category', 'brand', 'unit', 'prices', 'barcodes'])->where('uuid', $product)->firstOrFail();
        if ($canViewSuppliers) {
            $product->load(['suppliers' => fn ($query) => $query->where('suppliers.company_id', $company->getKey())->where('product_suppliers.company_id', $company->getKey())]);
        }
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('product.show', compact('company', 'companies', 'product', 'canViewSuppliers'));
    }
}
