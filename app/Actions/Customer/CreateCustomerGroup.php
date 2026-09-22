<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\CustomerGroup;
use Illuminate\Support\Facades\DB;

final readonly class CreateCustomerGroup
{
    /** @param array{name: string, code: string, description?: ?string} $input */
    public function handle(Company $company, array $input): CustomerGroup
    {
        return DB::transaction(function () use ($input, $company): CustomerGroup {
            return $company->customerGroups()->create(
                [
                    'name' => trim($input['name']),
                    'code' => strtoupper(trim($input['code'])),
                    'description' => $input['description'] ?? null,
                    'is_active' => true,
                ]);
        }, 2);
    }
}
