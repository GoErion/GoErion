<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\CreateUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Http\Requests\Product\UnitRequest;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class UnitController extends Controller
{
    public function index(ProductAccessRequest $request, Company $company): View
    {
        $units = $company->units()->orderBy('name')->paginate(20);

        return view('product.unit.index', compact('company', 'units'));
    }

    public function create(ProductAccessRequest $request, Company $company): View
    {
        return view('product.unit.create', compact('company'));
    }

    public function store(UnitRequest $request, Company $company, CreateUnit $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('unit.index', $company)->with('success', 'Unit created successfully.');
    }
}
