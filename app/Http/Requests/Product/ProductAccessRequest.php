<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $abilities = match ($this->route()->getName()) {
            'product.index', 'product.show' => ['viewAny'],
            'product.create' => ['create'],
            'product.edit' => ['viewAny', 'update'],
            'category.index', 'category.create', 'category.edit', 'category.archive', 'category.restore',
            'brand.index', 'brand.create', 'brand.edit', 'brand.archive', 'brand.restore',
            'unit.index', 'unit.create', 'unit.edit', 'unit.archive', 'unit.restore' => ['manageClassifications'],
            default => [],
        };
        if ($abilities === []) {
            return false;
        }
        foreach ($abilities as $ability) {
            if ($this->user()?->can($ability, [Product::class, $this->route('company')]) !== true) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->routeIs('category.index', 'brand.index', 'unit.index')
            ? ['status' => ['sometimes', Rule::in(['active', 'archived', 'all'])], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]
            : [];
    }
}
