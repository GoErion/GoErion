<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Services\Supplier\SupplierInput;
use Illuminate\Foundation\Http\FormRequest;

final class SupplierContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && $this->user()?->can('update', [Supplier::class, $company]) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SupplierInput::normalize($this->all()));
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return SupplierInput::contact();
    }
}
