<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateWarehouseLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\WarehouseLocationRequest;
use App\Models\Company\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

#[Middleware('auth')]
final class WarehouseLocationController extends Controller
{
    public function create(Warehouse $warehouse): View
    {
        Gate::authorize('view', $warehouse->branch->company);

        return view('company.warehouse-location.create', compact('warehouse'));
    }

    public function store(WarehouseLocationRequest $request, Warehouse $warehouse, CreateWarehouseLocation $action): RedirectResponse
    {
        $action->handle($request->validated(), $warehouse);

        return redirect()->route('home', $warehouse->branch->company)
            ->with('success', 'Warehouse location created successfully.');
    }
}
