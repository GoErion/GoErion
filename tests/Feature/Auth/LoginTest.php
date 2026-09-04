<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('authenticates with a normalized email address and regenerates the session', function () {
    $user = UserFactory::new()->create([
        'email' => 'member@example.com',
        'password' => 'SecurePassword1!',
    ]);
    $sessionId = session()->getId();

    $response = $this->post(route('login.store'), [
        'email' => ' MEMBER@EXAMPLE.COM ',
        'password' => 'SecurePassword1!',
    ]);

    $response->assertRedirect(route('company.create'));
    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($sessionId);
});

it('offers remembered authentication when requested', function () {
    $user = UserFactory::new()->create(['password' => 'SecurePassword1!']);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'SecurePassword1!',
        'remember' => true,
    ]);

    $response->assertCookie(Auth::guard()->getRecallerName());
});

it('throttles repeated failed attempts by normalized email and ip address', function () {
    Event::fake([Lockout::class]);
    $user = UserFactory::new()->create(['email' => 'member@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'email' => $attempt % 2 === 0 ? 'MEMBER@EXAMPLE.COM' : 'member@example.com',
            'password' => 'wrong-password',
        ])->assertInvalid(['email']);
    }

    $this->post(route('login.store'), [
        'email' => 'member@example.com',
        'password' => 'wrong-password',
    ])->assertInvalid(['email']);
    Event::assertDispatched(Lockout::class);
});

it('clears failed attempts after successful authentication', function () {
    $user = UserFactory::new()->create([
        'email' => 'member@example.com',
        'password' => 'SecurePassword1!',
    ]);
    $key = Str::transliterate('member@example.com|127.0.0.1');

    foreach (range(1, 4) as $attempt) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    expect(RateLimiter::attempts($key))->toBe(4);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'SecurePassword1!',
    ]);

    expect(RateLimiter::attempts($key))->toBe(0);
});

it('honors an internal intended destination', function () {
    $user = UserFactory::new()->create(['password' => 'SecurePassword1!']);

    $response = $this->withSession(['url.intended' => '/profile'])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'SecurePassword1!',
    ]);

    $response->assertRedirect('/profile');
});

it('rejects an external intended destination', function () {
    $user = UserFactory::new()->create(['password' => 'SecurePassword1!']);

    $response = $this->withSession(['url.intended' => 'https://example.net/phishing'])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'SecurePassword1!',
    ]);

    $response->assertRedirect(route('company.create'));
});
