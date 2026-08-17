<?php
declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required','string',Rule::in(['billing','shipping','other',])],
            'line_1' => ['required','string','max:255'],
            'line_2'  => ['nullable', 'string', 'max:255'],
            'city'  => ['nullable', 'string', 'max:100'],
            'postal_code'  => ['nullable', 'string', 'max:30'],
            'country'  => ['nullable', 'string', 'max:100'],
            'is_default' =>['nullable','boolean'],
        ];
    }
}
