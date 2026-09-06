<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Actions\Company\CreateRole;
use App\Actions\Company\DeleteRole;
use App\Actions\Company\UpdateRole;
use App\Enums\CompanyAccessEventType;
use App\Enums\CompanyPermission;
use App\Enums\InvitationStatus;
use App\Models\Auth\Permission;
use App\Models\Company\CompanyInvitation;
use Database\Factories\CompanyFactory;
use Database\Factories\PermissionFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates updates and deletes an unused custom role with access history', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $company->users()->attach($actor);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $permission = Permission::query()->where('name', CompanyPermission::MembersView->value)->sole();
    $role = app(CreateRole::class)->handle($company, $actor, 'manager', 'Manager', [$permission->id]);
    expect($role->permissions)->toHaveCount(1)->and($role->isEditable())->toBeTrue();
    app(UpdateRole::class)->handle($company, $role, $actor, 'team-manager', 'Team Manager', []);
    app(DeleteRole::class)->handle($company, $role, $actor);
    expect($company->accessEvents()->pluck('event_type')->all())->toBe([
        CompanyAccessEventType::RoleCreated, CompanyAccessEventType::RoleUpdated, CompanyAccessEventType::RoleDeleted,
    ])->and($company->accessEvents()->where('event_type', CompanyAccessEventType::RoleDeleted)->sole()->role_id)->toBeNull();
});

it('rejects system roles unassignable permissions and assigned role deletion', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $member = UserFactory::new()->create();
    $company->users()->attach([$actor->id, $member->id]);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $owner = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'owner', 'is_system' => true]);
    $ownership = Permission::query()->where('name', CompanyPermission::OwnershipManage->value)->sole();
    expect(fn () => app(UpdateRole::class)->handle($company, $owner, $actor, 'changed', null, []))->toThrow(DomainException::class)
        ->and(fn () => app(CreateRole::class)->handle($company, $actor, 'bad', null, [$ownership->id]))->toThrow(DomainException::class);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $member->roles()->attach($role, ['company_id' => $company->id]);
    expect(fn () => app(DeleteRole::class)->handle($company, $role, $actor))->toThrow(DomainException::class)
        ->and($company->accessEvents()->count())->toBe(0);
});

it('rejects deleting a role referenced by a pending invitation', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $company->users()->attach($actor);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    CompanyInvitation::query()->create([
        'company_id' => $company->id,
        'role_id' => $role->id,
        'invited_by' => $actor->id,
        'email' => 'pending@example.com',
        'token' => 'pending-token',
        'status' => InvitationStatus::Pending,
        'expires_at' => now()->addDay(),
    ]);
    expect(fn () => app(DeleteRole::class)->handle($company, $role, $actor))->toThrow(DomainException::class);
});

it('returns 403 for another company and 404 for its nested role after authorization', function () {
    $company = CompanyFactory::new()->create();
    $other = CompanyFactory::new()->create();
    $user = UserFactory::new()->create();
    $role = RoleFactory::new()->create(['company_id' => $other->id]);
    $this->actingAs($user)->get(route('company.roles.edit', [$company, $role->uuid]))->assertForbidden();
    $company->users()->attach($user);
    $management = Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole();
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach($management);
    $user->roles()->attach($manager, ['company_id' => $company->id]);
    $this->actingAs($user)->get(route('company.roles.edit', [$company, $role->uuid]))->assertNotFound();
});

it('renders role pages without exposing ownership management in generic forms', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Role UI'], $owner);
    $this->actingAs($owner)->get(route('company.roles.create', $company))->assertOk()
        ->assertSee('Create role')->assertDontSee('company.ownership.manage')->assertSee('name="permission_ids[]"', false);
    $this->actingAs($owner)->get(route('company.roles.index', $company))->assertOk()->assertSee('Protected system role')->assertDontSee(route('company.roles.edit', [$company, $company->ownerRole]), false);
});

it('accepts only approved permissions whose assignable flag is enabled', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $company->users()->attach($actor);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $approved = Permission::query()->where('name', CompanyPermission::MembersView->value)->sole();

    $role = app(CreateRole::class)->handle($company, $actor, 'approved-role', null, [$approved->id]);

    expect($role->permissions()->pluck('name')->all())->toBe([CompanyPermission::MembersView->value]);
});

it('rejects an approved permission when its assignable flag is disabled', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $company->users()->attach($actor);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $approved = Permission::query()->where('name', CompanyPermission::MembersView->value)->sole();
    $approved->update(['assignable_to_custom_roles' => false]);

    expect(fn () => app(CreateRole::class)->handle($company, $actor, 'disabled-permission', null, [$approved->id]))
        ->toThrow(DomainException::class)
        ->and($company->roles()->where('name', 'disabled-permission')->exists())->toBeFalse();
});

it('rejects an unrelated permission even when its assignable flag is enabled', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $company->users()->attach($actor);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $unrelated = PermissionFactory::new()->create([
        'name' => 'unrelated.manually_assignable',
        'assignable_to_custom_roles' => true,
    ]);

    expect(fn () => app(CreateRole::class)->handle($company, $actor, 'unrelated-permission', null, [$unrelated->id]))
        ->toThrow(DomainException::class)
        ->and($unrelated->fresh()->assignable_to_custom_roles)->toBeTrue();
});

it('rejects an unrelated flagged permission when updating a custom role', function () {
    $company = CompanyFactory::new()->create();
    $actor = UserFactory::new()->create();
    $company->users()->attach($actor);
    $manager = RoleFactory::new()->create(['company_id' => $company->id]);
    $manager->permissions()->attach(Permission::query()->where('name', CompanyPermission::RolesManage->value)->sole());
    $actor->roles()->attach($manager, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
    $role = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'existing-role']);
    $unrelated = PermissionFactory::new()->create([
        'name' => 'unrelated.update_assignable',
        'assignable_to_custom_roles' => true,
    ]);

    expect(fn () => app(UpdateRole::class)->handle($company, $role, $actor, 'changed-role', null, [$unrelated->id]))
        ->toThrow(DomainException::class)
        ->and($role->fresh()->name)->toBe('existing-role')
        ->and($role->permissions()->count())->toBe(0);
});

it('rejects ownership, nonexistent, and manipulated permission identifiers without changing permissions', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Permission Boundary'], $owner);
    $ownership = Permission::query()->where('name', CompanyPermission::OwnershipManage->value)->sole();
    $before = Permission::query()->orderBy('id')->get(['id', 'name', 'display_name', 'assignable_to_custom_roles'])->toArray();

    $this->actingAs($owner)->post(route('company.roles.store', $company), [
        'name' => 'ownership-role',
        'permission_ids' => [$ownership->id],
    ])->assertSessionHasErrors('permission_ids.0');
    $this->actingAs($owner)->post(route('company.roles.store', $company), [
        'name' => 'missing-role',
        'permission_ids' => [PHP_INT_MAX],
    ])->assertSessionHasErrors('permission_ids.0');
    $this->actingAs($owner)->post(route('company.roles.store', $company), [
        'name' => 'manipulated-role',
        'permission_ids' => ['not-an-id'],
    ])->assertSessionHasErrors('permission_ids.0');

    expect(Permission::query()->orderBy('id')->get(['id', 'name', 'display_name', 'assignable_to_custom_roles'])->toArray())
        ->toBe($before);
});

it('enforces the permission intersection in both HTTP role forms', function (string $operation, string $kind) {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Allowlist HTTP'], $owner);
    $role = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'existing']);
    $permission = match ($kind) {
        'unrelated' => PermissionFactory::new()->create(['name' => 'outside.company', 'assignable_to_custom_roles' => true]),
        'ownership' => Permission::query()->where('name', CompanyPermission::OwnershipManage->value)->sole(),
        default => Permission::query()->where('name', CompanyPermission::MembersView->value)->sole(),
    };
    if ($kind === 'disabled') {
        $permission->update(['assignable_to_custom_roles' => false]);
    }
    if ($kind === 'ownership') {
        $permission->update(['assignable_to_custom_roles' => true]);
    }
    $permissionId = match ($kind) {
        'missing' => PHP_INT_MAX,
        'manipulated' => $permission->id.' OR 1=1',
        default => $permission->id,
    };
    $before = Permission::query()->orderBy('id')->get()->toArray();
    $payload = ['name' => 'allowed-role', 'permission_ids' => [$permissionId]];
    $this->actingAs($owner);
    $response = $operation === 'create'
        ? $this->post(route('company.roles.store', $company), $payload)
        : $this->put(route('company.roles.update', [$company, $role->uuid]), $payload);

    if ($kind === 'approved') {
        $response->assertSessionHasNoErrors()->assertRedirectToRoute('company.roles.index', $company);
        $saved = $company->roles()->where('name', 'allowed-role')->sole();
        expect($saved->permissions()->pluck('permissions.id')->all())->toBe([$permission->id]);
    } else {
        $response->assertSessionHasErrors('permission_ids.0');
        expect($company->roles()->where('name', 'allowed-role')->exists())->toBeFalse();
        expect($role->fresh()->name)->toBe('existing');
        expect($company->accessEvents()->count())->toBe(0);
    }
    expect(Permission::query()->orderBy('id')->get()->toArray())->toBe($before);
})->with(['create', 'update'])->with(['approved', 'disabled', 'unrelated', 'ownership', 'missing', 'manipulated']);

it('rejects invalid permissions at the Action boundary without partial writes', function (string $operation, string $kind) {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Allowlist Actions'], $owner);
    $role = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'original']);
    $permission = Permission::query()->where('name', $kind === 'ownership' ? CompanyPermission::OwnershipManage->value : CompanyPermission::MembersView->value)->sole();
    if ($kind === 'disabled') {
        $permission->update(['assignable_to_custom_roles' => false]);
    }
    if ($kind === 'ownership') {
        $permission->update(['assignable_to_custom_roles' => true]);
    }
    $id = match ($kind) {
        'missing' => PHP_INT_MAX,
        'manipulated' => $permission->id.'abc',
        default => $permission->id,
    };
    $before = Permission::query()->orderBy('id')->get()->toArray();
    $action = $operation === 'create'
        ? fn () => app(CreateRole::class)->handle($company, $owner, 'changed', null, [$id])
        : fn () => app(UpdateRole::class)->handle($company, $role, $owner, 'changed', null, [$id]);

    expect($action)->toThrow(DomainException::class);
    expect($role->fresh()->name)->toBe('original');
    expect($company->roles()->where('name', 'changed')->exists())->toBeFalse();
    expect($company->accessEvents()->count())->toBe(0);
    expect(Permission::query()->orderBy('id')->get()->toArray())->toBe($before);
})->with(['create', 'update'])->with(['disabled', 'ownership', 'missing', 'manipulated']);

it('loads only enabled canonical permissions for create and edit forms', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Form Allowlist'], $owner);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    PermissionFactory::new()->create(['name' => 'outside.company', 'assignable_to_custom_roles' => true]);
    Permission::query()->where('name', CompanyPermission::OwnershipManage->value)->update(['assignable_to_custom_roles' => true]);
    Permission::query()->where('name', CompanyPermission::MembersView->value)->update(['assignable_to_custom_roles' => false]);
    $expected = ['company.invitations.manage', 'company.members.manage', 'company.roles.manage', 'company.roles.view', 'company.update'];

    foreach ([route('company.roles.create', $company), route('company.roles.edit', [$company, $role->uuid])] as $url) {
        $this->actingAs($owner)->get($url)->assertOk()->assertViewHas('permissions', fn ($permissions): bool => $permissions->pluck('name')->sort()->values()->all() === $expected);
    }
});

it('preserves submitted string permission IDs after edit validation fails', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Validation'], $owner);
    $initial = Permission::query()->where('name', 'company.members.view')->sole();
    $submitted = Permission::query()->where('name', 'company.roles.view')->sole();
    $role = app(CreateRole::class)->handle($company, $owner, 'reviewer', null, [$initial->id]);
    $edit = route('company.roles.edit', [$company, $role->uuid]);
    $response = $this->actingAs($owner)->get($edit)->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@name="permission_ids[]"][@checked]/@value')->item(0)->nodeValue)->toBe((string) $initial->id);

    $response = $this->followingRedirects()->from($edit)->put(route('company.roles.update', [$company, $role->uuid]), [
        'name' => '', 'permission_ids' => [(string) $submitted->id],
    ])->assertOk()->assertSee('The name field is required.');
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $checked = $xpath->query('//input[@name="permission_ids[]"][@checked]/@value');
    expect($checked->length)->toBe(1)->and($checked->item(0)->nodeValue)->toBe((string) $submitted->id);
    expect($role->fresh()->name)->toBe('reviewer');
});
