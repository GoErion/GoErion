<?php
declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CategoryRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    public function create(Company $company)
    {
        $categories = $company->categories()
            ->whereNull('parent_id')
            ->with('descendants')
            ->orderBy('name')->get();

        return view('product.category.create',compact('company','categories'));
    }
    public function store(CategoryRequest $request,Company $company,CreateCategory $action)
    {
        $category = $action->handle($request->validated(),$company);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
