<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateWarehouse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\WarehouseRequest;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

#[Middleware('auth')]
final class WarehouseController extends Controller
{
    public function create(Company $company): View
    {
        Gate::authorize('view', $company);

        $branches = $company->branches()->where('is_active', true)->get();

        return view('company.warehouse.create', compact('company', 'branches'));
    }

    public function store(WarehouseRequest $request, CreateWarehouse $action, Company $company): RedirectResponse
    {
        $action->handle($request->validated(), $company);

        return redirect()->route('home', $company)->with('success', 'Warehouse created successfully.');
    }
}
