<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierAddress;
use Illuminate\Support\Facades\DB;

final readonly class SetDefaultSupplierAddress
{
    public function handle(Company $company, Supplier $supplier, SupplierAddress $address, User $actor): void
    {
        DB::transaction(function () use ($company, $supplier, $address, $actor): void {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'update');
            $address = $supplier->addresses()->whereKey($address->getKey())->firstOrFail();
            $supplier->addresses()->where('type', $address->type->value)->where('is_default', true)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }, 3);
    }
}
