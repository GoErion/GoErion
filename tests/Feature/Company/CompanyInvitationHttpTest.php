<?php

declare(strict_types=1);

use App\Actions\Company\AcceptInvitation;
use App\Actions\Company\CancelInvitation;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\CreateInvitation;
use App\Actions\Company\InvitationDelivery;
use App\Actions\Company\ResendInvitation;
use App\Mail\CompanyInvitationMail;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use App\Services\Company\InvitationClaim;
use App\Services\Company\SendCompanyInvitation;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Testing\Fakes\MailFake;
use Psr\Log\AbstractLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Exercise real commits before synchronous delivery, outside the test harness transaction.
    DB::commit();
    Mail::fake();
    Notification::fake();
    Queue::fake();
    $this->withoutVite();
    $this->actor = UserFactory::new()->create();
    $this->company = app(CreateCompany::class)->handle(['company_name' => 'Test workspace'], $this->actor);
    $this->roles = RoleFactory::new()->count(2)->create(['company_id' => $this->company->id]);
    $this->recipient = UserFactory::new()->create();
});

function httpInvitation(object $test): array
{
    $delivery = app(CreateInvitation::class)->handle($test->company, $test->actor, $test->recipient->email, $test->roles->modelKeys());

    return [$delivery->invitation, $delivery->takeToken()];
}

it('manages multi-role invitations and sends only committed nonqueued mail', function () {
    $this->actingAs($this->actor)->get(route('company.invitations.index', $this->company))->assertOk()->assertSee('No invitations found');
    $this->get(route('company.invitations.create', $this->company))->assertOk()->assertSee($this->roles->first()->display_name);
    $this->post(route('company.invitations.store', $this->company), ['email' => ' '.strtoupper($this->recipient->email).' ', 'role_ids' => $this->roles->modelKeys()])->assertRedirect(route('company.invitations.index', $this->company))->assertSessionHas('success');
    $invitation = $this->company->invitations()->sole();
    expect($invitation->email)->toBe($this->recipient->email);
    expect($invitation->roles()->count())->toBe(2);
    expect($invitation->last_sent_at)->not->toBeNull();
    Mail::assertSent(CompanyInvitationMail::class, function ($mail) use ($invitation) {
        expect(DB::transactionLevel())->toBe(0);
        $url = $mail->content()->with['entryUrl'];
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        expect($invitation->token_hash)->toBe(hash('sha256', $query['token']));
        expect(json_encode(session()->all()))->not->toContain($query['token']);
        expect(json_encode(DB::table('company_invitations')->get()))->not->toContain($query['token']);
        expect(fn () => serialize($mail))->toThrow(LogicException::class);
        expect($mail->render())->toContain('Review invitation')->not->toContain($invitation->token_hash);
        $mail->assertSeeInText('Review invitation');
        expect(fn () => json_encode($mail, JSON_THROW_ON_ERROR))->toThrow(LogicException::class);

        return true;
    });
    $oldDigest = $invitation->token_hash;
    $this->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertSessionHas('success');
    expect($invitation->fresh()->token_hash)->not->toBe($oldDigest);
    $this->delete(route('company.invitations.cancel', [$this->company, $invitation->uuid]))->assertSessionHas('success');
    expect($invitation->fresh()->cancelled_at)->not->toBeNull();
    expect($this->company->accessEvents()->count())->toBe(3);
    Mail::assertSentCount(2);
    Mail::assertNothingQueued();
    Queue::assertNothingPushed();
});

it('rejects unauthorized management and foreign invitation UUIDs', function () {
    [$invitation] = httpInvitation($this);
    $this->actingAs($this->recipient)->get(route('company.invitations.index', $this->company))->assertForbidden();
    $this->get(route('company.invitations.create', $this->company))->assertForbidden();
    $this->post(route('company.invitations.store', $this->company))->assertForbidden();
    $this->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertForbidden();
    $this->delete(route('company.invitations.cancel', [$this->company, $invitation->uuid]))->assertForbidden();
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other workspace'], $this->actor);
    $this->actingAs($this->actor)->post(route('company.invitations.resend', [$other, $invitation->uuid]))->assertNotFound();
    $this->delete(route('company.invitations.cancel', [$other, $invitation->uuid]))->assertNotFound();
    expect($invitation->fresh()->isPending())->toBeTrue();
    Mail::assertNothingSent();
});

it('validates assignable company roles and preserves safe old input', function (string $kind) {
    $ids = match ($kind) {
        'foreign' => [RoleFactory::new()->create()->id],
        'owner' => [$this->company->ownerRole()->sole()->id],
        'system' => [RoleFactory::new()->create(['company_id' => $this->company->id, 'is_system' => true])->id],
        'empty' => [],
    };
    $this->actingAs($this->actor)->from(route('company.invitations.create', $this->company))
        ->post(route('company.invitations.store', $this->company), ['email' => $this->recipient->email, 'role_ids' => $ids])
        ->assertSessionHasErrors()->assertSessionHasInput('email', $this->recipient->email);
    expect($this->company->invitations()->count())->toBe(0);
    Mail::assertNothingSent();
})->with(['foreign', 'owner', 'system', 'empty']);

it('replaces every matched entry credential before session storage and diagnostics', function (string $path) {
    [$invitation, $token] = httpInvitation($this);
    $before = $invitation->fresh()->getAttributes();
    config(['debugbar.storage.enabled' => false]);
    app('debugbar')->enable();
    $this->app->beforeResolving(StartSession::class, function () use ($token): void {
        if (request()->routeIs('invitations.entry')) {
            expect(request()->query())->not->toHaveKey('token');
            expect(request()->fullUrl())->not->toContain($token);
            expect(app('debugbar')->isEnabled())->toBeFalse();
        }
    });
    $this->get($path.'?token='.$token)->assertRedirect(route('invitations.review'))
        ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Pragma', 'no-cache')
        ->assertSessionHas(InvitationClaim::SESSION_KEY, hash('sha256', $token));
    expect(json_encode(session()->all()))->not->toContain($token);
    expect(request()->route()->getName())->toBe('invitations.entry');
    expect(request()->fullUrl())->not->toContain($token);
    expect(session()->previousUrl())->not->toContain($token);
    expect((string) session('url.intended'))->not->toContain($token);
    expect(app('debugbar')->isEnabled())->toBeFalse();
    expect($invitation->fresh()->getAttributes())->toBe($before);
    expect($this->company->accessEvents()->count())->toBe(1);
    expect($this->company->users()->count())->toBe(1);
    $this->get(route('invitations.review'))->assertOk()->assertSee('Sign in')->assertDontSee($token)->assertDontSee($invitation->token_hash)->assertDontSee($invitation->active_key)->assertSessionHas('url.intended', route('invitations.review'));
    Mail::assertNothingSent();
    Queue::assertNothingPushed();
})->with(['/invitations/entry', '/invitations/entry/', '/invitations/%65ntry', '/%69nvitations/entry']);

it('accepts only verified matching users once and clears pending state', function () {
    [$invitation, $token] = httpInvitation($this);
    $this->get(route('invitations.entry', ['token' => $token]));
    $this->actingAs($this->recipient)->post(route('invitations.accept'), ['company_id' => 999999, 'role_ids' => [999999]])->assertRedirect(route('home', $this->company))->assertSessionHas('success')->assertSessionMissing(InvitationClaim::SESSION_KEY);
    expect($this->recipient->roles()->count())->toBe(2);
    expect($this->company->users()->count())->toBe(2);
    expect($this->company->accessEvents()->count())->toBe(3);
    expect($invitation->fresh()->active_key)->toBeNull();
    $this->withSession([InvitationClaim::SESSION_KEY => hash('sha256', $token)])->post(route('invitations.accept'))->assertRedirect(route('invitations.review'))->assertSessionHas('error');
    expect($this->company->accessEvents()->count())->toBe(3);
    expect($this->recipient->roles()->count())->toBe(2);
});

it('requires normal verification and supports switching a mismatched account', function () {
    [$invitation, $token] = httpInvitation($this);
    $this->get(route('invitations.entry', ['token' => $token]));
    $this->actingAs($this->actor)->get(route('invitations.review'))->assertSee('different email address')->assertDontSee('Accept invitation and join');
    $this->post(route('logout'), ['return_to_invitation' => 1])->assertRedirect(route('invitations.review'))->assertSessionHas(InvitationClaim::SESSION_KEY);
    $this->assertGuest();
    $this->recipient->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($this->recipient)->post(route('invitations.accept'))->assertRedirect(route('verification.notice'));
    expect($invitation->fresh()->accepted_at)->toBeNull();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $this->recipient->id, 'hash' => sha1($this->recipient->email)]);
    $this->get($url)->assertRedirect(route('invitations.review'));
    $this->post(route('invitations.accept'))->assertRedirect(route('home', $this->company));
});

it('rejects wrong email and changes in membership or role eligibility without false events', function (string $kind) {
    [$invitation, $token] = httpInvitation($this);
    $this->get(route('invitations.entry', ['token' => $token]));
    if ($kind === 'member') {
        $this->company->users()->attach($this->recipient);
    }
    if ($kind === 'protected') {
        $this->roles->first()->update(['is_system' => true]);
    }
    $user = $kind === 'email' ? $this->actor : $this->recipient;
    $this->actingAs($user)->post(route('invitations.accept'))->assertSessionHas('error')->assertSessionMissing(InvitationClaim::SESSION_KEY);
    expect($invitation->fresh()->accepted_at)->toBeNull();
    expect($this->recipient->roles()->count())->toBe(0);
    expect($this->company->accessEvents()->count())->toBe(1);
})->with(['email', 'member', 'protected']);

it('treats invalid expired cancelled rotated and consumed links generically', function (string $kind) {
    [$invitation, $token] = httpInvitation($this);
    match ($kind) {
        'invalid' => $token = str_repeat('x', 43),
        'expired' => $this->travel(8)->days(),
        'cancelled' => app(CancelInvitation::class)->handle($this->company, $invitation, $this->actor),
        'rotated' => app(ResendInvitation::class)->handle($this->company, $invitation, $this->actor),
        'consumed' => app(AcceptInvitation::class)->handle($token, $this->recipient),
    };
    $events = $this->company->accessEvents()->count();
    $this->get(route('invitations.entry', ['token' => $token]))->assertRedirect(route('invitations.review'))->assertSessionMissing(InvitationClaim::SESSION_KEY);
    $this->get(route('invitations.review'))->assertOk()->assertSee('Invitation unavailable')->assertDontSee($this->company->company_name)->assertDontSee($this->recipient->email);
    expect($this->company->accessEvents()->count())->toBe($events);
})->with(['invalid', 'expired', 'cancelled', 'rotated', 'consumed']);

it('keeps a token-free intended destination through login and registration', function (string $flow) {
    [$invitation, $token] = httpInvitation($this);
    $this->get(route('invitations.entry', ['token' => $token]));
    $this->get(route('invitations.review'))->assertSessionHas('url.intended', route('invitations.review'));
    if ($flow === 'login') {
        $this->post(route('login.store'), ['email' => $this->recipient->email, 'password' => 'password'])->assertRedirect(route('invitations.review'));
    } else {
        $email = 'new-recipient@example.test';
        $invitation->update(['email' => $email]);
        $this->post(route('register.store'), ['name' => 'New teammate', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect(route('verification.notice'));
        expect(auth()->user()->hasVerifiedEmail())->toBeFalse();
    }
    expect(json_encode(session()->all()))->not->toContain($token);
    expect($invitation->fresh()->accepted_at)->toBeNull();
})->with(['login', 'registration']);

it('filters and paginates only the authorized company invitations', function () {
    for ($i = 0; $i < 14; $i++) {
        app(CreateInvitation::class)->handle($this->company, $this->actor, 'teammate'.$i.'@example.test', $this->roles->modelKeys());
    }
    $this->actingAs($this->actor)->get(route('company.invitations.index', [$this->company, 'status' => 'pending', 'search' => 'teammate']))->assertOk()->assertViewHas('invitations', fn ($rows) => $rows->total() === 14 && $rows->count() === 12)->assertSee('status=pending', false)->assertSee('search=teammate', false);
    $this->get(route('company.invitations.index', [$this->company, 'status' => 'accepted']))->assertSee('No invitations found');
});

it('enforces named management and public rate limits without false mutations', function () {
    [$invitation, $token] = httpInvitation($this);
    $this->actingAs($this->actor);
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertRedirect();
        $this->post(route('company.invitations.store', $this->company), [])->assertSessionHasErrors();
    }
    $events = $this->company->accessEvents()->count();
    $this->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertStatus(429);
    $this->post(route('company.invitations.store', $this->company), [])->assertStatus(429);
    for ($i = 0; $i < 20; $i++) {
        $this->get(route('invitations.entry'))->assertRedirect();
    }
    $this->get(route('invitations.entry', ['token' => $token]))->assertStatus(429)->assertHeader('Referrer-Policy', 'no-referrer');
    expect(json_encode(session()->all()))->not->toContain($token);
    expect($this->company->accessEvents()->count())->toBe($events);
});

it('keeps delivery failures generic and allows resend with a rotated credential', function () {
    $fake = new MailManager($this->app);
    $failing = new class($fake) extends MailFake
    {
        public function send($view, array $data = [], $callback = null)
        {
            throw new RuntimeException('Transport failed with private message context.');
        }
    };
    Mail::swap($failing);
    $this->actingAs($this->actor)->post(route('company.invitations.store', $this->company), ['email' => $this->recipient->email, 'role_ids' => $this->roles->modelKeys()])->assertSessionHas('error')->assertSessionMissing('success');
    $invitation = $this->company->invitations()->sole();
    expect($invitation->last_sent_at)->toBeNull();
    expect($invitation->isPending())->toBeTrue();
    expect(json_encode(session()->all()))->not->toContain('private message context');
    $digest = $invitation->token_hash;
    Mail::fake();
    $this->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertSessionHas('success');
    expect($invitation->fresh()->token_hash)->not->toBe($digest);
    expect($this->company->accessEvents()->count())->toBe(2);
});

it('refuses logging transports including unsafe fallbacks before consuming delivery', function (string $mailer) {
    $delivery = app(CreateInvitation::class)->handle($this->company, $this->actor, $this->recipient->email, $this->roles->modelKeys());
    Mail::swap(new MailManager($this->app));
    config(['mail.default' => $mailer]);
    expect(app(SendCompanyInvitation::class)->handle($delivery))->toBeFalse();
    expect($delivery->takeToken())->toMatch('/\A[A-Za-z0-9_-]{43}\z/');
    expect($delivery->invitation->fresh()->last_sent_at)->toBeNull();
})->with(['log', 'array', 'failover']);

it('does not send while an outer transaction is uncommitted', function () {
    DB::beginTransaction();
    try {
        $delivery = app(CreateInvitation::class)->handle($this->company, $this->actor, $this->recipient->email, $this->roles->modelKeys());
        expect(app(SendCompanyInvitation::class)->handle($delivery))->toBeFalse();
        Mail::assertNothingSent();
    } finally {
        DB::rollBack();
    }
    expect($this->company->invitations()->count())->toBe(0);
});

it('requires CSRF and disallows GET acceptance resend and cancellation', function () {
    [$invitation, $token] = httpInvitation($this);
    enforceInvitationCsrf();
    $this->actingAs($this->actor)->post(route('company.invitations.store', $this->company))->assertStatus(419);
    $this->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertStatus(419);
    $this->delete(route('company.invitations.cancel', [$this->company, $invitation->uuid]))->assertStatus(419);
    $this->actingAs($this->recipient)->post(route('invitations.accept'))->assertStatus(419)->assertHeader('Referrer-Policy', 'no-referrer');
    $this->get(route('invitations.accept'))->assertStatus(405);
    $this->get(route('company.invitations.resend', [$this->company, $invitation->uuid]))->assertStatus(405);
    $this->get(route('company.invitations.cancel', [$this->company, $invitation->uuid]))->assertStatus(405);
    expect($this->company->accessEvents()->count())->toBe(1);
});

it('invalidates an established claim after rotation or expiry and limits acceptance', function () {
    [$invitation, $token] = httpInvitation($this);
    $this->get(route('invitations.entry', ['token' => $token]));
    $delivery = app(ResendInvitation::class)->handle($this->company, $invitation, $this->actor);
    $this->get(route('invitations.review'))->assertSee('Invitation unavailable')->assertSessionMissing(InvitationClaim::SESSION_KEY);
    $this->get(route('invitations.entry', ['token' => $delivery->takeToken()]));
    $this->travelTo($delivery->invitation->expires_at);
    $this->actingAs($this->recipient)->post(route('invitations.accept'))->assertSessionHas('error');
    for ($i = 0; $i < 9; $i++) {
        $this->post(route('invitations.accept'))->assertRedirect();
    }
    $this->post(route('invitations.accept'))->assertStatus(429);
    expect($this->company->accessEvents()->count())->toBe(2);
    expect($this->recipient->roles()->count())->toBe(0);
});

it('registers and verifies normally before returning to review and accepting', function () {
    enforceInvitationCsrf();
    [$invitation, $token] = httpInvitation($this);
    $invitation->update(['email' => 'unregistered@example.test']);
    $this->get(route('invitations.entry', ['token' => $token]));
    $this->get(route('invitations.review'));
    $this->post(route('register.store'), ['_token' => csrf_token(), 'name' => 'New teammate', 'email' => $invitation->email, 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))->assertOk()->assertSessionHas(InvitationClaim::SESSION_KEY, hash('sha256', $token));
    $user = auth()->user();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->post(route('invitations.accept'), ['_token' => csrf_token()])->assertRedirect(route('verification.notice'));
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
    $this->get($url)->assertRedirect(route('invitations.review'));
    $this->get(route('invitations.review'))->assertOk()->assertDontSee($token);
    expect(json_encode(session()->all()))->not->toContain($token);
    $this->post(route('invitations.accept'), ['_token' => csrf_token()])->assertRedirect(route('home', $this->company))->assertSessionMissing(InvitationClaim::SESSION_KEY);
    expect($user->roles()->count())->toBe(2);
});

it('redirects guests and unverified users away from management', function (string $kind) {
    if ($kind === 'unverified') {
        $this->actor->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($this->actor);
    }
    $destination = route($kind === 'guest' ? 'login' : 'verification.notice');
    $this->get(route('company.invitations.index', $this->company))->assertRedirect($destination);
    $this->get(route('company.invitations.create', $this->company))->assertRedirect($destination);
    $this->post(route('company.invitations.store', $this->company), ['email' => $this->recipient->email, 'role_ids' => $this->roles->modelKeys()])->assertRedirect($destination);
    expect($this->company->invitations()->count())->toBe(0);
    Mail::assertNothingSent();
})->with(['guest', 'unverified']);

it('renders validation errors safely for malformed nested old input', function (string $field) {
    $this->actingAs($this->actor)->from(route('company.invitations.create', $this->company))
        ->post(route('company.invitations.store', $this->company), ['email' => $field === 'email' ? ['invalid'] : $this->recipient->email, 'role_ids' => [[$this->roles->first()->id]]])->assertSessionHasErrors();
    $this->get(route('company.invitations.create', $this->company))->assertOk()->assertSee('role');
    Mail::assertNothingSent();
})->with(['email', 'roles']);

/** Mail boundary double: actual configuration resolution, exclusively in-memory delivery and logging. */
function invitationTransportManager(Application $app, ?string $failure = null): MailManager
{
    return new class($app, $failure) extends MailManager
    {
        /** @var list<string> */
        public array $resolvedTransports = [];

        public readonly AbstractLogger $logger;

        public readonly ArrayTransport $transport;

        public function __construct(Application $app, ?string $failure)
        {
            parent::__construct($app);
            $this->logger = new class extends AbstractLogger
            {
                /** @var list<array{0: string, 1: mixed}> */
                public array $records = [];

                public function log(mixed $level, string|Stringable $message, array $context = []): void
                {
                    $this->records[] = [(string) $message, isset($context['exception']) ? $context['exception']->getMessage() : $context];
                }
            };
            $this->transport = new class($failure) extends ArrayTransport
            {
                public int $calls = 0;

                public function __construct(private readonly ?string $failure)
                {
                    parent::__construct();
                }

                public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                {
                    $this->calls++;
                    if ($this->failure !== null) {
                        throw new TransportException($this->failure);
                    }

                    return parent::send($message, $envelope);
                }
            };
        }

        public function createSymfonyTransport(array $config): TransportInterface
        {
            $transport = $config['transport'] ?? '';
            $this->resolvedTransports[] = $transport;

            return match ($transport) {
                'failover' => new FailoverTransport([$this->transport], 60, $this->logger),
                'roundrobin', 'round-robin' => new RoundRobinTransport([$this->transport], 60, $this->logger),
                'log' => new LogTransport($this->logger),
                default => $this->transport,
            };
        }
    };
}

it('delivers through effective ordinary transports without contacting a real server', function (array $configuration, string $expectedTransport) {
    $delivery = app(CreateInvitation::class)->handle($this->company, $this->actor, $this->recipient->email, $this->roles->modelKeys());
    $manager = invitationTransportManager($this->app);
    Mail::swap($manager);
    config(['mail.default' => 'invitation-test', 'mail.mailers.invitation-test' => $configuration]);

    expect(app(SendCompanyInvitation::class)->handle($delivery))->toBeTrue();
    expect($manager->resolvedTransports)->toBe([$expectedTransport]);
    expect($manager->transport->messages())->toHaveCount(1);
    expect($manager->logger->records)->toBeEmpty();
    expect($delivery->invitation->fresh()->last_sent_at)->not->toBeNull();
})->with([
    'smtp' => [['transport' => 'smtp'], 'smtp'],
    'smtp URL overrides declared log' => [['transport' => 'log', 'url' => 'smtp://user:transport-secret@localhost:2525'], 'smtp'],
    'provider' => [['transport' => 'postmark'], 'postmark'],
]);

it('rejects unsafe or ambiguous effective mail URLs before consuming credentials', function (mixed $url) {
    [$invitation, $token] = httpInvitation($this);
    $delivery = new InvitationDelivery($invitation, $token);
    $manager = invitationTransportManager($this->app);
    Mail::swap($manager);
    config(['mail.default' => 'invitation-test', 'mail.mailers.invitation-test' => ['transport' => 'smtp', 'url' => $url]]);

    expect(app(SendCompanyInvitation::class)->handle($delivery))->toBeFalse();
    expect($manager->resolvedTransports)->toBeEmpty();
    expect($manager->transport->calls)->toBe(0);
    expect($manager->logger->records)->toBeEmpty();
    expect($delivery->takeToken())->toBe($token);
    expect($invitation->fresh()->last_sent_at)->toBeNull();

    $this->actingAs($this->actor)->post(route('company.invitations.resend', [$this->company, $invitation->uuid]))
        ->assertSessionHas('error')->assertSessionMissing('success');
    expect(json_encode(session()->all()))->not->toContain($token)->not->toContain('transport-secret');
    expect($manager->resolvedTransports)->toBeEmpty();
})->with([
    'log' => 'log://user:transport-secret@localhost',
    'array' => 'array://localhost',
    'null' => 'null://localhost',
    'unknown' => 'diagnostic://localhost',
    'missing scheme' => '//user:transport-secret@localhost',
    'empty' => '',
    'malformed port' => 'smtp://localhost:invalid',
    'fragment' => 'smtp://localhost#transport-secret',
    'conflicting driver' => 'smtp://localhost?driver=log',
    'disguised log' => 'log://localhost?driver=smtp',
    'nonstring' => [['smtp://localhost']],
]);

it('rejects composite transports before a credential-bearing child can execute or log', function (array $configuration) {
    [$invitation, $token] = httpInvitation($this);
    $delivery = new InvitationDelivery($invitation, $token);
    $manager = invitationTransportManager($this->app, 'Synthetic transport-secret '.$token);
    Mail::swap($manager);
    config(['mail.default' => 'invitation-test', 'mail.mailers.invitation-test' => $configuration + ['mailers' => ['smtp']]]);
    $aliases = ConfigurationUrlParser::getDriverAliases();
    ConfigurationUrlParser::addDriverAlias('mysql2', 'failover');
    try {
        expect(app(SendCompanyInvitation::class)->handle($delivery))->toBeFalse();
        expect($manager->resolvedTransports)->toBeEmpty();
        expect($manager->transport->calls)->toBe(0);
        expect($manager->logger->records)->toBeEmpty();
        expect(json_encode($manager->logger->records))->not->toContain($token)->not->toContain('transport-secret');
        expect($delivery->takeToken())->toBe($token);
        expect($invitation->fresh()->last_sent_at)->toBeNull();
    } finally {
        ConfigurationUrlParser::addDriverAlias('mysql2', $aliases['mysql2']);
    }
})->with([
    'failover' => [['transport' => 'failover']],
    'roundrobin' => [['transport' => 'roundrobin']],
    'round-robin' => [['transport' => 'round-robin']],
    'URL failover' => [['transport' => 'smtp', 'url' => 'failover://localhost']],
    'URL roundrobin' => [['transport' => 'smtp', 'url' => 'roundrobin://localhost']],
    'URL alias' => [['transport' => 'smtp', 'url' => 'mysql2://localhost']],
]);

it('keeps invitation listing total and authorization queries constant as the page grows', function (string $status) {
    [$invitation, $token] = httpInvitation($this);
    if ($status === 'accepted') {
        app(AcceptInvitation::class)->handle($token, $this->recipient);
    }
    $this->actingAs($this->actor);
    $requestQueries = function () use ($status): array {
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->get(route('company.invitations.index', [$this->company, 'status' => $status]))->assertOk();
            $queries = collect(DB::getQueryLog());

            return [
                'total' => $queries->count(),
                'authorization' => $queries->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'exists') && (str_contains($query['query'], 'company_user') || str_contains($query['query'], 'user_roles')))->count(),
            ];
        } finally {
            DB::disableQueryLog();
        }
    };
    $smallCount = $requestQueries();
    for ($i = 0; $i < 13; $i++) {
        $recipient = UserFactory::new()->create(['email' => 'growth'.$i.'@example.test']);
        $delivery = app(CreateInvitation::class)->handle($this->company, $this->actor, $recipient->email, $this->roles->modelKeys());
        if ($status === 'accepted') {
            app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient);
        }
    }
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other workspace'], $this->actor);
    $role = RoleFactory::new()->create(['company_id' => $other->id]);
    app(CreateInvitation::class)->handle($other, $this->actor, 'foreign@example.test', [$role->id]);
    $largeCount = $requestQueries();
    expect($smallCount['authorization'])->toBeGreaterThan(0);
    expect($largeCount['authorization'])->toBe($smallCount['authorization']);
    expect($largeCount['total'])->toBe($smallCount['total']);
    $this->get(route('company.invitations.index', [$this->company, 'status' => $status]))->assertViewHas('invitations', fn ($rows): bool => $rows->total() === 14 && $rows->count() === 12 && $rows->every(fn ($row): bool => $row->company_id === $this->company->id))->assertDontSee('foreign@example.test')->assertSee('status='.$status, false);
    fwrite(STDOUT, json_encode(['invitation_queries' => ['status' => $status, 'one_row' => $smallCount, 'twelve_rows' => $largeCount]])."\n");
})->with(['pending', 'accepted']);

it('filters populated cancelled and expired invitations without including other statuses', function () {
    $this->freezeTime();
    [$cancelled] = httpInvitation($this);
    app(CancelInvitation::class)->handle($this->company, $cancelled, $this->actor);
    $expired = app(CreateInvitation::class)->handle($this->company, $this->actor, 'expired@example.test', $this->roles->modelKeys())->invitation;
    $this->travelTo($expired->expires_at);
    app(CreateInvitation::class)->handle($this->company, $this->actor, 'pending@example.test', $this->roles->modelKeys());
    $this->actingAs($this->actor);
    foreach (['cancelled' => $cancelled, 'expired' => $expired] as $status => $expected) {
        $this->get(route('company.invitations.index', [$this->company, 'status' => $status]))->assertOk()
            ->assertViewHas('invitations', fn ($rows): bool => $rows->total() === 1 && $rows->first()->is($expected))
            ->assertSee($expected->email)->assertDontSee('pending@example.test');
    }
});

it('renders complete unbroken role names with wrapping in invitation cards and review', function () {
    $name = str_repeat('UnbrokenRoleName', 12);
    $this->roles->first()->update(['display_name' => $name]);
    [$invitation, $token] = httpInvitation($this);
    $index = $this->actingAs($this->actor)->get(route('company.invitations.index', $this->company))->assertOk()->assertSeeText($name);
    $this->get(route('invitations.entry', ['token' => $token]));
    $review = $this->get(route('invitations.review'))->assertOk()->assertSeeText($name);

    foreach ([$index, $review] as $response) {
        $document = new DOMDocument;
        expect($document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING))->toBeTrue();
        $labels = (new DOMXPath($document))->query('//*[text()="'.$name.'"]');
        expect($labels->length)->toBe(1);
        $classes = explode(' ', $labels->item(0)->getAttribute('class'));
        expect($classes)->toContain('min-w-0', 'max-w-full', 'break-words')->not->toContain('truncate', 'overflow-hidden');
    }
    expect($invitation->fresh()->accepted_at)->toBeNull();
});

it('shows only the route company accepted invitations and preserves the selected filter', function () {
    [$accepted, $token] = httpInvitation($this);
    app(AcceptInvitation::class)->handle($token, $this->recipient);
    app(CreateInvitation::class)->handle($this->company, $this->actor, 'pending-filter@example.test', $this->roles->modelKeys());
    $cancelled = app(CreateInvitation::class)->handle($this->company, $this->actor, 'cancelled-filter@example.test', $this->roles->modelKeys())->invitation;
    app(CancelInvitation::class)->handle($this->company, $cancelled, $this->actor);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other workspace'], $this->actor);
    $role = RoleFactory::new()->create(['company_id' => $other->id]);
    $foreignRecipient = UserFactory::new()->create(['email' => 'foreign-accepted@example.test']);
    $foreign = app(CreateInvitation::class)->handle($other, $this->actor, $foreignRecipient->email, [$role->id]);
    app(AcceptInvitation::class)->handle($foreign->takeToken(), $foreignRecipient);

    $response = $this->actingAs($this->actor)->get(route('company.invitations.index', [$this->company, 'status' => 'accepted']))
        ->assertOk()->assertSeeText($accepted->email)
        ->assertDontSeeText('pending-filter@example.test')->assertDontSeeText('cancelled-filter@example.test')->assertDontSeeText($foreignRecipient->email)
        ->assertViewHas('invitations', fn ($rows): bool => $rows->total() === 1 && $rows->first()->is($accepted))
        ->assertViewHas('filters', ['status' => 'accepted']);
    $document = new DOMDocument;
    expect($document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING))->toBeTrue();
    expect((new DOMXPath($document))->query('//select[@name="status"]/option[@value="accepted" and @selected]')->length)->toBe(1);
});

it('keeps entry failure application log messages and exception context token free', function () {
    [$invitation, $token] = httpInvitation($this);
    $logger = new class extends AbstractLogger
    {
        /** @var list<array{message: string, context: array<string, mixed>}> */
        public array $records = [];

        public function log(mixed $level, string|Stringable $message, array $context = []): void
        {
            $this->records[] = ['message' => (string) $message, 'context' => $context];
        }
    };
    Log::swap(new Logger($logger));
    $this->withSession(['url.intended' => route('invitations.review')]);
    CompanyInvitation::retrieved(function (): void {
        throw new class('Controlled downstream invitation failure.') extends RuntimeException
        {
            /** @return array{request: array{url: string, query: array<string, mixed>}} */
            public function context(): array
            {
                return ['request' => ['url' => request()->fullUrl(), 'query' => request()->query()]];
            }
        };
    });

    $this->get(route('invitations.entry', ['token' => $token]))->assertStatus(500)
        ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Pragma', 'no-cache')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->assertDontSee($token);
    expect($logger->records)->toHaveCount(1);
    $record = $logger->records[0];
    expect($record['message'])->toBe('Controlled downstream invitation failure.')->not->toContain($token);
    expect($record['context']['request'])->toBe(['url' => route('invitations.entry'), 'query' => []]);
    expect(json_encode($record['context'], JSON_THROW_ON_ERROR))->not->toContain($token);
    $exception = $record['context']['exception'];
    expect($exception)->toBeInstanceOf(RuntimeException::class);
    expect(json_encode($exception->context(), JSON_THROW_ON_ERROR))->not->toContain($token);
    expect((string) $exception)->not->toContain($token);
    expect(request()->fullUrl())->not->toContain($token);
    expect(session()->previousUrl())->toBe(route('invitations.entry'))->not->toContain($token);
    expect(session('url.intended'))->toBe(route('invitations.review'));
    expect(json_encode(session()->all(), JSON_THROW_ON_ERROR))->not->toContain($token);
    expect(DB::table('company_invitations')->where('id', $invitation->id)->value('accepted_at'))->toBeNull();
    expect($this->company->accessEvents()->count())->toBe(1);
    Mail::assertNothingSent();
});

it('isolates management rate limits by both user and company', function (string $operation) {
    [$invitation] = httpInvitation($this);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other workspace'], $this->actor);
    $otherRole = RoleFactory::new()->create(['company_id' => $other->id]);
    $otherInvitation = app(CreateInvitation::class)->handle($other, $this->actor, $this->recipient->email, [$otherRole->id])->invitation;
    $manager = UserFactory::new()->create();
    $this->company->users()->attach($manager);
    $manager->roles()->attach($this->company->ownerRole()->sole(), ['company_id' => $this->company->id, 'assigned_by' => $this->actor->id]);
    $url = fn (Company $company, CompanyInvitation $invitation): string => $operation === 'store' ? route('company.invitations.store', $company) : route('company.invitations.resend', [$company, $invitation->uuid]);
    $this->actingAs($this->actor);
    for ($i = 0; $i < 5; $i++) {
        $this->post($url($this->company, $invitation))->assertRedirect();
    }
    $this->post($url($this->company, $invitation))->assertStatus(429);
    $this->post($url($other, $otherInvitation))->assertRedirect();
    $this->actingAs($manager)->post($url($this->company, $invitation))->assertRedirect();
})->with(['store', 'resend']);

it('returns pending claims to review from every already-verified verification branch', function (string $branch) {
    [$invitation, $token] = httpInvitation($this);
    $this->get(route('invitations.entry', ['token' => $token]));
    $this->actingAs($this->recipient);
    $response = match ($branch) {
        'callback' => $this->get(URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $this->recipient->id, 'hash' => sha1($this->recipient->email)])),
        'prompt' => $this->get(route('verification.notice')),
        'notification' => $this->post(route('verification.send')),
    };
    $response->assertRedirect(route('invitations.review'))->assertSessionHas(InvitationClaim::SESSION_KEY, hash('sha256', $token));
    expect(json_encode(session()->all()))->not->toContain($token);
    expect($invitation->fresh()->accepted_at)->toBeNull();
    Notification::assertNothingSent();
})->with(['callback', 'prompt', 'notification']);

function enforceInvitationCsrf(): void
{
    app()->bind(PreventRequestForgery::class, function (Application $app) {
        return new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };
    });
}

it('protects equivalent entry URLs on throttled and server-error responses', function (string $path, int $status) {
    [$invitation, $token] = httpInvitation($this);
    $attributes = (array) DB::table('company_invitations')->where('id', $invitation->id)->first();
    config(['debugbar.storage.enabled' => false]);
    app('debugbar')->enable();
    if ($status === 429) {
        RateLimiter::for('invitation-entry', fn () => Limit::perMinute(1)->by('privacy-test'));
        $this->get($path)->assertRedirect(route('invitations.review'));
    } else {
        CompanyInvitation::retrieved(function (): void {
            throw new RuntimeException('Synthetic invitation lookup failure.');
        });
    }

    $this->get($path.'?token='.$token)->assertStatus($status)
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Pragma', 'no-cache')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertDontSee($token);
    expect(request()->route()->getName())->toBe('invitations.entry');
    expect(request()->query())->not->toHaveKey('token');
    expect(request()->fullUrl())->not->toContain($token);
    expect(json_encode(session()->all()))->not->toContain($token);
    expect(app('debugbar')->isEnabled())->toBeFalse();
    expect((array) DB::table('company_invitations')->where('id', $invitation->id)->first())->toBe($attributes);
    expect($this->company->users()->count())->toBe(1);
    expect($this->company->accessEvents()->count())->toBe(1);
    Mail::assertNothingSent();
})->with(['/invitations/entry', '/invitations/entry/', '/invitations/%65ntry', '/%69nvitations/entry'])->with([429, 500]);

it('isolates public invitation limits by their account and network keys', function () {
    $this->actingAs($this->recipient);
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('invitations.accept'))->assertRedirect(route('invitations.review'));
    }
    $this->post(route('invitations.accept'))->assertStatus(429);
    $this->actingAs($this->actor)->post(route('invitations.accept'))->assertRedirect(route('invitations.review'));
    $this->actingAs($this->recipient)->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->post(route('invitations.accept'))->assertRedirect(route('invitations.review'));
    for ($i = 0; $i < 20; $i++) {
        $this->get(route('invitations.entry'))->assertRedirect(route('invitations.review'));
    }
    $this->get(route('invitations.entry'))->assertStatus(429);
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])->get(route('invitations.entry'))->assertRedirect(route('invitations.review'));
    expect($this->company->users()->count())->toBe(1);
    expect($this->company->accessEvents()->count())->toBe(0);
    Mail::assertNothingSent();
});
