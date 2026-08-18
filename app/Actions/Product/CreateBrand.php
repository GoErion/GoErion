<?php
declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Company\Company;

final readonly class CreateBrand
{
    public function handle(array $input,Company $company)
    {
        return $company->brands()->create(
            [
                'name' => strtolower($input['name']),
                'code' => $input['code'],
            ]);
    }
}
