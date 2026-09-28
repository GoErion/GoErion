<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Product\Brand;
use App\Services\Product\ClassificationInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class UpdateBrand
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, Brand $brand, User $actor, array $values): Brand
    {
        return DB::transaction(function () use ($company, $brand, $actor, $values): Brand {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $brand = $company->brands()->whereKey($brand->id)->lockForUpdate()->firstOrFail();
            $values = Validator::make(ClassificationInput::normalize($values, 'brand'), ClassificationInput::rules($company, 'brand', $brand->id))->validate();
            Validator::make($values, ['name' => [Rule::unique('brands', 'name')->where('company_id', $company->id)->where('code', $values['code'])->ignore($brand->id)]])->validate();
            $brand->update($values);

            return $brand;
        }, 3);
    }
}
