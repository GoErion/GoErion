<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierContact;
use Illuminate\Support\Facades\DB;

final readonly class SetPrimarySupplierContact
{
    public function handle(Company $company, Supplier $supplier, SupplierContact $contact, User $actor): void
    {
        DB::transaction(function () use ($company, $supplier, $contact, $actor): void {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'update');
            $contact = $supplier->contacts()->whereKey($contact->getKey())->firstOrFail();
            $supplier->contacts()->where('is_primary', true)->update(['is_primary' => false]);
            $contact->update(['is_primary' => true]);
        }, 3);
    }
}
