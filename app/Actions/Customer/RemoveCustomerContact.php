<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerContact;
use Illuminate\Support\Facades\DB;

final readonly class RemoveCustomerContact
{
    public function handle(Company $company, Customer $customer, CustomerContact $contact): void
    {
        DB::transaction(function () use ($company, $customer, $contact): void {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $contact = $customer->contacts()->whereKey($contact->getKey())->firstOrFail();
            $wasPrimary = $contact->is_primary;
            $contact->delete();

            if ($wasPrimary) {
                $customer->contacts()->orderBy('id')->first()?->update(['is_primary' => true]);
            }
        }, 2);
    }
}
