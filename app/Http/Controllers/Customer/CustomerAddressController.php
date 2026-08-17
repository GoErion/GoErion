<?php
declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerAddressRequest;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;

final class CustomerAddressController extends Controller
{
    public function create(Customer $customer)
    {
        return view('customer.address.create',compact('customer'));
    }

    public function store(CustomerAddressRequest $request,Customer $customer,CreateCustomerAddress $action)
    {
        $address = $action->handle($request->validated(),$customer);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
