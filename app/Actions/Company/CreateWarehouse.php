<?php
declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company\Company;

final readonly class CreateWarehouse
{
    public function handle(array $input,Company $company)
    {
        $branch = $company->branches()->findOrFail($input['branch_id']);

        return $branch->warehouses()->create([
            'warehouse_name' => $input['warehouse_name'],
            'code' => $input['code'],
            'is_active' => $input['is_active'] ?? true,
        ]);
    }
}
