<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use Illuminate\Foundation\Http\FormRequest;

final class ProductAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->routeIs('product.create') ? 'create' : ($this->routeIs('product.*') ? 'viewAny' : 'manageClassifications');

        return $this->user()?->can($ability, [Product::class, $this->route('company')]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
