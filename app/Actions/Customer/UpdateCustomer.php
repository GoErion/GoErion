<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateCustomer
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Customer $customer, array $values): Customer
    {
        return DB::transaction(function () use ($company, $customer, $values): Customer {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $currentGroupId = $customer->groupMembership()->value('customer_group_id');
            $groupAssignmentSubmitted = array_key_exists('customer_group_id', $values);
            $groupId = $groupAssignmentSubmitted ? $values['customer_group_id'] : $currentGroupId;
            if ($groupId !== null && (int) $groupId !== (int) $currentGroupId
                && ! $company->customerGroups()->whereKey($groupId)->where('is_active', true)->exists()) {
                throw new \DomainException('Select an active group from this company.');
            }

            $customer->update([
                'name' => trim($values['name']),
                'email' => isset($values['email']) ? Str::lower(trim($values['email'])) : null,
                'phone' => $values['phone'] ?? null,
                'tax_pin' => $values['tax_pin'] ?? null,
            ]);

            if ($groupAssignmentSubmitted) {
                if ($groupId === null) {
                    $customer->groupMembership()->delete();
                } else {
                    $customer->groupMembership()->updateOrCreate(
                        ['customer_id' => $customer->getKey()],
                        ['customer_group_id' => $groupId, 'company_id' => $company->getKey()],
                    );
                }
            }

            return $customer->refresh();
        }, 2);
    }
}
