<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerAddress
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Customer $customer, CustomerAddress $address, array $values): CustomerAddress
    {
        return DB::transaction(function () use ($company, $customer, $address, $values): CustomerAddress {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $address = $customer->addresses()->whereKey($address->getKey())->firstOrFail();
            $oldType = $address->type->value;
            $newType = $values['type'];
            $wasDefault = $address->is_default;
            $newTypeHasDefault = $customer->addresses()->where('type', $newType)->where('is_default', true)
                ->whereKeyNot($address->getKey())->exists();
            $makeDefault = $oldType === $newType
                ? ($values['is_default'] ?? false) || $wasDefault
                : ($values['is_default'] ?? false) || ! $newTypeHasDefault;

            if ($makeDefault) {
                $customer->addresses()->where('type', $newType)->where('is_default', true)
                    ->whereKeyNot($address->getKey())->update(['is_default' => false]);
            }

            $address->update([
                'type' => $newType,
                'line_1' => $values['line_1'],
                'line_2' => $values['line_2'] ?? null,
                'city' => $values['city'] ?? null,
                'postal_code' => $values['postal_code'] ?? null,
                'country' => $values['country'] ?? null,
                'is_default' => $makeDefault,
            ]);

            if ($oldType !== $newType && $wasDefault) {
                $customer->addresses()->where('type', $oldType)->orderBy('id')->first()?->update(['is_default' => true]);
            }

            return $address;
        }, 2);
    }
}
