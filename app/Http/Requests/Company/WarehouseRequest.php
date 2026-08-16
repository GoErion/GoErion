<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class WarehouseRequest extends FormRequest
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
            'branch_id' => ['required','integer',Rule::exists('branches','id')->where(
                fn($query) => $query->where('company_id',$this->route('company')->id)
                )],
            'warehouse_name' => ['required','string','max:255'],
            'code' => ['required', 'string', 'max:50']
        ];
    }
}
