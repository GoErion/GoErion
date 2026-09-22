<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Support\Facades\DB;

final readonly class SetCustomerActive
{
    public function handle(Company $company, Customer $customer, bool $active): Customer
    {
        return DB::transaction(function () use ($company, $customer, $active): Customer {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $customer->update(['is_active' => $active]);

            return $customer;
        }, 2);
    }
}
