<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use App\Models\Customer\CustomerGroup;
use Illuminate\Support\Facades\DB;

final readonly class SetCustomerGroupActive
{
    public function handle(Company $company, CustomerGroup $group, bool $active): CustomerGroup
    {
        return DB::transaction(function () use ($company, $group, $active): CustomerGroup {
            $group = $company->customerGroups()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();
            $group->update(['is_active' => $active]);

            return $group;
        }, 2);
    }
}
