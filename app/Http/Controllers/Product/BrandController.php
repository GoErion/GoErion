<?php
declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateBrand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BrandRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class BrandController extends Controller
{
    public function create(Company $company)
    {
        return view('product.brand.create', compact('company'));
    }
    public function store(BrandRequest $request,Company $company,CreateBrand $action)
    {
        $brand = $action->handle($request->validated(),$company,);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
