<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Auth\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'name' => fake()->unique()->slug(2),
            'display_name' => fake()->words(2, true),
        ];
    }
}
