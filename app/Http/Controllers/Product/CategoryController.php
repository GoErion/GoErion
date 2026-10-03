<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\ArchiveCategory;
use App\Actions\Product\CreateCategory;
use App\Actions\Product\RestoreCategory;
use App\Actions\Product\UpdateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CategoryRequest;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Models\Company\Company;
use App\Services\Product\ClassificationInput;
use App\Services\Product\ProductInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function index(ProductAccessRequest $request, Company $company): View
    {
        $status = $request->validated('status', 'active');
        $categories = $company->categories()->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))->orderBy('name')->orderBy('id')->paginate(20)->appends(['status' => $status]);
        $paths = ClassificationInput::paths($company);

        return view('product.category.index', compact('company', 'categories', 'status', 'paths'));
    }

    public function create(ProductAccessRequest $request, Company $company): View
    {
        $categories = $company->categories()->where('is_active', true)->orderBy('name')->get();
        $paths = ClassificationInput::paths($company);
        $form = ProductInput::displayValues($request->old());

        return view('product.category.create', compact('company', 'form', 'categories', 'paths'));
    }

    public function store(CategoryRequest $request, Company $company, CreateCategory $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('category.index', $company)->with('success', 'Category created successfully.');
    }

    public function edit(ProductAccessRequest $request, Company $company, string $category): View
    {
        $category = $company->categories()->where('uuid', $category)->firstOrFail();
        $categories = $company->categories()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $category->parent_id))
            ->orderBy('name')->get();
        $paths = ClassificationInput::paths($company);
        $old = $request->old();
        $form = ProductInput::displayValues(array_replace($category->only(['name', 'code', 'parent_id']), $old));
        if (array_key_exists('parent_id', $old)) {
            $parent = $old['parent_id'];
            $isRoot = $parent === null || $parent === '';
            $isOption = (is_int($parent) || is_string($parent))
                && $categories->contains(fn ($option) => (string) $option->id === (string) $parent);
            if (! $isRoot && ! $isOption) {
                $form['parent_id'] = (string) ($category->parent_id ?? '');
            }
        }

        return view('product.category.edit', compact('company', 'category', 'form', 'categories', 'paths'));
    }

    public function update(CategoryRequest $request, Company $company, string $category, UpdateCategory $action): RedirectResponse
    {
        $category = $company->categories()->where('uuid', $category)->firstOrFail();
        $action->handle($company, $category, $request->user(), $request->validated());

        return redirect()->route('category.index', $company)->with('success', 'Category updated.');
    }

    public function archive(ProductAccessRequest $request, Company $company, string $category, ArchiveCategory $action): RedirectResponse
    {
        $category = $company->categories()->where('uuid', $category)->firstOrFail();
        $action->handle($company, $category, $request->user());

        return redirect()->route('category.index', $company)->with('success', 'Category archived.');
    }

    public function restore(ProductAccessRequest $request, Company $company, string $category, RestoreCategory $action): RedirectResponse
    {
        $category = $company->categories()->where('uuid', $category)->firstOrFail();
        $action->handle($company, $category, $request->user());

        return redirect()->route('category.index', $company)->with('success', 'Category restored.');
    }
}
