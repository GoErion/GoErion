<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\CreateSupplierContact;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierContactRequest;
use App\Models\Supplier\Supplier;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class SupplierContactController extends Controller
{
    public function create(Supplier $supplier)
    {
        return view('supplier.contact.create',compact('supplier'));
    }

    public function store(SupplierContactRequest $request,Supplier $supplier,CreateSupplierContact $action)
    {
        $action->handle($request->validated(),$supplier);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
