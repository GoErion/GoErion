<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Enums\CustomerPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Services\Company\NumberSequenceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateCustomer
{
    public function __construct(private NumberSequenceService $numbers) {}

    /** @param array<string, mixed> $values */
    public function handle(Company $company, User $actor, array $values): Customer
    {
        return DB::transaction(function () use ($company, $actor, $values): Customer {
            $company = Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();
            $hasCommercialValues = ($values['credit_limit'] ?? '0') != 0 || ($values['payment_terms_days'] ?? 0) != 0;
            if ($hasCommercialValues && ! $actor->hasCompanyPermission($company, CustomerPermission::CreditManage->value)) {
                throw new AuthorizationException;
            }

            $groupId = $values['customer_group_id'] ?? null;
            if ($groupId !== null && ! $company->customerGroups()->whereKey($groupId)->where('is_active', true)->exists()) {
                throw new \DomainException('Select an active group from this company.');
            }

            $sequence = $company->numberSequences()->firstOrCreate(
                ['type' => 'customer'], ['prefix' => 'CUS', 'next_number' => 1],
            );
            do {
                $code = $this->numbers->next($sequence);
            } while ($company->customers()->where('code', $code)->exists());

            $customer = $company->customers()->create([
                'name' => trim($values['name']),
                'code' => $code,
                'email' => isset($values['email']) ? Str::lower(trim($values['email'])) : null,
                'phone' => $values['phone'] ?? null,
                'tax_pin' => $values['tax_pin'] ?? null,
                'credit_limit' => $values['credit_limit'] ?? '0',
                'payment_terms_days' => $values['payment_terms_days'] ?? 0,
                'is_active' => true,
            ]);

            if ($groupId !== null) {
                $customer->groupMembership()->create([
                    'customer_group_id' => $groupId, 'company_id' => $company->getKey(),
                ]);
            }

            return $customer;
        }, 2);
    }
}
