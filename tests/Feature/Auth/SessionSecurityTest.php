<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('invalidates the session and regenerates the csrf token during logout', function () {
    $user = UserFactory::new()->create();
    $this->actingAs($user);
    session()->put('sensitive-state', 'value');
    $sessionId = session()->getId();
    $csrfToken = session()->token();

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('marketing'));
    $this->assertGuest();
    $response->assertSessionMissing('sensitive-state');
    expect(session()->getId())->not->toBe($sessionId);
    expect(session()->token())->not->toBe($csrfToken);
});

it('changes the password and invalidates other authenticated sessions', function () {
    Event::fake([OtherDeviceLogout::class]);
    $user = UserFactory::new()->create(['password' => 'OldPassword1!']);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'OldPassword1!',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

    $response->assertRedirect(route('profile.edit'));
    expect(Hash::check('NewPassword1!', $user->fresh()->password))->toBeTrue();
    Event::assertDispatched(OtherDeviceLogout::class);
});

it('logs out a session whose stored password hash is stale', function () {
    $user = UserFactory::new()->create(['password' => 'OldPassword1!']);
    $oldPasswordHash = $user->password;
    $user->forceFill(['password' => 'NewPassword1!'])->save();

    $response = $this->actingAs($user)
        ->withSession(['password_hash_web' => $oldPasswordHash])
        ->get(route('company.create'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

it('requires confirmation and reverification when changing an email address', function () {
    Notification::fake();
    $user = UserFactory::new()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'new-address@example.com',
        ]);

    $response->assertRedirect(route('verification.notice'));
    $user->refresh();
    expect($user->email)->toBe('new-address@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});
