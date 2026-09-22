<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => filled($this->input('email')) ? Str::lower(trim((string) $this->input('email'))) : null]);
        }
    }

    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && $this->user()?->can('create', [Customer::class, $company]) === true;
    }

    public function rules(): array
    {
        $company = $this->route('company');
        $canManageCredit = $this->user()?->can('manageCredit', [Customer::class, $company]) === true;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_pin' => ['nullable', 'string', 'max:50'],
            'customer_group_id' => ['nullable', 'integer', Rule::exists('customer_groups', 'id')
                ->where('company_id', $company->getKey())->where('is_active', true)],
            'credit_limit' => [$canManageCredit ? 'nullable' : 'prohibited', 'string', 'regex:/^\d{1,15}(?:\.\d{1,4})?$/'],
            'payment_terms_days' => [$canManageCredit ? 'nullable' : 'prohibited', 'integer', 'between:0,3650'],
            'code' => ['prohibited'],
            'company_id' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }
}
