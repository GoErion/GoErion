<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\ArchiveBrand;
use App\Actions\Product\CreateBrand;
use App\Actions\Product\RestoreBrand;
use App\Actions\Product\UpdateBrand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BrandRequest;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Models\Company\Company;
use App\Services\Product\ProductInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class BrandController extends Controller
{
    public function index(ProductAccessRequest $request, Company $company): View
    {
        $status = $request->validated('status', 'active');
        $brands = $company->brands()->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))->latest()->orderByDesc('id')->paginate(20)->appends(['status' => $status]);

        return view('product.brand.index', compact('company', 'brands', 'status'));
    }

    public function create(ProductAccessRequest $request, Company $company): View
    {
        $form = ProductInput::displayValues($request->old());

        return view('product.brand.create', compact('company', 'form'));
    }

    public function store(BrandRequest $request, Company $company, CreateBrand $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('brand.index', $company)->with('success', 'Brand created successfully.');
    }

    public function edit(ProductAccessRequest $request, Company $company, string $brand): View
    {
        $brand = $company->brands()->where('uuid', $brand)->firstOrFail();

        $form = ProductInput::displayValues(array_replace($brand->only(['name', 'code']), $request->old()));

        return view('product.brand.edit', compact('company', 'brand', 'form'));
    }

    public function update(BrandRequest $request, Company $company, string $brand, UpdateBrand $action): RedirectResponse
    {
        $brand = $company->brands()->where('uuid', $brand)->firstOrFail();
        $action->handle($company, $brand, $request->user(), $request->validated());

        return redirect()->route('brand.index', $company)->with('success', 'Brand updated successfully.');
    }

    public function archive(ProductAccessRequest $request, Company $company, string $brand, ArchiveBrand $action): RedirectResponse
    {
        $brand = $company->brands()->where('uuid', $brand)->firstOrFail();
        $action->handle($company, $brand, $request->user());

        return redirect()->route('brand.index', $company)->with('success', 'Brand archived successfully.');
    }

    public function restore(ProductAccessRequest $request, Company $company, string $brand, RestoreBrand $action): RedirectResponse
    {
        $brand = $company->brands()->where('uuid', $brand)->firstOrFail();
        $action->handle($company, $brand, $request->user());

        return redirect()->route('brand.index', $company)->with('success', 'Brand restored.');
    }
}
