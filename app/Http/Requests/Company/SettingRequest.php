<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Company\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company
            && $this->user()?->can('view', $company) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_due_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
