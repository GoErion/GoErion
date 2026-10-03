<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use App\Models\Supplier\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductIndexRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('q'))) {
            $this->merge(['q' => trim($this->input('q'))]);
        }
    }

    public function authorize(): bool
    {
        $company = $this->route('company');

        return $this->user()?->can('viewAny', [Product::class, $company]) === true
            && (! $this->has('supplier') || $this->user()->can('viewAny', [Supplier::class, $company]));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['active', 'archived', 'all'])],
            'sort' => ['sometimes', Rule::in(['name', 'sku', 'created_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ];
        foreach (['category' => 'categories', 'brand' => 'brands', 'unit' => 'units', 'supplier' => 'suppliers'] as $key => $table) {
            $rules[$key] = ['nullable', 'uuid', Rule::exists($table, 'uuid')->where('company_id', $this->route('company')->id)];
        }

        return $rules;
    }
}
