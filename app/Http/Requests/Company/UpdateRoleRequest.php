<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Auth\Permission;
use App\Models\Company\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['permission_ids' => $this->input('permission_ids', [])]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && $this->user()?->can('manageRoles', $company) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $company = $this->route('company');
        $role = $company->roles()->where('uuid', $this->route('roleUuid'))->first();

        return [
            'name' => ['required', 'string', 'max:255', 'not_in:owner', Rule::unique('roles', 'name')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($role?->id)],
            'display_name' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')->where(fn ($query) => $query->where('assignable_to_custom_roles', true)->whereIn('name', Permission::assignableNames()))],
        ];
    }
}
