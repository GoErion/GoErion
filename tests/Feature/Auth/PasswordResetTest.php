<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('returns the same public response for known and unknown email addresses', function () {
    Notification::fake();
    $user = UserFactory::new()->create(['email' => 'known@example.com']);

    $knownResponse = $this->post(route('password.email'), ['email' => $user->email]);
    $unknownResponse = $this->post(route('password.email'), ['email' => 'unknown@example.com']);

    $knownResponse->assertSessionHas('status', 'If an account exists for that email address, we have sent a password reset link.');
    $unknownResponse->assertSessionHas('status', 'If an account exists for that email address, we have sent a password reset link.');
    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets a password with a valid token and dispatches the standard event', function () {
    Notification::fake();
    Event::fake([PasswordReset::class]);
    $user = UserFactory::new()->create([
        'email' => 'member@example.com',
        'password' => 'OldPassword1!',
    ]);
    $originalRememberToken = $user->getRememberToken();
    $resetToken = null;

    $this->post(route('password.email'), ['email' => $user->email]);
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$resetToken): bool {
        $resetToken = $notification->token;

        return true;
    });

    $response = $this->post(route('password.update'), [
        'token' => $resetToken,
        'email' => ' MEMBER@EXAMPLE.COM ',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
    $user->refresh();
    expect(Hash::check('NewPassword1!', $user->password))->toBeTrue();
    expect($user->getRememberToken())->not->toBe($originalRememberToken);
    Event::assertDispatched(PasswordReset::class);
});

it('rejects an invalid reset token without changing the password', function () {
    $user = UserFactory::new()->create(['password' => 'OldPassword1!']);

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertInvalid(['email']);
    expect(Hash::check('OldPassword1!', $user->fresh()->password))->toBeTrue();
});

it('applies the default password policy to resets', function () {
    $user = UserFactory::new()->create();

    $this->post(route('password.update'), [
        'token' => 'token',
        'email' => $user->email,
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertInvalid(['password']);
});

it('throttles forgotten password submissions', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.email'), ['email' => 'unknown@example.com']);
    }

    $this->post(route('password.email'), ['email' => 'unknown@example.com'])
        ->assertTooManyRequests();
});

it('throttles password reset submissions', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'member@example.com',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);
    }

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => 'member@example.com',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertTooManyRequests();
});
