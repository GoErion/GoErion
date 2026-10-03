<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Unit;
use Illuminate\Support\Facades\DB;

final readonly class ArchiveUnit
{
    public function handle(Company $company, Unit $record, User $actor): Unit
    {
        return DB::transaction(function () use ($company, $record, $actor): Unit {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $record = $company->units()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $record->update(['is_active' => false]);

            return $record;
        }, 3);
    }
}
