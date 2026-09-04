<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to confirm a password', function () {
    $this->get(route('password.confirm'))->assertRedirect(route('login'));
    $this->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('login'));
});

it('confirms a valid password and returns to the intended destination', function () {
    $user = UserFactory::new()->create(['password' => 'SecurePassword1!']);

    $response = $this->actingAs($user)
        ->withSession(['url.intended' => route('profile.edit')])
        ->post(route('password.confirm.store'), ['password' => 'SecurePassword1!']);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('auth.password_confirmed_at');
});

it('rejects an invalid password without confirming it', function () {
    $user = UserFactory::new()->create();

    $response = $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'wrong-password']);

    $response->assertInvalid(['password']);
    $response->assertSessionMissing('auth.password_confirmed_at');
});

it('requires recent password confirmation for profile changes and deletion', function () {
    $user = UserFactory::new()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Changed Name',
        'email' => $user->email,
    ])->assertRedirect(route('password.confirm'));

    $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ])->assertRedirect(route('password.confirm'));
    $this->assertModelExists($user);
});
