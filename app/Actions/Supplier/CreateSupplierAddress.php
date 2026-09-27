<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierAddress;
use App\Services\Supplier\SupplierInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class CreateSupplierAddress
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Supplier $supplier, User $actor, array $values): SupplierAddress
    {
        return DB::transaction(function () use ($company, $supplier, $values, $actor): SupplierAddress {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'update');
            $values = Validator::make(SupplierInput::normalize($values), SupplierInput::address())->validate();
            $type = $values['type'];
            $isDefault = ! $supplier->addresses()->where('type', $type)->exists() || ($values['is_default'] ?? false);
            if ($isDefault) {
                $supplier->addresses()->where('type', $type)->where('is_default', true)->update(['is_default' => false]);
            }

            return $supplier->addresses()->create([
                'type' => $type,
                'line_1' => $values['line_1'],
                'line_2' => $values['line_2'] ?? null,
                'city' => $values['city'] ?? null,
                'postal_code' => $values['postal_code'] ?? null,
                'country' => $values['country'] ?? null,
                'is_default' => $isDefault,
            ]);
        }, 3);
    }
}
