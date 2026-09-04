<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->unique()->company(),
            'legal_name' => fake()->unique()->company().' Limited',
            'registration_number' => fake()->unique()->bothify('REG-#####'),
            'tax_number' => fake()->unique()->bothify('PIN-########'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }
}
