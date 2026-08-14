<?php
declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required','string','max:255'],
            'legal_name' => ['nullable','string','max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
