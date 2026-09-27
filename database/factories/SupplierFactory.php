<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

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
            'currency' => 'KES',
            'lead_time_days' => null,
            'payment_terms_days' => 0,
            'is_active' => true,
        ];
    }
}
