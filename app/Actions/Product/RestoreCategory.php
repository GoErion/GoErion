<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Category;
use App\Services\Product\CategoryHierarchy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RestoreCategory
{
    public function handle(Company $company, Category $record, User $actor): Category
    {
        return DB::transaction(function () use ($company, $record, $actor): Category {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $record = $company->categories()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            CategoryHierarchy::validate($company, $record->parent_id, $record->id);
            if ($record->parent_id !== null && ! $company->categories()->whereKey($record->parent_id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['parent_id' => 'Restore the parent Category first.']);
            }
            $record->update(['is_active' => true]);

            return $record;
        }, 3);
    }
}
