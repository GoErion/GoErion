<?php

declare(strict_types=1);

use App\Actions\Auth\DeleteAccount;
use App\Actions\Company\AcceptInvitation;
use App\Actions\Company\CancelInvitation;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\CreateInvitation;
use App\Actions\Company\DeleteRole;
use App\Actions\Company\PromoteCompanyOwner;
use App\Actions\Company\ResendInvitation;
use App\Enums\CompanyAccessEventType;
use App\Enums\InvitationStatus;
use App\Models\Auth\Role;
use App\Models\Company\CompanyAccessEvent;
use App\Models\Company\CompanyInvitation;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function invitationCompany(): array
{
    $actor = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Invitation Company'], $actor);
    $roles = RoleFactory::new()->count(2)->create(['company_id' => $company->id]);
    $recipient = UserFactory::new()->create();

    return [$company, $actor, $roles, $recipient];
}

it('creates a normalized multi-role invitation with private high entropy credentials and history', function () {
    $this->travelTo(now()->startOfSecond());
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $result = app(CreateInvitation::class)->handle($company, $actor, '  '.strtoupper($recipient->email).'  ', $roles->modelKeys());
    $invitation = $result->invitation->fresh();
    $token = $result->takeToken();

    expect(strlen(base64_decode(strtr($token, '-_', '+/'))))->toBe(32);
    expect($token)->toMatch('/^[A-Za-z0-9_-]{43}$/');
    expect($invitation->email)->toBe($recipient->email);
    expect($invitation->getRouteKey())->toBe($invitation->uuid);
    expect($invitation->resolveRouteBinding($invitation->uuid)->id)->toBe($invitation->id);
    expect($invitation->token_hash)->not->toBe($token)->toBe(hash('sha256', $token));
    expect($invitation->toArray())->not->toHaveKeys(['token', 'token_hash', 'active_key', 'status']);
    expect($invitation->toJson())->not->toContain($token)->not->toContain($invitation->token_hash)->not->toContain($invitation->active_key);
    expect($invitation->expires_at->equalTo(now()->addDays(7)))->toBeTrue();
    expect($invitation->isPending())->toBeTrue();
    expect($invitation->roles)->toHaveCount(2);
    expect($invitation->invitedBy->is($actor))->toBeTrue();
    expect(DB::table('company_invitation_roles')->where('invitation_id', $invitation->id)->pluck('company_id')->unique()->all())->toBe([$company->id]);
    expect(fn () => $result->takeToken())->toThrow(LogicException::class);
    expect(fn () => serialize($result))->toThrow(LogicException::class);
    expect(fn () => json_encode($result, JSON_THROW_ON_ERROR))->toThrow(LogicException::class);
    $event = $company->accessEvents()->sole();
    expect($event->event_type)->toBe(CompanyAccessEventType::InvitationCreated);
    expect($event->metadata['roles'])->toHaveCount(2);
    expect(json_encode($event->metadata))->not->toContain($token)->not->toContain($invitation->token_hash)->not->toContain($recipient->email);
    expect($event->metadata['invitation_uuid'])->toBe($invitation->uuid);
});

it('enforces active uniqueness in the database and releases observed expiry atomically', function () {
    $this->travelTo(now()->startOfSecond());
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $first = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys())->invitation;
    expect(fn () => app(CreateInvitation::class)->handle($company, $actor, strtoupper($recipient->email), $roles->modelKeys()))->toThrow(DomainException::class);
    expect(fn () => DB::table('company_invitations')->insert([
        'uuid' => (string) Str::uuid(), 'company_id' => $company->id, 'email' => $recipient->email,
        'active_key' => $first->active_key, 'expires_at' => now()->addDay(),
    ]))->toThrow(QueryException::class);
    $this->travel(7)->days();
    expect($first->fresh()->lifecycle())->toBe(InvitationStatus::Expired);
    $second = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys())->invitation;
    expect($first->fresh()->active_key)->toBeNull();
    expect($second->active_key)->toBe($first->active_key);
    expect($company->invitations()->count())->toBe(2);
    expect($company->accessEvents()->count())->toBe(2);
});

it('rejects empty foreign protected and nonexistent roles without writes', function (string $kind) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $ids = match ($kind) {
        'empty' => [], 'foreign' => [RoleFactory::new()->create()->id],
        'owner' => [$company->ownerRole()->sole()->id],
        'system' => [RoleFactory::new()->create(['company_id' => $company->id, 'is_system' => true])->id],
        'missing' => [99999999],
    };
    expect(fn () => app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $ids))->toThrow(DomainException::class);
    expect($company->invitations()->count())->toBe(0);
    expect($company->accessEvents()->count())->toBe(0);
})->with(['empty', 'foreign', 'owner', 'system', 'missing']);

it('rejects existing members and invalid email before persistence', function (string $kind) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $email = $kind === 'member' ? ' '.strtoupper($actor->email).' ' : 'not-an-email';
    expect(fn () => app(CreateInvitation::class)->handle($company, $actor, $email, $roles->modelKeys()))->toThrow(DomainException::class);
    expect($company->invitations()->count())->toBe(0);
})->with(['member', 'invalid']);

it('rotates only the digest on resend and invalidates the old and changed tokens', function () {
    $this->travelTo(now()->startOfSecond());
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $first = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $old = $first->takeToken();
    $key = $first->invitation->active_key;
    $this->travel(2)->days();
    $second = app(ResendInvitation::class)->handle($company, $first->invitation, $actor);
    $token = $second->takeToken();
    expect($token)->not->toBe($old);
    expect($second->invitation->active_key)->toBe($key);
    expect($second->invitation->expires_at->equalTo(now()->addDays(7)))->toBeTrue();
    foreach ([$old, $token.'x', '', 'invalid'] as $invalid) {
        expect(fn () => app(AcceptInvitation::class)->handle($invalid, $recipient))->toThrow(DomainException::class, 'This invitation is not available.');
    }
    expect(app(AcceptInvitation::class)->handle($token, $recipient)->is($company))->toBeTrue();
    expect($company->accessEvents()->pluck('event_type')->all())->toBe([
        CompanyAccessEventType::InvitationCreated, CompanyAccessEventType::InvitationResent,
        CompanyAccessEventType::InvitationAccepted, CompanyAccessEventType::MemberJoined,
    ]);
});

it('accepts atomically with company-aware assignment metadata and narrowly idempotent replay', function () {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $token = $delivery->takeToken();
    $action = app(AcceptInvitation::class);
    expect($action->handle($token, $recipient)->is($company))->toBeTrue();
    expect($action->handle($token, $recipient)->is($company))->toBeTrue();
    $invitation = $delivery->invitation->fresh();
    expect($invitation->lifecycle())->toBe(InvitationStatus::Accepted);
    expect($invitation->acceptedBy->is($recipient))->toBeTrue();
    expect($invitation->active_key)->toBeNull();
    $pivots = DB::table('user_roles')->where('user_id', $recipient->id)->get();
    expect($pivots)->toHaveCount(2);
    foreach ($pivots as $pivot) {
        expect($pivot->company_id)->toBe($company->id);
        expect($pivot->assigned_by)->toBe($actor->id);
        expect($pivot->created_at)->not->toBeNull();
    }
    expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$actor->id]);
    expect($company->accessEvents()->count())->toBe(3);
    $recipient->roles()->detach($roles->first());
    $action->handle($token, $recipient);
    expect($recipient->roles()->count())->toBe(1);
    $company->users()->detach($recipient);
    expect(fn () => $action->handle($token, $recipient))->toThrow(DomainException::class);
    expect($recipient->roles()->count())->toBe(0);
    expect($company->accessEvents()->count())->toBe(3);
});

it('rejects invalid recipients or changed eligibility without granting roles', function (string $kind) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    match ($kind) {
        'unverified' => $recipient->forceFill(['email_verified_at' => null])->save(),
        'mismatch' => $recipient->update(['email' => 'other@example.com']),
        'member' => $company->users()->attach($recipient),
        'protected' => $roles->first()->update(['is_system' => true]),
        'empty' => $delivery->invitation->roles()->detach(),
        'deleted-account' => app(DeleteAccount::class)->handle($recipient),
    };
    expect(fn () => app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient))->toThrow(DomainException::class);
    expect($recipient->roles()->count())->toBe(0);
    expect($delivery->invitation->fresh()->accepted_at)->toBeNull();
    expect($company->accessEvents()->count())->toBe(1);
})->with(['unverified', 'mismatch', 'member', 'protected', 'empty', 'deleted-account']);

it('cancels without deleting history and rejects repeated cancellation or acceptance', function () {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    app(CancelInvitation::class)->handle($company, $delivery->invitation, $actor);
    $invitation = $delivery->invitation->fresh();
    expect($invitation->lifecycle())->toBe(InvitationStatus::Cancelled);
    expect($invitation->cancelledBy->is($actor))->toBeTrue();
    expect($invitation->active_key)->toBeNull();
    expect($invitation->roles()->count())->toBe(2);
    expect(fn () => app(CancelInvitation::class)->handle($company, $invitation, $actor))->toThrow(DomainException::class);
    expect(fn () => app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient))->toThrow(DomainException::class);
    expect($company->accessEvents()->count())->toBe(2);
});

it('rejects lifecycle mutations at the exact expiry boundary without false history', function (string $operation) {
    $this->travelTo(now()->startOfSecond());
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $this->travel(7)->days();
    expect(fn () => match ($operation) {
        'accept' => app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient),
        'cancel' => app(CancelInvitation::class)->handle($company, $delivery->invitation, $actor),
        'resend' => app(ResendInvitation::class)->handle($company, $delivery->invitation, $actor),
    })->toThrow(DomainException::class);
    expect($delivery->invitation->fresh()->lifecycle())->toBe(InvitationStatus::Expired);
    expect($company->accessEvents()->count())->toBe(1);
})->with(['accept', 'cancel', 'resend']);

it('rechecks stale actor permissions for every management action', function (string $operation) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $actor->load('roles.permissions', 'companies');
    $company->ownerRole()->sole()->permissions()->detach();
    expect(fn () => match ($operation) {
        'create' => app(CreateInvitation::class)->handle($company, $actor, 'new@example.com', $roles->modelKeys()),
        'cancel' => app(CancelInvitation::class)->handle($company, $delivery->invitation, $actor),
        'resend' => app(ResendInvitation::class)->handle($company, $delivery->invitation, $actor),
    })->toThrow(AuthorizationException::class);
    expect($company->accessEvents()->count())->toBe(1);
    expect($delivery->invitation->fresh()->token_hash)->toBe($delivery->invitation->token_hash);
})->with(['create', 'cancel', 'resend']);

it('rolls back every lifecycle mutation when append-only history fails', function (string $operation) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $before = $delivery->invitation->fresh()->getAttributes();
    Event::listen('eloquent.creating: '.CompanyAccessEvent::class, fn () => throw new RuntimeException('History unavailable.'));
    try {
        expect(fn () => match ($operation) {
            'create' => app(CreateInvitation::class)->handle($company, $actor, 'new@example.com', $roles->modelKeys()),
            'cancel' => app(CancelInvitation::class)->handle($company, $delivery->invitation, $actor),
            'resend' => app(ResendInvitation::class)->handle($company, $delivery->invitation, $actor),
            'accept' => app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient),
        })->toThrow(RuntimeException::class, 'History unavailable.');
    } finally {
        Event::forget('eloquent.creating: '.CompanyAccessEvent::class);
    }
    expect($delivery->invitation->fresh()->getAttributes())->toBe($before);
    expect($company->invitations()->count())->toBe(1);
    expect($company->users()->whereKey($recipient)->exists())->toBeFalse();
    expect($recipient->roles()->count())->toBe(0);
    expect($company->accessEvents()->count())->toBe(1);
})->with(['create', 'cancel', 'resend', 'accept']);

it('enforces both tenant boundaries and uniqueness on the invitation pivot', function (string $kind) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $invitation = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys())->invitation;
    $otherRole = RoleFactory::new()->create();
    $row = match ($kind) {
        'foreign-role' => ['invitation_id' => $invitation->id, 'company_id' => $company->id, 'role_id' => $otherRole->id],
        'foreign-invitation' => ['invitation_id' => $invitation->id, 'company_id' => $otherRole->company_id, 'role_id' => $otherRole->id],
        'duplicate' => ['invitation_id' => $invitation->id, 'company_id' => $company->id, 'role_id' => $roles->first()->id],
    };
    expect(fn () => DB::table('company_invitation_roles')->insert($row))->toThrow(QueryException::class);
    expect($invitation->roles()->count())->toBe(2);
})->with(['foreign-role', 'foreign-invitation', 'duplicate']);

it('blocks role deletion only for active invitations and preserves historical role names', function (string $state) {
    $this->travelTo(now()->startOfSecond());
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $invitation = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys())->invitation;
    $role = $roles->first();
    if ($state === 'pending') {
        expect(fn () => app(DeleteRole::class)->handle($company, $role, $actor))->toThrow(DomainException::class);
        expect($invitation->roles()->count())->toBe(2);
    } else {
        if ($state === 'cancelled') {
            app(CancelInvitation::class)->handle($company, $invitation, $actor);
        } else {
            $this->travel(7)->days();
        }
        app(DeleteRole::class)->handle($company, $role, $actor);
        expect($invitation->roles()->count())->toBe(1);
        expect($company->accessEvents()->oldest('id')->first()->metadata['roles'][0]['uuid'])->toBe($role->uuid);
    }
})->with(['pending', 'cancelled', 'expired']);

it('retains invitations when the inviter account is deleted', function () {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $invitation = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys())->invitation;
    $replacement = UserFactory::new()->create();
    $company->users()->attach($replacement);
    app(PromoteCompanyOwner::class)->handle($company, $replacement, $actor);
    app(DeleteAccount::class)->handle($actor);
    expect($invitation->fresh()->invited_by)->toBeNull();
    expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$replacement->id]);
});

it('rejects Eloquent physical deletion', function () {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $invitation = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys())->invitation;
    foreach ([fn () => $invitation->delete(), fn () => $invitation->deleteQuietly(), fn () => $invitation->forceDelete(), fn () => CompanyInvitation::destroy($invitation->id), fn () => $company->invitations()->delete(), fn () => CompanyInvitation::query()->forceDelete(), fn () => CompanyInvitation::query()->truncate()] as $delete) {
        expect($delete)->toThrow(LogicException::class);
    }
    expect($invitation->fresh())->not->toBeNull();
});

it('revalidates recipient and roles when resending', function (string $kind) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    match ($kind) {
        'member' => $company->users()->attach($recipient),
        'system-role' => $roles->first()->update(['is_system' => true]),
        'no-roles' => $delivery->invitation->roles()->detach(),
        'cancelled' => app(CancelInvitation::class)->handle($company, $delivery->invitation, $actor),
        'accepted' => app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient),
    };
    $count = $company->accessEvents()->count();
    expect(fn () => app(ResendInvitation::class)->handle($company, $delivery->invitation, $actor))->toThrow(DomainException::class);
    expect($delivery->invitation->fresh()->token_hash)->toBe($delivery->invitation->token_hash);
    expect($company->accessEvents()->count())->toBe($count);
})->with(['member', 'system-role', 'no-roles', 'cancelled', 'accepted']);

it('does not let a route company manage another company invitation', function (string $operation) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other Company'], $actor);
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    expect(fn () => match ($operation) {
        'cancel' => app(CancelInvitation::class)->handle($other, $delivery->invitation, $actor),
        'resend' => app(ResendInvitation::class)->handle($other, $delivery->invitation, $actor),
    })->toThrow(ModelNotFoundException::class);
    expect($delivery->invitation->fresh()->isPending())->toBeTrue();
    expect($other->accessEvents()->count())->toBe(0);
})->with(['cancel', 'resend']);

it('rolls back membership and acceptance when the second acceptance event fails', function () {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    Event::listen('eloquent.creating: '.CompanyAccessEvent::class, function (CompanyAccessEvent $event): void {
        if ($event->event_type === CompanyAccessEventType::MemberJoined) {
            throw new RuntimeException('Membership history unavailable.');
        }
    });
    try {
        expect(fn () => app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient))->toThrow(RuntimeException::class);
    } finally {
        Event::forget('eloquent.creating: '.CompanyAccessEvent::class);
    }
    expect($company->users()->whereKey($recipient)->exists())->toBeFalse();
    expect($recipient->roles()->count())->toBe(0);
    expect($delivery->invitation->fresh()->isPending())->toBeTrue();
    expect($company->accessEvents()->count())->toBe(1);
});

it('retains accepted history after role and recipient account deletion', function () {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $token = $delivery->takeToken();
    app(AcceptInvitation::class)->handle($token, $recipient);
    app(DeleteAccount::class)->handle($recipient);
    app(DeleteRole::class)->handle($company, $roles->first(), $actor);
    expect($delivery->invitation->fresh()->accepted_by)->toBeNull();
    expect($delivery->invitation->fresh()->lifecycle())->toBe(InvitationStatus::Accepted);
    expect($delivery->invitation->roles()->count())->toBe(1);
    expect($company->accessEvents()->where('event_type', CompanyAccessEventType::InvitationAccepted)->sole()->metadata['roles'])->toHaveCount(2);
    expect(fn () => app(AcceptInvitation::class)->handle($token, $recipient))->toThrow(DomainException::class);
});

it('honors configured lifetime and normalizes the freshly resolved accepting email', function () {
    $this->travelTo(now()->startOfSecond());
    config()->set('invitations.lifetime_days', 10);
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    expect($delivery->invitation->expires_at->equalTo(now()->addDays(10)))->toBeTrue();
    DB::table('users')->where('id', $recipient->id)->update(['email' => ' '.strtoupper($recipient->email).' ']);
    expect(app(AcceptInvitation::class)->handle($delivery->takeToken(), $recipient)->is($company))->toBeTrue();
});

it('rejects malformed role identifiers without coercing them into eligible roles', function (string $kind) {
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $ids = match ($kind) {
        'boolean' => [true],
        'float' => [(float) $roles->first()->id],
        'numeric-string' => [(string) $roles->first()->id],
        'nested' => [[$roles->first()->id]],
        'zero' => [0],
    };
    expect(fn () => app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $ids))->toThrow(DomainException::class, 'This invitation is not available.');
    expect($company->invitations()->count())->toBe(0);
    expect($company->accessEvents()->count())->toBe(0);
})->with(['boolean', 'float', 'numeric-string', 'nested', 'zero']);

it('does not revive an invitation that expires while resend revalidates roles', function () {
    $this->travelTo(now()->startOfSecond());
    [$company, $actor, $roles, $recipient] = invitationCompany();
    $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
    $expiry = $delivery->invitation->expires_at;
    Event::listen('eloquent.retrieved: '.Role::class, function () use ($expiry): void {
        $this->travelTo($expiry);
    });
    try {
        expect(fn () => app(ResendInvitation::class)->handle($company, $delivery->invitation, $actor))->toThrow(DomainException::class);
    } finally {
        Event::forget('eloquent.retrieved: '.Role::class);
    }
    expect($delivery->invitation->fresh()->token_hash)->toBe($delivery->invitation->token_hash);
    expect($company->accessEvents()->count())->toBe(1);
});
