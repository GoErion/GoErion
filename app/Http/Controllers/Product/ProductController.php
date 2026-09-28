<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Company\Company;
use App\Models\Product\Product;
use App\Models\Supplier\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function index(ProductAccessRequest $request, Company $company): View
    {
        $products = $company->products()->with(['category', 'unit', 'prices' => fn ($query) => $query->where('price_type', 'retail')->where('currency', strtoupper((string) $company->currency))])->latest()->orderByDesc('id')->paginate(20);

        return view('product.index', compact('company', 'products'));
    }

    public function create(ProductAccessRequest $request, Company $company): View
    {
        $categories = $company->categories()->where('is_active', true)->orderBy('name')->get();
        $brands = $company->brands()->where('is_active', true)->orderBy('name')->get();
        $units = $company->units()->where('is_active', true)->orderBy('name')->get();

        $priceRows = $this->displayRows($request->old('prices'), ['price_type', 'price', 'currency']);
        $barcodeRows = $this->displayRows($request->old('barcodes'), ['barcode', 'type']);

        return view('product.create', compact('company', 'categories', 'brands', 'units', 'priceRows', 'barcodeRows'));
    }

    public function store(ProductRequest $request, Company $company, CreateProduct $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('product.index', $company)->with('success', 'Product created successfully.');
    }

    public function show(ProductAccessRequest $request, Company $company, string $product): View
    {
        $canViewSuppliers = $request->user()->can('viewAny', [Supplier::class, $company]);
        $canViewCosts = $request->user()->can('viewCost', [Product::class, $company]);
        $product = $company->products()->with(['category', 'brand', 'unit', 'prices' => fn ($query) => $query->when(! $canViewCosts, fn ($query) => $query->where('price_type', '<>', 'cost')), 'barcodes'])->where('uuid', $product)->firstOrFail();
        if ($canViewSuppliers) {
            $product->load(['suppliers' => fn ($query) => $query->where('suppliers.company_id', $company->id)->where('product_suppliers.company_id', $company->id)]);
        }

        return view('product.show', compact('company', 'product', 'canViewSuppliers', 'canViewCosts'));
    }

    /**
     * @param  list<string>  $fields
     * @return list<array<string, string>>
     */
    private function displayRows(mixed $input, array $fields): array
    {
        if (! is_array($input)) {
            return [];
        }
        $rows = [];
        foreach (array_slice($input, 0, 100, true) as $sourceIndex => $inputRow) {
            if (! is_array($inputRow)) {
                continue;
            }
            $row = ['error_index' => (string) $sourceIndex];
            foreach ($fields as $field) {
                $value = $inputRow[$field] ?? null;
                $row[$field] = is_scalar($value) ? (string) $value : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
