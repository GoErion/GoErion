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

final readonly class UpdateSupplierContact
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Supplier $supplier, SupplierContact $contact, User $actor, array $values): SupplierContact
    {
        return DB::transaction(function () use ($company, $supplier, $contact, $values, $actor): SupplierContact {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'update');
            $values = Validator::make(SupplierInput::normalize($values), SupplierInput::contact())->validate();
            $contact = $supplier->contacts()->whereKey($contact->getKey())->firstOrFail();
            $contact->update([
                'name' => trim($values['name']),
                'email' => isset($values['email']) ? Str::lower(trim($values['email'])) : null,
                'phone' => $values['phone'] ?? null,
                'position' => $values['position'] ?? null,
            ]);

            return $contact;
        }, 3);
    }
}
