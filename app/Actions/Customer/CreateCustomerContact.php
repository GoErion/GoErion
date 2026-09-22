<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateCustomerContact
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Customer $customer, array $values): CustomerContact
    {
        return DB::transaction(function () use ($company, $customer, $values): CustomerContact {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();

            return $customer->contacts()->create([
                'name' => trim($values['name']),
                'email' => isset($values['email']) ? Str::lower(trim($values['email'])) : null,
                'phone' => $values['phone'] ?? null,
                'position' => $values['position'] ?? null,
                'is_primary' => ! $customer->contacts()->exists(),
            ]);
        }, 2);
    }
}
