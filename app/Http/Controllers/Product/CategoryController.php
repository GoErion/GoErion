<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CategoryRequest;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function create(ProductAccessRequest $request, Company $company): View
    {
        $categories = $company->categories()->where('is_active', true)->orderBy('name')->get();

        return view('product.category.create', compact('company', 'categories'));
    }

    public function store(CategoryRequest $request, Company $company, CreateCategory $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('home', $company)->with('success', 'Category created successfully.');
    }
}
