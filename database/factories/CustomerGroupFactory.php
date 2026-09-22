<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->bothify('GRP-######'),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
