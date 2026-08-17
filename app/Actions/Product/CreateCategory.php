<?php
declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Company\Company;

final readonly class CreateCategory
{
    public function handle(array $input,Company $company)
    {
        return $company->categories()->create(
            [
                'parent_id' => $input['parent_id'] ?? null,
                'name' => $input['name'],
                'code' => $input['code'],
            ]);
    }
}
