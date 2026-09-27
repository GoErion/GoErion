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

final readonly class UpdateSupplierAddress
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Supplier $supplier, SupplierAddress $address, User $actor, array $values): SupplierAddress
    {
        return DB::transaction(function () use ($company, $supplier, $address, $values, $actor): SupplierAddress {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'update');
            $values = Validator::make(SupplierInput::normalize($values), SupplierInput::address())->validate();
            $address = $supplier->addresses()->whereKey($address->getKey())->firstOrFail();
            $oldType = $address->type->value;
            $newType = $values['type'];
            $wasDefault = $address->is_default;
            $newTypeHasDefault = $supplier->addresses()->where('type', $newType)->where('is_default', true)
                ->whereKeyNot($address->getKey())->exists();
            $makeDefault = $oldType === $newType
                ? ($values['is_default'] ?? false) || $wasDefault
                : ($values['is_default'] ?? false) || ! $newTypeHasDefault;

            if ($makeDefault) {
                $supplier->addresses()->where('type', $newType)->where('is_default', true)
                    ->whereKeyNot($address->getKey())->update(['is_default' => false]);
            }

            $address->update([
                'type' => $newType,
                'line_1' => $values['line_1'],
                'line_2' => $values['line_2'] ?? null,
                'city' => $values['city'] ?? null,
                'postal_code' => $values['postal_code'] ?? null,
                'country' => $values['country'] ?? null,
                'is_default' => $makeDefault,
            ]);

            if ($oldType !== $newType && $wasDefault) {
                $supplier->addresses()->where('type', $oldType)->orderBy('id')->first()?->update(['is_default' => true]);
            }

            return $address;
        }, 3);
    }
}
