<?php
declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Company\Company;

final readonly class CreateUnit
{
    public function handle(Company $company,array $input)
    {
        return $company->units()->create([
            'name' => $input['name'],
            'symbol' => strtolower($input['symbol']),
        ]);
    }
}
