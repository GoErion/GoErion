<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Models\Product\Brand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BrandRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower($this->string('name')->toString()),
            'code' => strtoupper($this->string('code')->toString()),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company !== null
            && $this->user()?->companies()->whereKey($company->getKey())->exists() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Brand::class)
                    ->where(fn (Builder $query): Builder => $query
                        ->where('company_id', $this->route('company')->getKey())
                        ->where('code', strtoupper($this->string('code')->toString())))
                    ->ignore($this->route('brand')),
            ],
            'code' => ['required', 'string', 'max:50'],
        ];
    }
}
