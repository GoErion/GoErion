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

final readonly class CreateCategory
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, User $actor, array $values): Category
    {
        return DB::transaction(function () use ($company, $actor, $values): Category {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $values = Validator::make(ClassificationInput::normalize($values, 'category'), ClassificationInput::rules($company, 'category'))->validate();
            CategoryHierarchy::validate($company, isset($values['parent_id']) ? (int) $values['parent_id'] : null);

            return $company->categories()->create([...$values, 'is_active' => true]);
        }, 3);
    }
}
