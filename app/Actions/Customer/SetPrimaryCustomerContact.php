<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerContact;
use Illuminate\Support\Facades\DB;

final readonly class SetPrimaryCustomerContact
{
    public function handle(Company $company, Customer $customer, CustomerContact $contact): void
    {
        DB::transaction(function () use ($company, $customer, $contact): void {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $contact = $customer->contacts()->whereKey($contact->getKey())->firstOrFail();
            $customer->contacts()->where('is_primary', true)->update(['is_primary' => false]);
            $contact->update(['is_primary' => true]);
        }, 2);
    }
}
