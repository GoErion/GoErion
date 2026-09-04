<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders accessible guest authentication forms with csrf protection', function (string $routeName, string $heading) {
    $response = $this->get(route($routeName));

    $response
        ->assertSee($heading)
        ->assertSee('name="_token"', false)
        ->assertSee('autocomplete=', false)
        ->assertDontSee('value="password"', false);
})->with([
    'login' => ['login', 'Welcome back'],
    'registration' => ['register', 'Create your GoErion account'],
    'forgotten password' => ['password.request', 'Reset your password'],
]);

it('renders the reset form without populating password fields', function () {
    $response = $this->get(route('password.reset', [
        'token' => 'safe-token',
        'email' => 'member@example.com',
    ]));

    $response
        ->assertSee('Choose a new password')
        ->assertSee('value="member@example.com"', false)
        ->assertDontSee('type="password" value=', false);
});

it('renders verification and password confirmation actions for authenticated users', function () {
    $user = UserFactory::new()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertSee('Verify your email address')
        ->assertSee(route('verification.send'), false)
        ->assertSee(route('logout'), false);

    $this->actingAs($user)->get(route('password.confirm'))
        ->assertSee('Confirm your password')
        ->assertSee('autocomplete="current-password"', false);
});
