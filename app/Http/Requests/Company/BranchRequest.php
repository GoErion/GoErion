<?php
declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'branch_name' => ['required','string','max:255'],
            'code'  => ['required', 'string', 'max:255'],
            'email' => ['nullable','email','max:255'],
            'phone'  => ['nullable', 'string', 'max:255'],
            'address' => ['nullable','string']
        ];
    }
}
