<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Unit;
use App\Services\Product\ClassificationInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class CreateUnit
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, User $actor, array $values): Unit
    {
        return DB::transaction(function () use ($company, $actor, $values): Unit {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $values = Validator::make(ClassificationInput::normalize($values, 'unit'), ClassificationInput::rules($company, 'unit'))->validate();

            return $company->units()->create([...$values, 'is_active' => true]);
        }, 3);
    }
}
