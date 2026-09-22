<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateCustomerContact
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Customer $customer, CustomerContact $contact, array $values): CustomerContact
    {
        return DB::transaction(function () use ($company, $customer, $contact, $values): CustomerContact {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $contact = $customer->contacts()->whereKey($contact->getKey())->firstOrFail();
            $contact->update([
                'name' => trim($values['name']),
                'email' => isset($values['email']) ? Str::lower(trim($values['email'])) : null,
                'phone' => $values['phone'] ?? null,
                'position' => $values['position'] ?? null,
            ]);

            return $contact;
        }, 2);
    }
}
