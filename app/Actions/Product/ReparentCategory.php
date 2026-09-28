<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Category;
use App\Services\Product\CategoryHierarchy;
use Illuminate\Support\Facades\DB;

final readonly class ReparentCategory
{
    public function handle(Company $company, Category $category, User $actor, ?int $parent): Category
    {
        return DB::transaction(function () use ($company, $category, $actor, $parent): Category {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $category = $company->categories()->whereKey($category->id)->lockForUpdate()->firstOrFail();
            if ($parent !== null) {
                $company->categories()->whereKey($parent)->where('is_active', true)->firstOrFail();
            }
            CategoryHierarchy::validate($company, $parent, $category->id);
            $category->update(['parent_id' => $parent]);

            return $category;
        }, 3);
    }
}
