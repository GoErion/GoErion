<?php
declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Company\Company;

final readonly class CreateSupplier
{
    public function handle(array $input,Company $company)
    {
        return $company->suppliers()->create(
            [
                'name' => $input['name'],
                'code' => $input['code'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'payment_terms_days' => $input['payment_terms_days'] ?? 0,
                'is_active' => $input['is_active'] ?? true,
            ]);
    }
}
