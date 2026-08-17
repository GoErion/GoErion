<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\CreateSupplierAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierAddressRequest;
use App\Models\Supplier\Supplier;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class SupplierAddressController extends Controller
{
    public function create(Supplier $supplier)
    {
        return view('supplier.address.create',compact('supplier'));
    }

    public function store(SupplierAddressRequest $request,Supplier $supplier,CreateSupplierAddress $action)
    {
        $action->handle($request->validated(),$supplier);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
