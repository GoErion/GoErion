<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use Illuminate\Support\Facades\DB;

final readonly class SetDefaultCustomerAddress
{
    public function handle(Company $company, Customer $customer, CustomerAddress $address): void
    {
        DB::transaction(function () use ($company, $customer, $address): void {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $address = $customer->addresses()->whereKey($address->getKey())->firstOrFail();
            $customer->addresses()->where('type', $address->type->value)->where('is_default', true)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }, 2);
    }
}
