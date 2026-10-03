<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use App\Services\Product\ProductInput;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $args = [Product::class, $this->route('company')];

        return $this->user()?->can('viewAny', $args) === true && $this->user()->can('update', $args);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $company = $this->route('company');
        $product = $company->products()->where('uuid', $this->route('product'))->firstOrFail();

        return ProductInput::profileRules($company, $product);
    }
}
