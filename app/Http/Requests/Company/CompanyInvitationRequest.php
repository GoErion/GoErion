<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Company\CompanyInvitation;
use Illuminate\Validation\Rule;

final class CompanyInvitationRequest extends ManageInvitationRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => CompanyInvitation::normalizeEmail($this->input('email'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('company_id', $this->route('company')->id)->where('is_system', 0)->whereNot('name', 'owner')],
        ];
    }
}
