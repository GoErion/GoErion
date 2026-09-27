<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier\SupplierAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierAddress>
 */
class SupplierAddressFactory extends Factory
{
    protected $model = SupplierAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => SupplierFactory::new(),
            'type' => 'billing',
            'line_1' => fake()->streetAddress(),
            'line_2' => null,
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'is_default' => false,
        ];
    }
}
