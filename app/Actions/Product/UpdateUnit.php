<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Unit;
use App\Services\Product\ClassificationInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class UpdateUnit
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Unit $record, User $actor, array $values): Unit
    {
        return DB::transaction(function () use ($company, $record, $actor, $values): Unit {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $record = $company->units()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $values = Validator::make(ClassificationInput::normalize($values, 'unit'), ClassificationInput::rules($company, 'unit', $record->id))->validate();
            $record->update($values);

            return $record;
        }, 3);
    }
}
