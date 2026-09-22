<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateCustomerRequest extends FormRequest
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

        return $company instanceof Company && $this->user()?->can('update', [Customer::class, $company]) === true;
    }

    public function rules(): array
    {
        $company = $this->route('company');
        $customer = $company->customers()->where('uuid', $this->route('customer'))->firstOrFail();
        $currentGroupId = $customer->groupMembership()->value('customer_group_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_pin' => ['nullable', 'string', 'max:50'],
            'customer_group_id' => ['nullable', 'integer', Rule::exists('customer_groups', 'id')
                ->where(fn ($query) => $query->where('company_id', $company->getKey())
                    ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $currentGroupId)))],
            'code' => ['prohibited'],
            'company_id' => ['prohibited'],
            'credit_limit' => ['prohibited'],
            'payment_terms_days' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }
}
