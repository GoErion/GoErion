<?php
declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerGroupRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class CustomerGroupController extends Controller
{
    public function store(CustomerGroupRequest $request, Company $company,CreateCustomerGroup $action)
    {
        $group = $action->handle($request->validated(),$company,);
        return redirect()->route('home', $company)->with('success', 'Customer group created successfully');
    }
}
