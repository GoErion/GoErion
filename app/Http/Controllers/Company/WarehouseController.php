<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateWarehouse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\WarehouseRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class WarehouseController extends Controller
{
    public function create(Company $company)
    {
        $branches = $company->branches()->where('is_active',true)->get();

        return view('company.warehouse.create',compact('company','branches'));
    }
    public function store(WarehouseRequest $request,CreateWarehouse $action,Company $company)
    {
        $warehouse = $action->handle($request->validated(),$company);
        
        return redirect()->route('home')->with(['success','you have successful create Warehouse']);
    }
}
