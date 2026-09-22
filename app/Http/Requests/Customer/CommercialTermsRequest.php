<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Foundation\Http\FormRequest;

final class CommercialTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && $this->user()?->can('manageCredit', [Customer::class, $company]) === true;
    }

    public function rules(): array
    {
        return [
            'credit_limit' => ['required', 'string', 'regex:/^\d{1,15}(?:\.\d{1,4})?$/'],
            'payment_terms_days' => ['required', 'integer', 'between:0,3650'],
            'company_id' => ['prohibited'],
            'code' => ['prohibited'],
        ];
    }
}
