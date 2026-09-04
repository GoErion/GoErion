<?php

declare(strict_types=1);

use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('shows the verification notice to an unverified user', function () {
    $user = UserFactory::new()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verify your email address');
});

it('prevents an unverified user from accessing onboarding and the erp', function () {
    $user = UserFactory::new()->unverified()->create();
    $company = CompanyFactory::new()->create();
    $user->companies()->attach($company);

    $this->actingAs($user)->get(route('company.create'))
        ->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(route('home', $company))
        ->assertRedirect(route('verification.notice'));
});

it('verifies a valid signed request and resolves the destination', function () {
    Event::fake([Verified::class]);
    $user = UserFactory::new()->unverified()->create();
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(route('company.create'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('rejects a verification request for a different authenticated user', function () {
    $user = UserFactory::new()->unverified()->create();
    $otherUser = UserFactory::new()->unverified()->create();
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
    );

    $this->actingAs($otherUser)->get($verificationUrl)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects malformed and expired verification links', function (string $verificationUrl) {
    $user = UserFactory::new()->unverified()->create();

    $this->actingAs($user)->get($verificationUrl)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->with([
    'malformed signature' => fn (): string => route('verification.verify', ['id' => 1, 'hash' => 'invalid']),
    'expired signature' => function (): string {
        $user = UserFactory::new()->unverified()->create();

        return URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );
    },
]);

it('resends the verification notification', function () {
    Notification::fake();
    $user = UserFactory::new()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, VerifyEmail::class);
});
