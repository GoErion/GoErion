<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Product;
use App\Services\Product\ClassificationInput;
use Illuminate\Foundation\Http\FormRequest;

final class UnitRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(ClassificationInput::normalize($this->all(), 'unit'));
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manageClassifications', [Product::class, $this->route('company')]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $company = $this->route('company');
        $record = $this->route('unit') === null ? null : $company->units()->where('uuid', $this->route('unit'))->firstOrFail();
        $rules = ClassificationInput::rules($company, 'unit', $record?->id);

        return $rules;
    }
}
