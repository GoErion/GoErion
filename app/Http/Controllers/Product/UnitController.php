<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Actions\Product\ArchiveUnit;
use App\Actions\Product\CreateUnit;
use App\Actions\Product\RestoreUnit;
use App\Actions\Product\UpdateUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductAccessRequest;
use App\Http\Requests\Product\UnitRequest;
use App\Models\Company\Company;
use App\Services\Product\ProductInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class UnitController extends Controller
{
    public function index(ProductAccessRequest $request, Company $company): View
    {
        $status = $request->validated('status', 'active');
        $units = $company->units()->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))->orderBy('name')->orderBy('id')->paginate(20)->appends(['status' => $status]);

        return view('product.unit.index', compact('company', 'units', 'status'));
    }

    public function create(ProductAccessRequest $request, Company $company): View
    {
        $form = ProductInput::displayValues($request->old());

        return view('product.unit.create', compact('company', 'form'));
    }

    public function store(UnitRequest $request, Company $company, CreateUnit $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated());

        return redirect()->route('unit.index', $company)->with('success', 'Unit created successfully.');
    }

    public function edit(ProductAccessRequest $request, Company $company, string $unit): View
    {
        $unit = $company->units()->where('uuid', $unit)->firstOrFail();
        $form = ProductInput::displayValues(array_replace($unit->only(['name', 'symbol', 'parent_id']), $request->old()));

        return view('product.unit.edit', compact('company', 'unit', 'form'));
    }

    public function update(UnitRequest $request, Company $company, string $unit, UpdateUnit $action): RedirectResponse
    {
        $unit = $company->units()->where('uuid', $unit)->firstOrFail();
        $action->handle($company, $unit, $request->user(), $request->validated());

        return redirect()->route('unit.index', $company)->with('success', 'Unit updated.');
    }

    public function archive(ProductAccessRequest $request, Company $company, string $unit, ArchiveUnit $action): RedirectResponse
    {
        $unit = $company->units()->where('uuid', $unit)->firstOrFail();
        $action->handle($company, $unit, $request->user());

        return redirect()->route('unit.index', $company)->with('success', 'Unit archived.');
    }

    public function restore(ProductAccessRequest $request, Company $company, string $unit, RestoreUnit $action): RedirectResponse
    {
        $unit = $company->units()->where('uuid', $unit)->firstOrFail();
        $action->handle($company, $unit, $request->user());

        return redirect()->route('unit.index', $company)->with('success', 'Unit restored.');
    }
}
