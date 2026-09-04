<?php

declare(strict_types=1);

use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects a verified user with one company to that company after login', function () {
    $user = UserFactory::new()->create(['password' => 'SecurePassword1!']);
    $company = CompanyFactory::new()->create();
    $user->companies()->attach($company);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'SecurePassword1!',
    ]);

    $response->assertRedirect(route('home', $company));
    $this->assertAuthenticatedAs($user);
});

it('returns invalid credentials to login without flashing the password', function () {
    $user = UserFactory::new()->create();

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'not-the-password',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertInvalid(['email'])
        ->assertSessionMissing('_old_input.password');
    $this->assertGuest();
});

it('redirects an authenticated verified user without a company to onboarding', function () {
    $user = UserFactory::new()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(route('company.create'));
});
