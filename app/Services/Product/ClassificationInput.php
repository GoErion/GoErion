<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Models\Company\Company;
use Illuminate\Validation\Rule;

final class ClassificationInput
{
    /** @return array<string, mixed> */
    public static function rules(Company $company, string $type, ?int $id = null): array
    {
        $rules = ['company_id' => ['missing'], 'uuid' => ['missing'], 'is_active' => ['missing'], 'name' => ['required', 'string', 'max:255']];
        if ($type === 'unit') {
            return [...$rules, 'name' => ['required', 'string', 'max:100', Rule::unique('units', 'name')->where('company_id', $company->id)->ignore($id)], 'symbol' => ['required', 'string', 'max:20', Rule::unique('units', 'symbol')->where('company_id', $company->id)->ignore($id)]];
        }

        if ($type === 'category') {
            $currentParent = $id === null ? null : $company->categories()->whereKey($id)->firstOrFail()->parent_id;
            $parent = Rule::exists('categories', 'id')->where('company_id', $company->id)
                ->where(function ($query) use ($currentParent): void {
                    $query->where('is_active', true);
                    if ($currentParent !== null) {
                        $query->orWhere('id', $currentParent);
                    }
                });

            return [...$rules,
                'code' => ['required', 'string', 'max:255', Rule::unique('categories', 'code')->where('company_id', $company->id)->ignore($id)],
                'parent_id' => ['nullable', 'integer', $parent],
            ];
        }

        return [...$rules, 'code' => ['required', 'string', 'max:50']];
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    public static function normalize(array $values, string $type): array
    {
        foreach (['name', 'code', 'symbol'] as $key) {
            if (isset($values[$key]) && is_string($values[$key])) {
                $values[$key] = trim($values[$key]);
            }
        }
        if ($type === 'brand') {
            if (isset($values['name']) && is_string($values['name'])) {
                $values['name'] = strtolower($values['name']);
            }
            if (isset($values['code']) && is_string($values['code'])) {
                $values['code'] = strtoupper($values['code']);
            }
        }
        if ($type === 'unit' && isset($values['symbol']) && is_string($values['symbol'])) {
            $values['symbol'] = strtolower($values['symbol']);
        }

        return $values;
    }

    /** @return array<int, array{path: string, depth: int}> */
    public static function paths(Company $company): array
    {
        $nodes = $company->categories()->get(['id', 'name', 'parent_id'])->keyBy('id');
        $paths = [];
        foreach ($nodes as $node) {
            $labels = [];
            $seen = [];
            $cursor = $node;
            while ($cursor !== null) {
                if (isset($seen[$cursor->id]) || count($labels) >= 3) {
                    $labels[] = '[Invalid hierarchy]';
                    break;
                }
                $seen[$cursor->id] = true;
                $labels[] = $cursor->name;
                $cursor = $cursor->parent_id === null ? null : $nodes->get($cursor->parent_id);
            }
            $paths[$node->id] = ['path' => implode(' / ', array_reverse($labels)), 'depth' => count($labels)];
        }

        return $paths;
    }
}
