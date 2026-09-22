<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'name' => fake()->name(),
            'code' => fake()->unique()->bothify('LEG-######'),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'tax_pin' => null,
            'credit_limit' => '0.0000',
            'payment_terms_days' => 0,
            'is_active' => true,
        ];
    }
}
