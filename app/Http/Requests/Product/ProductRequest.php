<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use App\Services\Product\ProductInput;
use Illuminate\Foundation\Http\FormRequest;

final class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(ProductInput::normalize($this->all(), $this->route('company')));
    }

    public function authorize(): bool
    {
        $args = [Product::class, $this->route('company')];

        return $this->user()?->can('create', $args) === true
            && (! $this->filled('prices') || $this->user()->can('managePrices', $args))
            && (! $this->filled('barcodes') || $this->user()->can('manageBarcodes', $args));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ProductInput::rules($this->route('company'));
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [ProductInput::after(...)];
    }
}
