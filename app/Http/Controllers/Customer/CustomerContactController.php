<?php
declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerContact;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerContactRequest;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;

final class CustomerContactController extends Controller
{
    public function create(Customer $customer)
    {
        return view('customer.contact.create', compact('customer'));
    } 

    public function store(CustomerContactRequest $request,Customer $customer,CreateCustomerContact $action)
    {
        $contacts = $action->handle($request->validated(),$customer);

        return redirect()->route('home')->with('success', 'Customer created successfully.');
    }
}
