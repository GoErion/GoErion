<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateBrand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BrandRequest;
use App\Models\Company\Company;
use App\Models\Product\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\View\View;

#[Middleware('auth')]
final class BrandController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        $this->ensureCompanyMember($request, $company);

        $brands = $company->brands()->latest()->paginate(20);

        return view('product.brand.index', compact('company', 'brands'));
    }

    public function create(Request $request, Company $company): View
    {
        $this->ensureCompanyMember($request, $company);

        return view('product.brand.create', compact('company'));
    }

    public function store(BrandRequest $request, Company $company, CreateBrand $action): RedirectResponse
    {
        $action->handle($request->validated(), $company);

        return redirect()->route('brand.index', $company)->with('success', 'Brand created successfully.');
    }

    public function edit(Request $request, Company $company, Brand $brand): View
    {
        $this->ensureCompanyMember($request, $company);

        return view('product.brand.edit', compact('company', 'brand'));
    }

    public function update(BrandRequest $request, Company $company, Brand $brand): RedirectResponse
    {
        $brand->update([
            'name' => strtolower($request->validated('name')),
            'code' => strtoupper($request->validated('code')),
        ]);

        return redirect()->route('brand.index', $company)->with('success', 'Brand updated successfully.');
    }

    public function destroy(Request $request, Company $company, Brand $brand): RedirectResponse
    {
        $this->ensureCompanyMember($request, $company);

        $brand->delete();

        return redirect()->route('brand.index', $company)->with('success', 'Brand deleted successfully.');
    }

    private function ensureCompanyMember(Request $request, Company $company): void
    {
        abort_unless($request->user()->companies()->whereKey($company->getKey())->exists(), 403);
    }
}
