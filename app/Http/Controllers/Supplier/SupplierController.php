<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\CreateSupplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierRequest;
use App\Models\Company\Company;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class SupplierController extends Controller
{
    public function create(Company $company)
    {
        return view('supplier.create',compact('company'));
    }

    public function store(SupplierRequest $request,Company $company,CreateSupplier $createSupplier)
    {
        $supplier = $createSupplier->handle($request->validated(),$company);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
