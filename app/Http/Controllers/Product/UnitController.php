<?php
declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\UnitRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class UnitController extends Controller
{
    public function index()
    {
        
    }
    public function create(Company $company)
    {
        return view('product.unit.create', compact('company'));
    }

    public function store(UnitRequest $request,Company $company,CreateUnit $action)
    {
        $action->handle(
            $company,
            $request->validated()
        );

        return redirect()->route('home',$company)->with('success', 'Unit created successfully.');
    }
}
