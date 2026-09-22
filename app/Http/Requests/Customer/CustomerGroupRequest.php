<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Company\Company;
use App\Models\Customer\CustomerGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerGroupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && $this->user()?->can('manage', [CustomerGroup::class, $company]) === true;
    }

    public function rules(): array
    {
        $company = $this->route('company');
        $creating = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => $creating
                ? ['required', 'string', 'max:50', Rule::unique('customer_groups', 'code')->where('company_id', $company->getKey())]
                : ['prohibited'],
            'description' => ['nullable', 'string'],
            'company_id' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }
}
