<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use App\Services\Product\ClassificationInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BrandRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(ClassificationInput::normalize($this->all(), 'brand'));
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manageClassifications', [Product::class, $this->route('company')]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = ClassificationInput::rules($this->route('company'), 'brand');
        if (is_string($this->input('code'))) {
            $rules['name'][] = Rule::unique('brands', 'name')->where('company_id', $this->route('company')->id)->where('code', $this->input('code'))->ignore($this->route('company')->brands()->where('uuid', $this->route('brand'))->value('id'));
        }

        return $rules;
    }
}
