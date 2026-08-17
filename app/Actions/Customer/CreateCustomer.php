<?php
declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;

final readonly class CreateCustomer
{
    public function handle(array $input,Company $company)
    {
        return $company->customers()->create(
            [
                'name' => $input['name'],
                'code' => $input['code'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'credit_limit' => $input['credit_limit'] ?? 0,
                'payment_terms_days' => $input['payment_terms_days'] ?? 0,
                'is_active' => $input['is_active'] ?? true,
            ]);
    }
}
