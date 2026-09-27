<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company\Company;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'category_id' => fn (array $values) => Company::query()->findOrFail($values['company_id'])->categories()->create(['name' => 'Hardware', 'code' => fake()->unique()->uuid()])->id,
            'unit_id' => fn (array $values) => Company::query()->findOrFail($values['company_id'])->units()->create(['name' => 'Each', 'symbol' => fake()->unique()->bothify('EA-######')])->id,
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->uuid(),
            'product_type' => 'physical',
            'is_active' => true,
        ];
    }
}
