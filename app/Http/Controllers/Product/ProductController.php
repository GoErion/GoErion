<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\ArchiveProduct;
use App\Actions\Product\CreateProduct;
use App\Actions\Product\RestoreProduct;
use App\Actions\Product\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Requests\Product\ProductLifecycleRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Company\Company;
use App\Models\Product\Product;
use App\Models\Supplier\Supplier;
use App\Services\Product\ProductInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function index(ProductIndexRequest $request, Company $company): View
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $filters += ['status' => 'active', 'sort' => 'created_at', 'direction' => 'desc'];
        $query = $company->products();
        $catalogEmpty = ! $company->products()->exists();
        if ($filters['status'] !== 'all') {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if (isset($filters['q'])) {
            $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']).'%';
            $query->where(function ($query) use ($search, $company): void {
                $query->whereRaw("products.sku LIKE ? ESCAPE '!'", [$search])
                    ->orWhereRaw("products.name LIKE ? ESCAPE '!'", [$search])
                    ->orWhereHas('barcodes', fn ($query) => $query->where('product_barcodes.company_id', $company->id)->whereRaw("barcode LIKE ? ESCAPE '!'", [$search]));
            });
        }
        foreach (['category' => 'categories', 'brand' => 'brands', 'unit' => 'units'] as $key => $relation) {
            if (isset($filters[$key])) {
                $id = $company->{$relation}()->where('uuid', $filters[$key])->firstOrFail()->id;
                $query->where($key.'_id', $id);
            }
        }
        if (isset($filters['supplier'])) {
            $supplier = $company->suppliers()->where('uuid', $filters['supplier'])->firstOrFail();
            $query->whereHas('suppliers', fn ($query) => $query->where('suppliers.id', $supplier->id)->where('suppliers.company_id', $company->id)->where('product_suppliers.company_id', $company->id));
        }
        $products = $query->with(['category', 'unit', 'brand', 'prices' => fn ($query) => $query->where('company_id', $company->id)->where('price_type', 'retail')->where('currency', strtoupper((string) $company->currency))])
            ->orderBy($filters['sort'], $filters['direction'])->orderBy('products.id', $filters['direction'])->paginate(20)->appends($filters);
        $categories = $company->categories()->orderBy('name')->get();
        $brands = $company->brands()->orderBy('name')->get();
        $units = $company->units()->orderBy('name')->get();
        $canViewSuppliers = $request->user()->can('viewAny', [Supplier::class, $company]);
        $suppliers = $canViewSuppliers ? $company->suppliers()->orderBy('name')->get() : collect();
        $canUpdate = $request->user()->can('update', [Product::class, $company]);
        $canArchive = $request->user()->can('archive', [Product::class, $company]);

        return view('product.index', compact('company', 'products', 'filters', 'catalogEmpty', 'categories', 'brands', 'units', 'suppliers', 'canViewSuppliers', 'canUpdate', 'canArchive'));
    }

    public function create(ProductAccessRequest $request, Company $company): View
    {
        $categories = $company->categories()->where('is_active', true)->orderBy('name')->get();
        $brands = $company->brands()->where('is_active', true)->orderBy('name')->get();
        $units = $company->units()->where('is_active', true)->orderBy('name')->get();

        $priceRows = $this->displayRows($request->old('prices'), ['price_type', 'price', 'currency']);
        $barcodeRows = $this->displayRows($request->old('barcodes'), ['barcode', 'type']);

        $form = ProductInput::displayValues($request->old());

        return view('product.create', compact('company', 'categories', 'brands', 'units', 'priceRows', 'barcodeRows', 'form'));
    }

    public function store(ProductRequest $request, Company $company, CreateProduct $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('product.index', $company)->with('success', 'Product created successfully.');
    }

    public function edit(ProductAccessRequest $request, Company $company, string $product): View
    {
        $product = $company->products()->where('uuid', $product)->firstOrFail();
        $categories = $company->categories()->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $product->category_id))->orderBy('name')->get();
        $brands = $company->brands()->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $product->brand_id))->orderBy('name')->get();
        $units = $company->units()->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $product->unit_id))->orderBy('name')->get();
        $form = ProductInput::displayValues(array_replace($product->only(['name', 'description', 'category_id', 'brand_id', 'unit_id']), $request->old()));

        return view('product.edit', compact('company', 'product', 'categories', 'brands', 'units', 'form'));
    }

    public function update(UpdateProductRequest $request, Company $company, string $product, UpdateProduct $action): RedirectResponse
    {
        $product = $company->products()->where('uuid', $product)->firstOrFail();
        $action->handle($company, $product, $request->user(), $request->validated());

        return redirect()->route('product.show', [$company, $product])->with('success', 'Product updated.');
    }

    public function archive(ProductLifecycleRequest $request, Company $company, string $product, ArchiveProduct $action): RedirectResponse
    {
        $product = $company->products()->where('uuid', $product)->firstOrFail();
        $action->handle($company, $product, $request->user());

        return redirect()->route('product.show', [$company, $product])->with('success', 'Product archived.');
    }

    public function restore(ProductLifecycleRequest $request, Company $company, string $product, RestoreProduct $action): RedirectResponse
    {
        $product = $company->products()->where('uuid', $product)->firstOrFail();
        $action->handle($company, $product, $request->user());

        return redirect()->route('product.show', [$company, $product])->with('success', 'Product restored.');
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
