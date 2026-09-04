<?php

declare(strict_types=1);

use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects a verified user with multiple companies to company selection', function () {
    $user = UserFactory::new()->create(['password' => 'SecurePassword1!']);
    $companies = CompanyFactory::new()->count(2)->create();
    $user->companies()->attach($companies);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'SecurePassword1!',
    ]);

    $response->assertRedirect(route('companies.select'));
});

it('lists only companies belonging to the authenticated user', function () {
    $user = UserFactory::new()->create();
    $memberCompany = CompanyFactory::new()->create(['company_name' => 'Member Company']);
    CompanyFactory::new()->create(['company_name' => 'Other Company']);
    $user->companies()->attach($memberCompany);

    $this->actingAs($user)->get(route('companies.select'))
        ->assertSee('Member Company')
        ->assertDontSee('Other Company')
        ->assertSee(route('home', $memberCompany), false);
});
