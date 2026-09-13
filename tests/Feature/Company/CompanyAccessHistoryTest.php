<?php

declare(strict_types=1);

use App\Actions\Company\AssignMemberRoles;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\CreateRole;
use App\Actions\Company\DeleteRole;
use App\Actions\Company\DemoteCompanyOwner;
use App\Actions\Company\LeaveCompany;
use App\Actions\Company\PromoteCompanyOwner;
use App\Actions\Company\RemoveCompanyMember;
use App\Actions\Company\UpdateRole;
use App\Enums\CompanyAccessEventType;
use App\Enums\CompanyPermission;
use App\Models\Auth\Permission;
use App\Models\Company\CompanyAccessEvent;
use App\Models\Company\CompanyInvitation;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function accessHistoryCompany(): array
{
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Access History'], $owner);

    return [$company, $owner];
}

it('records exact role creation history', function () {
    [$company, $actor] = accessHistoryCompany();
    $permission = Permission::query()->where('name', CompanyPermission::MembersView->value)->sole();
    $role = app(CreateRole::class)->handle($company, $actor, 'auditor', 'Auditor', [$permission->id]);
    $event = $company->accessEvents()->sole();

    expect($event->company_id)->toBe($company->id);
    expect($event->event_type)->toBe(CompanyAccessEventType::RoleCreated)
        ->and($event->actor_user_id)->toBe($actor->id)
        ->and($event->subject_user_id)->toBeNull()
        ->and($event->role_id)->toBe($role->id)
        ->and($event->metadata)->toBe(['permissions' => [CompanyPermission::MembersView->value]]);
});

it('records exact before and after role update history', function () {
    [$company, $actor] = accessHistoryCompany();
    $beforePermission = Permission::query()->where('name', CompanyPermission::MembersView->value)->sole();
    $afterPermission = Permission::query()->where('name', CompanyPermission::RolesView->value)->sole();
    $role = app(CreateRole::class)->handle($company, $actor, 'auditor', 'Auditor', [$beforePermission->id]);

    app(UpdateRole::class)->handle($company, $role, $actor, 'reviewer', 'Reviewer', [$afterPermission->id]);
    $event = $company->accessEvents()->latest('id')->firstOrFail();

    expect($event->company_id)->toBe($company->id);
    expect($event->event_type)->toBe(CompanyAccessEventType::RoleUpdated)
        ->and($event->actor_user_id)->toBe($actor->id)
        ->and($event->role_id)->toBe($role->id)
        ->and($event->subject_user_id)->toBeNull()
        ->and($event->metadata['before'])->toBe(['name' => 'auditor', 'display_name' => 'Auditor', 'permissions' => [CompanyPermission::MembersView->value]])
        ->and($event->metadata['after'])->toBe(['name' => 'reviewer', 'display_name' => 'Reviewer', 'permissions' => [CompanyPermission::RolesView->value]]);
});

it('preserves exact role deletion history after the role reference becomes null', function () {
    [$company, $actor] = accessHistoryCompany();
    $role = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'temporary']);

    app(DeleteRole::class)->handle($company, $role, $actor);
    $event = $company->accessEvents()->sole();

    expect($event->company_id)->toBe($company->id);
    expect($event->event_type)->toBe(CompanyAccessEventType::RoleDeleted)
        ->and($event->actor_user_id)->toBe($actor->id)
        ->and($event->role_id)->toBeNull()
        ->and($event->subject_user_id)->toBeNull()
        ->and($event->metadata)->toBe(['role_uuid' => $role->uuid, 'name' => 'temporary']);
});

it('records exact member role replacement history', function () {
    [$company, $actor] = accessHistoryCompany();
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $beforeRole = RoleFactory::new()->create(['company_id' => $company->id]);
    $afterRole = RoleFactory::new()->create(['company_id' => $company->id]);
    $member->roles()->attach($beforeRole, ['company_id' => $company->id, 'assigned_by' => $actor->id]);

    app(AssignMemberRoles::class)->handle($company, $member, $actor, [$afterRole->id]);
    $event = $company->accessEvents()->sole();

    expect($event->company_id)->toBe($company->id);
    expect($event->event_type)->toBe(CompanyAccessEventType::MemberRolesUpdated)
        ->and($event->actor_user_id)->toBe($actor->id)
        ->and($event->subject_user_id)->toBe($member->id)
        ->and($event->role_id)->toBeNull()
        ->and($event->metadata)->toBe([
            'before_role_uuids' => [$beforeRole->uuid],
            'after_role_uuids' => [$afterRole->uuid],
        ]);
});

it('records exact owner promotion and demotion history', function () {
    [$company, $actor] = accessHistoryCompany();
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $ownerRole = $company->ownerRole()->sole();

    app(PromoteCompanyOwner::class)->handle($company, $member, $actor);
    app(DemoteCompanyOwner::class)->handle($company, $member, $actor);
    $events = $company->accessEvents()->orderBy('id')->get();

    expect($events)->toHaveCount(2)
        ->and($events[0]->company_id)->toBe($company->id)
        ->and($events[1]->company_id)->toBe($company->id)
        ->and($events[0]->metadata)->toBe(['before' => ['is_owner' => false], 'after' => ['is_owner' => true]])
        ->and($events[1]->metadata)->toBe(['before' => ['is_owner' => true], 'after' => ['is_owner' => false]])
        ->and($events[0]->event_type)->toBe(CompanyAccessEventType::OwnerPromoted)
        ->and($events[0]->actor_user_id)->toBe($actor->id)
        ->and($events[0]->subject_user_id)->toBe($member->id)
        ->and($events[0]->role_id)->toBe($ownerRole->id)
        ->and($events[1]->event_type)->toBe(CompanyAccessEventType::OwnerDemoted)
        ->and($events[1]->actor_user_id)->toBe($actor->id)
        ->and($events[1]->subject_user_id)->toBe($member->id)
        ->and($events[1]->role_id)->toBe($ownerRole->id);
});

it('records exact member removal history', function () {
    [$company, $actor] = accessHistoryCompany();
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $member->roles()->attach($role, ['company_id' => $company->id, 'assigned_by' => $actor->id]);

    app(RemoveCompanyMember::class)->handle($company, $member, $actor);
    $event = $company->accessEvents()->sole();

    expect($event->company_id)->toBe($company->id);
    expect($event->event_type)->toBe(CompanyAccessEventType::MemberRemoved)
        ->and($event->actor_user_id)->toBe($actor->id)
        ->and($event->subject_user_id)->toBe($member->id)
        ->and($event->role_id)->toBeNull()
        ->and($event->metadata)->toBe(['role_uuids' => [$role->uuid]]);
});

it('records exact voluntary departure history', function () {
    [$company, $owner] = accessHistoryCompany();
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $member->roles()->attach($role, ['company_id' => $company->id, 'assigned_by' => $owner->id]);

    app(LeaveCompany::class)->handle($company, $member);
    $event = $company->accessEvents()->sole();

    expect($event->company_id)->toBe($company->id);
    expect($event->event_type)->toBe(CompanyAccessEventType::MemberLeft)
        ->and($event->actor_user_id)->toBe($member->id)
        ->and($event->subject_user_id)->toBe($member->id)
        ->and($event->role_id)->toBeNull()
        ->and($event->metadata)->toBe(['role_uuids' => [$role->uuid]]);
});

it('rolls access history back with a failed transaction', function () {
    [$company, $actor] = accessHistoryCompany();

    expect(fn () => DB::transaction(function () use ($company, $actor): void {
        $company->accessEvents()->create([
            'actor_user_id' => $actor->id,
            'event_type' => CompanyAccessEventType::RoleCreated,
        ]);
        throw new RuntimeException('Force rollback.');
    }))->toThrow(RuntimeException::class)
        ->and($company->accessEvents()->count())->toBe(0);
});

it('does not record history for failed ownership operations', function () {
    [$company, $owner] = accessHistoryCompany();

    expect(fn () => app(DemoteCompanyOwner::class)->handle($company, $owner, $owner))->toThrow(DomainException::class)
        ->and($company->accessEvents()->count())->toBe(0);
});

it('does not record history for failed member removal', function () {
    [$company, $owner] = accessHistoryCompany();

    expect(fn () => app(RemoveCompanyMember::class)->handle($company, $owner, $owner))->toThrow(DomainException::class)
        ->and($company->accessEvents()->count())->toBe(0);
});

it('nulls deleted actor and subject references without deleting history', function () {
    [$company, $owner] = accessHistoryCompany();
    $actor = UserFactory::new()->create();
    $subject = UserFactory::new()->create();
    $event = $company->accessEvents()->create([
        'actor_user_id' => $actor->id,
        'subject_user_id' => $subject->id,
        'event_type' => CompanyAccessEventType::MemberRemoved,
    ]);

    $actor->delete();
    expect($event->fresh()->actor_user_id)->toBeNull();
    expect($event->fresh()->subject_user_id)->toBe($subject->id);
    $subject->delete();

    expect($event->fresh()->actor_user_id)->toBeNull()
        ->and($event->fresh()->subject_user_id)->toBeNull()
        ->and($company->accessEvents()->whereKey($event)->exists())->toBeTrue()
        ->and($owner->exists)->toBeTrue();
});

it('nulls deleted role and invitation references without enabling invitation routes', function () {
    [$company, $actor] = accessHistoryCompany();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $invitation = CompanyInvitation::query()->create([
        'company_id' => $company->id,
        'invited_by' => $actor->id,
        'email' => 'history@example.com',
        'token_hash' => hash('sha256', 'legacy-test-token'),
        'expires_at' => now()->addDay(),
    ]);
    $event = $company->accessEvents()->create([
        'actor_user_id' => $actor->id,
        'role_id' => $role->id,
        'invitation_id' => $invitation->id,
        'event_type' => CompanyAccessEventType::RoleUpdated,
        'metadata' => ['reason' => 'reference-retention-test'],
    ]);

    expect(fn () => $invitation->delete())->toThrow(LogicException::class);
    DB::table('company_invitations')->where('id', $invitation->id)->delete();
    app(DeleteRole::class)->handle($company, $role, $actor);

    expect($event->fresh()->role_id)->toBeNull()
        ->and($event->fresh()->invitation_id)->toBeNull()
        ->and($event->fresh()->metadata)->toBe(['reason' => 'reference-retention-test']);
});

it('exposes no access history mutation routes', function () {
    $mutationRoutes = collect(Route::getRoutes()->getRoutes())->filter(
        fn ($route): bool => str_contains($route->uri(), 'access-event')
            && in_array($route->methods()[0], ['POST', 'PUT', 'PATCH', 'DELETE'], true),
    );

    expect($mutationRoutes)->toBeEmpty();
});

it('rejects Eloquent access history updates and deletes', function () {
    $event = CompanyAccessEvent::factory()->create();

    expect(fn () => $event->update(['metadata' => ['changed' => true]]))->toThrow(LogicException::class)
        ->and(fn () => $event->delete())->toThrow(LogicException::class);
});

it('keeps secrets and raw invitation tokens out of generated event metadata', function () {
    [$company, $actor] = accessHistoryCompany();
    $permission = Permission::query()->where('name', CompanyPermission::MembersView->value)->sole();
    $actor->forceFill(['remember_token' => 'private-session-value'])->save();
    $member = UserFactory::new()->create();
    $departing = UserFactory::new()->create();
    $company->users()->attach([$member->id, $departing->id]);
    CompanyInvitation::query()->create([
        'company_id' => $company->id,
        'invited_by' => $actor->id,
        'email' => 'pending-history@example.com',
        'token_hash' => hash('sha256', 'raw-invitation-value'),
        'expires_at' => now()->addDay(),
    ]);
    $role = app(CreateRole::class)->handle($company, $actor, 'safe-metadata', null, [$permission->id]);
    app(UpdateRole::class)->handle($company, $role, $actor, 'safe-renamed', null, []);
    app(AssignMemberRoles::class)->handle($company, $member, $actor, [$role->id]);
    app(PromoteCompanyOwner::class)->handle($company, $member, $actor);
    app(DemoteCompanyOwner::class)->handle($company, $member, $actor);
    app(RemoveCompanyMember::class)->handle($company, $member, $actor);
    app(LeaveCompany::class)->handle($company, $departing);
    app(DeleteRole::class)->handle($company, $role, $actor);
    expect($company->accessEvents()->count())->toBe(8);
    $metadata = json_encode($company->accessEvents()->pluck('metadata')->all(), JSON_THROW_ON_ERROR);
    expect($metadata)->not->toContain('private-session-value')->not->toContain('raw-invitation-value')->not->toContain($actor->password);

    expect(strtolower($metadata))->not->toContain('password')
        ->not->toContain('secret')
        ->not->toContain('token');
});

it('rolls back the real privileged Action and its history together', function (string $operation) {
    [$company, $actor] = accessHistoryCompany();
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $role = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'rollback-role']);
    if ($operation === 'demote') {
        app(PromoteCompanyOwner::class)->handle($company, $member, $actor);
    }
    $tables = ['roles', 'role_permissions', 'user_roles', 'company_user', 'company_access_events'];
    $before = [];
    foreach ($tables as $table) {
        $before[$table] = DB::table($table)->get()->toArray();
    }

    expect(fn () => DB::transaction(function () use ($operation, $company, $actor, $member, $role): void {
        match ($operation) {
            'create' => app(CreateRole::class)->handle($company, $actor, 'rolled-back', null, []),
            'update' => app(UpdateRole::class)->handle($company, $role, $actor, 'rolled-back', null, []),
            'delete' => app(DeleteRole::class)->handle($company, $role, $actor),
            'assign' => app(AssignMemberRoles::class)->handle($company, $member, $actor, [$role->id]),
            'promote' => app(PromoteCompanyOwner::class)->handle($company, $member, $actor),
            'demote' => app(DemoteCompanyOwner::class)->handle($company, $member, $actor),
            'remove' => app(RemoveCompanyMember::class)->handle($company, $member, $actor),
            'leave' => app(LeaveCompany::class)->handle($company, $member),
        };
        throw new RuntimeException('Rollback real Action.');
    }))->toThrow(RuntimeException::class, 'Rollback real Action.');

    foreach ($tables as $table) {
        expect(DB::table($table)->get()->toArray())->toEqual($before[$table]);
    }
})->with(['create', 'update', 'delete', 'assign', 'promote', 'demote', 'remove', 'leave']);

it('preserves all history and access state for rejected member and ownership operations', function (string $operation) {
    [$company, $actor] = accessHistoryCompany();
    $outsider = UserFactory::new()->create();
    $before = $company->accessEvents()->get()->toArray();
    $action = match ($operation) {
        'assign' => fn () => app(AssignMemberRoles::class)->handle($company, $outsider, $actor, []),
        'promote' => fn () => app(PromoteCompanyOwner::class)->handle($company, $outsider, $actor),
        'demote' => fn () => app(DemoteCompanyOwner::class)->handle($company, $outsider, $actor),
        'remove' => fn () => app(RemoveCompanyMember::class)->handle($company, $outsider, $actor),
        'leave' => fn () => app(LeaveCompany::class)->handle($company, $outsider),
    };
    expect($action)->toThrow(DomainException::class);
    expect($company->accessEvents()->get()->toArray())->toBe($before);
    expect($company->users()->pluck('users.id')->all())->toBe([$actor->id]);
})->with(['assign', 'promote', 'demote', 'remove', 'leave']);

it('blocks bulk and quiet Eloquent history mutations without changing stored rows', function (string $operation) {
    $event = CompanyAccessEvent::factory()->create();
    $before = $event->fresh()->getRawOriginal();
    $query = CompanyAccessEvent::query()->whereKey($event);
    $mutation = match ($operation) {
        'update' => fn () => $query->update(['metadata' => '{"changed":true}']),
        'delete' => fn () => $query->delete(),
        'forceDelete' => fn () => $query->forceDelete(),
        'increment' => fn () => $query->increment('actor_user_id'),
        'decrement' => fn () => $query->decrement('actor_user_id'),
        'incrementEach' => fn () => $query->incrementEach(['actor_user_id' => 1]),
        'decrementEach' => fn () => $query->decrementEach(['actor_user_id' => 1]),
        'upsert' => fn () => $query->upsert([['id' => $event->id, 'metadata' => '{}']], ['id'], ['metadata']),
        'updateOrInsert' => fn () => $query->updateOrInsert(['id' => $event->id], ['metadata' => '{}']),
        'updateFrom' => fn () => $query->updateFrom(['metadata' => '{}']),
        'truncate' => fn () => $query->truncate(),
        'quietUpdate' => fn () => $event->updateQuietly(['metadata' => ['changed' => true]]),
        'quietDelete' => fn () => $event->deleteQuietly(),
    };
    expect($mutation)->toThrow(LogicException::class, 'Company access events are append-only.');
    expect(CompanyAccessEvent::query()->findOrFail($event->id)->getRawOriginal())->toBe($before);
    expect(CompanyAccessEvent::query()->count())->toBe(1);
})->with(['update', 'delete', 'forceDelete', 'increment', 'decrement', 'incrementEach', 'decrementEach', 'upsert', 'updateOrInsert', 'updateFrom', 'truncate', 'quietUpdate', 'quietDelete']);

it('rechecks privileged authority on direct Action invocation despite loaded owner relationships', function (string $operation) {
    [$company, $remaining] = accessHistoryCompany();
    $actor = UserFactory::new()->create();
    $target = UserFactory::new()->create();
    $company->users()->attach([$actor->id, $target->id]);
    app(PromoteCompanyOwner::class)->handle($company, $actor, $remaining);
    $role = app(CreateRole::class)->handle($company, $remaining, 'unchanged', 'Unchanged', []);
    $actor->load('companies', 'roles.permissions');
    expect($actor->can('manageOwnership', $company))->toBeTrue();
    app(DemoteCompanyOwner::class)->handle($company, $actor, $remaining);
    $before = [];
    foreach (['company_user', 'user_roles', 'roles', 'role_permissions', 'company_access_events'] as $table) {
        $before[$table] = DB::table($table)->get()->toArray();
    }
    $mutate = fn () => match ($operation) {
        'promote' => app(PromoteCompanyOwner::class)->handle($company, $target, $actor),
        'demote' => app(DemoteCompanyOwner::class)->handle($company, $remaining, $actor),
        'remove' => app(RemoveCompanyMember::class)->handle($company, $target, $actor),
        'assign' => app(AssignMemberRoles::class)->handle($company, $target, $actor, [$role->id]),
        'create-role' => app(CreateRole::class)->handle($company, $actor, 'forbidden', null, []),
        'update-role' => app(UpdateRole::class)->handle($company, $role, $actor, 'forbidden', null, []),
        'delete-role' => app(DeleteRole::class)->handle($company, $role, $actor),
    };
    expect($mutate)->toThrow(AuthorizationException::class);
    foreach ($before as $table => $rows) {
        expect(DB::table($table)->get()->toArray())->toEqual($rows);
    }
})->with(['promote', 'demote', 'remove', 'assign', 'create-role', 'update-role', 'delete-role']);
