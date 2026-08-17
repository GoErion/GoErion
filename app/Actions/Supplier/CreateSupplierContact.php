<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierContact;

final readonly class CreateSupplierContact
{
    public function handle(array $input,Supplier $supplier): SupplierContact
    {
        return $supplier->contacts()->create([
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'position' => $input['position'] ?? null,
        ]);
    }
}
