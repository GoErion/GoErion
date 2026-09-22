<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use Illuminate\Support\Facades\DB;

final readonly class CreateCustomerAddress
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Customer $customer, array $values): CustomerAddress
    {
        return DB::transaction(function () use ($company, $customer, $values): CustomerAddress {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $type = $values['type'];
            $isDefault = ! $customer->addresses()->where('type', $type)->exists() || ($values['is_default'] ?? false);
            if ($isDefault) {
                $customer->addresses()->where('type', $type)->where('is_default', true)->update(['is_default' => false]);
            }

            return $customer->addresses()->create([
                'type' => $type,
                'line_1' => $values['line_1'],
                'line_2' => $values['line_2'] ?? null,
                'city' => $values['city'] ?? null,
                'postal_code' => $values['postal_code'] ?? null,
                'country' => $values['country'] ?? null,
                'is_default' => $isDefault,
            ]);
        }, 2);
    }
}
