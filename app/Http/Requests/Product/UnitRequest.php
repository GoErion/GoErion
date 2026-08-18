<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UnitRequest extends FormRequest
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
        $company = $this->route('company');

        return [
            'name' => ['required','string','max:100',Rule::unique('units', 'name')
                    ->where('company_id', $company->id),
            ],
            'symbol' => ['required','string','max:20',Rule::unique('units', 'symbol')
                    ->where('company_id', $company->id),
            ],
        ];
    }
}
