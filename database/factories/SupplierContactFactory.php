<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier\SupplierContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierContact>
 */
class SupplierContactFactory extends Factory
{
    protected $model = SupplierContact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => SupplierFactory::new(),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'position' => fake()->optional()->jobTitle(),
            'is_primary' => false,
        ];
    }
}
