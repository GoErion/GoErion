<?php

declare(strict_types=1);

namespace Database\Factories\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Company\CompanyAccessEvent;
use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyAccessEvent>
 */
class CompanyAccessEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'actor_user_id' => UserFactory::new(),
            'subject_user_id' => null,
            'role_id' => null,
            'invitation_id' => null,
            'event_type' => CompanyAccessEventType::MemberRolesUpdated,
            'metadata' => [],
        ];
    }
}
