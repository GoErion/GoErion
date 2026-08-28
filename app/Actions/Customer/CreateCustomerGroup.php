<?php
declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Company\Company;
use Illuminate\Support\Facades\DB;

final readonly class CreateCustomerGroup
{
    public function handle(array $input, Company $company)
    {
        return DB::transaction(function() use($input,$company)
        {
            $code = strtoupper(trim($input['code']));

            return $company->customerGroups()->create(
                [
                    'name' => trim($input['name']),
                    'code' => $code,
                    'description' => $input['description'] ?? null,
                    'is_active' => true,
                ]);
        });
    }
}
