<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Models\Company\Company;
use Illuminate\Validation\ValidationException;

final class CategoryHierarchy
{
    /** Validate a proposed parent assignment, including every descendant's depth. */
    public static function validate(Company $company, ?int $parent, ?int $category = null): void
    {
        $parents = $company->categories()->pluck('parent_id', 'id')->all();
        if ($parent !== null && ! array_key_exists($parent, $parents)) {
            self::fail();
        }
        $parents[$category ?? -1] = $parent;
        foreach (array_keys($parents) as $id) {
            $seen = [];
            $depth = 0;
            while ($id !== null) {
                if (isset($seen[$id]) || ! array_key_exists($id, $parents) || ++$depth > 3) {
                    self::fail();
                }
                $seen[$id] = true;
                $id = $parents[$id];
            }
        }
    }

    private static function fail(): never
    {
        throw ValidationException::withMessages(['parent_id' => 'Categories require same-Company parents, no cycles, and at most three levels.']);
    }
}
