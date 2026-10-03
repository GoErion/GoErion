<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Category;
use App\Services\Product\CategoryHierarchy;
use App\Services\Product\ClassificationInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class UpdateCategory
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Category $record, User $actor, array $values): Category
    {
        return DB::transaction(function () use ($company, $record, $actor, $values): Category {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $record = $company->categories()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            if (! array_key_exists('parent_id', $values)) {
                $values['parent_id'] = $record->parent_id;
            }
            $values = Validator::make(ClassificationInput::normalize($values, 'category'), ClassificationInput::rules($company, 'category', $record->id))->validate();
            CategoryHierarchy::validate($company, isset($values['parent_id']) ? (int) $values['parent_id'] : null, $record->id);
            $record->update($values);

            return $record;
        }, 3);
    }
}
