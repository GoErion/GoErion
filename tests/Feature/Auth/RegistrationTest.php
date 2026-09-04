<?php

declare(strict_types=1);

use App\Models\Auth\User;
use Database\Factories\UserFactory;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('registers authenticates and asks a new user to verify their email', function () {
    Event::fake([Registered::class]);

    $response = $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => ' ADA@EXAMPLE.COM ',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
    ]);

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();
    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticatedAs($user);
    expect($user->email_verified_at)->toBeNull();
    expect(Hash::check('SecurePassword1!', $user->password))->toBeTrue();
    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
});

it('sends an email verification notification after registration', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
    ]);

    $user = User::query()->where('email', 'grace@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects duplicate email addresses regardless of case', function () {
    UserFactory::new()->create(['email' => 'owner@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Another Owner',
        'email' => 'OWNER@EXAMPLE.COM',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
    ]);

    $response->assertInvalid(['email']);
    expect(User::query()->count())->toBe(1);
});

it('validates registration fields and never flashes passwords', function () {
    $response = $this->post(route('register.store'), [
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response
        ->assertInvalid(['name', 'email', 'password'])
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
});

it('does not mass assign security-sensitive registration fields', function () {
    $this->post(route('register.store'), [
        'name' => 'Secure User',
        'email' => 'secure@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
        'email_verified_at' => now(),
        'remember_token' => 'attacker-controlled',
    ]);

    $user = User::query()->where('email', 'secure@example.com')->firstOrFail();
    expect($user->email_verified_at)->toBeNull();
    expect($user->getRememberToken())->not->toBe('attacker-controlled');
});

it('regenerates the session when registering', function () {
    $sessionId = session()->getId();

    $this->post(route('register.store'), [
        'name' => 'Session User',
        'email' => 'session@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
    ]);

    expect(session()->getId())->not->toBe($sessionId);
});
