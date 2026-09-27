<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use Illuminate\Support\Facades\DB;

final readonly class RestoreSupplier
{
    public function handle(Company $company, Supplier $supplier, User $actor): Supplier
    {
        return DB::transaction(function () use ($company, $supplier, $actor): Supplier {
            $supplier = SupplierMutation::lock($company, $supplier, $actor, 'archive');
            $supplier->update(['is_active' => true]);

            return $supplier;
        }, 3);
    }
}
