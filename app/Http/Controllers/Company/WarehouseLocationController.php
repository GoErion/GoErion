<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateWarehouseLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\WarehouseLocationRequest;
use App\Models\Company\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class WarehouseLocationController extends Controller
{
    public function create(Warehouse $warehouse)
    {
        return view('company.warehouse-location.create',compact('warehouse'));
    }
    public function store(WarehouseLocationRequest $request,Warehouse $warehouse,CreateWarehouseLocation $action)
    {
        $location = $action->handle($request->validated(),$warehouse);

        return redirect()->route('home');
            // ->route('warehouse-location.show', $location)
            // ->with('success', 'Warehouse location created successfully.');
    }
}
