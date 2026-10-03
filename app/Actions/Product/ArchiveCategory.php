<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Category;
use App\Services\Product\CategoryHierarchy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ArchiveCategory
{
    public function handle(Company $company, Category $record, User $actor): Category
    {
        return DB::transaction(function () use ($company, $record, $actor): Category {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $record = $company->categories()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            CategoryHierarchy::validate($company, $record->parent_id, $record->id);
            $parents = $company->categories()->pluck('parent_id', 'id')->all();
            foreach ($company->categories()->where('is_active', true)->pluck('id') as $id) {
                $seen = [];
                while (($id = $parents[$id] ?? null) !== null) {
                    if ($id === $record->id) {
                        throw ValidationException::withMessages(['category' => 'Archive active descendants first.']);
                    }
                    if (isset($seen[$id])) {
                        throw ValidationException::withMessages(['category' => 'Invalid category hierarchy.']);
                    }
                    $seen[$id] = true;
                }
            }
            $record->update(['is_active' => false]);

            return $record;
        }, 3);
    }
}
