<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use Illuminate\Support\Facades\DB;

final readonly class RemoveCustomerAddress
{
    public function handle(Company $company, Customer $customer, CustomerAddress $address): void
    {
        DB::transaction(function () use ($company, $customer, $address): void {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $address = $customer->addresses()->whereKey($address->getKey())->firstOrFail();
            $type = $address->type->value;
            $wasDefault = $address->is_default;
            $address->delete();
            if ($wasDefault) {
                $customer->addresses()->where('type', $type)->orderBy('id')->first()?->update(['is_default' => true]);
            }
        }, 2);
    }
}
