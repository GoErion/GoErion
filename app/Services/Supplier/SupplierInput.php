<?php

declare(strict_types=1);

namespace App\Services\Supplier;

use App\Enums\SupplierAddressType;
use Illuminate\Validation\Rule;

final class SupplierInput
{
    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public static function normalize(array $values): array
    {
        foreach (['name', 'email', 'legal_name', 'tax_pin', 'registration_number', 'website', 'currency', 'position'] as $key) {
            if (isset($values[$key]) && is_string($values[$key])) {
                $values[$key] = trim($values[$key]);
                if ($key === 'email') {
                    $values[$key] = mb_strtolower($values[$key]);
                }
                if ($key === 'currency') {
                    $values[$key] = strtoupper($values[$key]);
                }
                if ($values[$key] === '' && $key !== 'name') {
                    $values[$key] = null;
                }
            }
        }

        return $values;
    }

    /** @return array<string, array<mixed>> */
    public static function protectedFields(): array
    {
        return array_fill_keys(['company_id', 'uuid', 'code', 'is_active', 'supplier_id', 'id'], ['missing']);
    }

    /** @return array<string, array<mixed>> */
    public static function profile(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_pin' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            ...self::protectedFields(),
        ];
    }

    /** @return array<string, array<mixed>> */
    public static function terms(bool $required = true): array
    {
        return [
            'currency' => [$required ? 'required' : 'sometimes', 'string', 'regex:/^[A-Z]{3}$/'],
            'payment_terms_days' => [$required ? 'required' : 'sometimes', 'integer', 'between:0,3650'],
            'lead_time_days' => ['nullable', 'integer', 'between:0,3650'],
        ];
    }

    /** @return array<string, array<mixed>> */
    public static function contact(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['missing'],
            ...self::protectedFields(),
        ];
    }

    /** @return array<string, array<mixed>> */
    public static function address(): array
    {
        return [
            'type' => ['required', Rule::enum(SupplierAddressType::class)],
            'line_1' => ['required', 'string', 'max:255'],
            'line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            ...self::protectedFields(),
        ];
    }
}
