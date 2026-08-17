<?php
declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Customer\Customer;

final readonly class CreateCustomerContact
{
    public function handle(array $input, Customer $customer)
    {
        return $customer->contacts()->create(
            [
                'name' => $input['name'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'position' => $input['position'] ?? null,
            ]);
    }
}
