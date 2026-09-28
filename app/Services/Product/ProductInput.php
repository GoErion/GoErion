<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Enums\BarcodeType;
use App\Enums\ProductType;
use App\Models\Company\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as Validation;

final class ProductInput
{
    /** @param array<string, mixed> $values @return array<string, mixed> */
    public static function normalize(array $values, Company $company): array
    {
        foreach (['name', 'description'] as $key) {
            if (isset($values[$key]) && is_string($values[$key])) {
                $values[$key] = trim($values[$key]);
            }
        }
        $values['product_type'] ??= 'physical';
        foreach (['track_stock', 'track_serial', 'track_batch'] as $key) {
            $values[$key] ??= false;
        }
        if (isset($values['prices']) && is_array($values['prices'])) {
            foreach ($values['prices'] as &$price) {
                if (! is_array($price)) {
                    continue;
                }
                $price['currency'] ??= strtoupper((string) $company->currency);
                if (is_string($price['currency'])) {
                    $price['currency'] = strtoupper(trim($price['currency']));
                }
            }
        }

        return $values;
    }

    /** @return array<string, mixed> */
    public static function rules(Company $company, bool $children = true): array
    {
        $exists = fn (string $table) => Rule::exists($table, 'id')->where('company_id', $company->id)->where('is_active', true);
        $rules = [
            'company_id' => ['missing'], 'uuid' => ['missing'], 'sku' => ['missing'], 'prefix' => ['missing'], 'is_active' => ['missing'],
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', $exists('categories')],
            'unit_id' => ['required', 'integer', $exists('units')],
            'brand_id' => ['nullable', 'integer', $exists('brands')],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'track_stock' => ['boolean'], 'track_serial' => ['boolean'], 'track_batch' => ['boolean'],
        ];
        if (! $children) {
            return [...$rules, 'prices' => ['missing'], 'barcodes' => ['missing']];
        }

        return [...$rules,
            'prices' => ['nullable', 'array', 'max:100'], 'prices.*' => ['array:price_type,price,currency'],
            'prices.*.price_type' => ['required', Rule::in(['retail', 'wholesale', 'dealer'])],
            'prices.*.price' => ['required', 'string', 'regex:/\A[0-9]{1,15}(?:\.[0-9]{1,4})?\z/D'],
            'prices.*.currency' => ['required', 'string', 'regex:/\A[A-Z]{3}\z/D'],
            'barcodes' => ['nullable', 'array', 'max:100'], 'barcodes.*' => ['array:barcode,type'],
            'barcodes.*.barcode' => ['required', 'string', 'max:100', 'distinct:strict', Rule::unique('product_barcodes', 'barcode')->where('company_id', $company->id)],
            'barcodes.*.type' => ['required', Rule::enum(BarcodeType::class)],
        ];
    }

    public static function after(Validation $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        $values = $validator->getData();
        if (($values['track_serial'] || $values['track_batch']) && ! $values['track_stock']) {
            $validator->errors()->add('track_stock', 'Serial or batch tracking requires stock tracking.');
        }
        $seen = [];
        foreach ($values['prices'] ?? [] as $key => $price) {
            $pair = $price['price_type'].':'.$price['currency'];
            if (isset($seen[$pair])) {
                $validator->errors()->add("prices.$key.price_type", 'Each selling price type and currency must be unique.');
            }
            $seen[$pair] = true;
        }
        foreach ($values['barcodes'] ?? [] as $key => $barcode) {
            $value = $barcode['barcode'];
            $lengths = match ($barcode['type']) {
                'EAN' => [8, 13], 'UPC' => [12], default => []
            };
            if ($lengths === []) {
                if (trim($value) === '') {
                    $validator->errors()->add("barcodes.$key.barcode", 'A barcode value is required.');
                }

                continue;
            }
            if (! ctype_digit($value) || ! in_array(strlen($value), $lengths, true)) {
                $validator->errors()->add("barcodes.$key.barcode", 'Invalid barcode length or digits.');

                continue;
            }
            $sum = 0;
            foreach (str_split(strrev(substr($value, 0, -1))) as $i => $digit) {
                $sum += (int) $digit * ($i % 2 === 0 ? 3 : 1);
            }
            if ((10 - $sum % 10) % 10 !== (int) substr($value, -1)) {
                $validator->errors()->add("barcodes.$key.barcode", 'Invalid barcode check digit.');
            }
        }
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    public static function validate(array $values, Company $company, bool $children = true): array
    {
        $validator = Validator::make(self::normalize($values, $company), self::rules($company, $children));
        $validator->after(self::after(...));

        return $validator->validate();
    }
}
