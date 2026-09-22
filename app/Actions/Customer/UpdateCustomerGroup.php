<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\CustomerGroup;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerGroup
{
    /** @param array{name: string, description?: ?string} $values */
    public function handle(Company $company, CustomerGroup $group, array $values): CustomerGroup
    {
        return DB::transaction(function () use ($company, $group, $values): CustomerGroup {
            $group = $company->customerGroups()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();
            $group->update(['name' => trim($values['name']), 'description' => $values['description'] ?? null]);

            return $group;
        }, 2);
    }
}
