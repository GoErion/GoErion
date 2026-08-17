<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierAddress;
use Illuminate\Support\Facades\DB;

final readonly class CreateSupplierAddress
{
    public function handle(array $input,Supplier $supplier): SupplierAddress
    {
        return DB::transaction(function () use ($input, $supplier)
        {
            if ($input['is_default'] ?? false)
            {
                $supplier->addresses()
                    ->where('type', $input['type'])
                    ->update([
                        'is_default' => false,
                    ]);
            }

            return $supplier->addresses()->create([
                'type' => $input['type'],
                'line_1' => $input['line_1'],
                'line_2' => $input['line_2'] ?? null,
                'city' => $input['city'] ?? null,
                'postal_code' => $input['postal_code'] ?? null,
                'country' => $input['country'] ?? null,
                'is_default' => $input['is_default'] ?? false,
            ]);
        });
    }
}
