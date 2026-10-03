<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use Illuminate\Foundation\Http\FormRequest;

final class ProductLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $args = [Product::class, $this->route('company')];

        return $this->user()?->can('viewAny', $args) === true && $this->user()->can('archive', $args);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
