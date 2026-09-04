<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Company\Company;
use App\Models\Product\Brand;

final readonly class CreateBrand
{
    public function handle(array $input, Company $company): Brand
    {
        return $company->brands()->create(
            [
                'name' => strtolower($input['name']),
                'code' => strtoupper($input['code']),
            ]);
    }
}
