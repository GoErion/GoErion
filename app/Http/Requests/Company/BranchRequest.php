<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Company\Company;
use Illuminate\Foundation\Http\FormRequest;

final class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company
            && $this->user()?->can('view', $company) === true;
    }

    public function rules(): array
    {
        return [
            'branch_name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ];
    }
}
