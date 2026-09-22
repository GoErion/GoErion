<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Enums\CustomerPermission;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerCommercialTerms
{
    /** @param array{credit_limit: string, payment_terms_days: int} $values */
    public function handle(Company $company, Customer $customer, User $actor, array $values): Customer
    {
        return DB::transaction(function () use ($company, $customer, $actor, $values): Customer {
            $customer = $company->customers()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            if (! $actor->hasCompanyPermission($company, CustomerPermission::CreditManage->value)) {
                throw new AuthorizationException;
            }
            $customer->update([
                'credit_limit' => $values['credit_limit'],
                'payment_terms_days' => $values['payment_terms_days'],
            ]);

            return $customer;
        }, 2);
    }
}
