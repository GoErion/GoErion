<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use App\Services\Product\ClassificationInput;
use Illuminate\Foundation\Http\FormRequest;

final class CategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(ClassificationInput::normalize($this->all(), 'category'));
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manageClassifications', [Product::class, $this->route('company')]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $company = $this->route('company');
        $record = $this->route('category') === null ? null : $company->categories()->where('uuid', $this->route('category'))->firstOrFail();
        $rules = ClassificationInput::rules($company, 'category', $record?->id);

        return $rules;
    }
}
