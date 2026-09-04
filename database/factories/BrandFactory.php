<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'name' => fake()->unique()->company(),
            'code' => fake()->unique()->bothify('BR-###'),
        ];
    }
}
