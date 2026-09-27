<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Services\Supplier\SupplierInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class UpdateSupplierCommercialTerms
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Supplier $supplier, User $actor, array $values): Supplier
    {
        return DB::transaction(function () use ($company, $supplier, $actor, $values): Supplier {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'manageTerms');
            $values = Validator::make(SupplierInput::normalize($values), [...SupplierInput::terms(), ...SupplierInput::protectedFields()])->validate();
            $supplier->update($values);

            return $supplier;
        }, 3);
    }
}
