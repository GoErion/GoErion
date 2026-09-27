<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierContact;
use App\Services\Supplier\SupplierInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final readonly class CreateSupplierContact
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Supplier $supplier, User $actor, array $values): SupplierContact
    {
        return DB::transaction(function () use ($company, $supplier, $values, $actor): SupplierContact {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'update');
            $values = Validator::make(SupplierInput::normalize($values), SupplierInput::contact())->validate();

            return $supplier->contacts()->create([
                'name' => trim($values['name']),
                'email' => isset($values['email']) ? Str::lower(trim($values['email'])) : null,
                'phone' => $values['phone'] ?? null,
                'position' => $values['position'] ?? null,
                'is_primary' => ! $supplier->contacts()->exists(),
            ]);
        }, 3);
    }
}
