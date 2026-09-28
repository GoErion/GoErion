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

final readonly class CreateBrand
{
    /** @param array<string, mixed> $values */
    public function handle(Company $company, User $actor, array $values): Brand
    {
        return DB::transaction(function () use ($company, $actor, $values): Brand {
            $company = ProductMutation::lock($company, $actor, 'manageClassifications');
            $values = Validator::make(ClassificationInput::normalize($values, 'brand'), ClassificationInput::rules($company, 'brand'))->validate();
            Validator::make($values, ['name' => [Rule::unique('brands', 'name')->where('company_id', $company->id)->where('code', $values['code'])]])->validate();

            return $company->brands()->create([...$values, 'is_active' => true]);
        }, 3);
    }
}
