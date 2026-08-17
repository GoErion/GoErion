<?php
declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class CustomerController extends Controller
{
    public function create(Company $company)
    {
        return view('customer.create', compact('company'));
    }
    public function store(CustomerRequest $request,Company $company,CreateCustomer $action)
    {
        $customer = $action->handle($request->validated(),$company);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
