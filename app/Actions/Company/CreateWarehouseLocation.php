<?php
declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company\Warehouse;

final readonly class CreateWarehouseLocation
{
    public function handle(array $input,Warehouse $warehouse)
    {
        return $warehouse->locations()->create(
            [
                'name' => $input['name'],
                'code' => $input['code'],
                'is_active' => $input['is_active'] ?? true
            ]);
    }
}
