<?php
declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company\Company;

final readonly class CreateBranch
{
    public function handle(array $input,Company $company)
    {
        return $company->branches()->create(
            [
                'branch_name' => $input['branch_name'],
                'code' => $input['code'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'address' => $input['address'] ?? null,
            ]);
    }
}
