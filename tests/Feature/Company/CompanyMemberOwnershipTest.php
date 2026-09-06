<?php

declare(strict_types=1);

use App\Actions\Company\AssignMemberRoles;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\DemoteCompanyOwner;
use App\Actions\Company\LeaveCompany;
use App\Actions\Company\PromoteCompanyOwner;
use App\Actions\Company\RemoveCompanyMember;
use App\Enums\CompanyAccessEventType;
use App\Enums\CompanyPermission;
use App\Models\Auth\Permission;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

uses(RefreshDatabase::class);

function phase2b1Company(): array
{
    $company = CompanyFactory::new()->create();
    $owner = UserFactory::new()->create();
    $member = UserFactory::new()->create();
    $company->users()->attach([$owner->id, $member->id]);
    $ownerRole = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'owner', 'display_name' => 'Owner', 'is_system' => true]);
    $owner->roles()->attach($ownerRole, ['company_id' => $company->id, 'assigned_by' => $owner->id]);
    $ownerRole->permissions()->sync(collect(CompanyPermission::cases())->map(fn (CompanyPermission $permission) => Permission::query()->firstOrCreate(['name' => $permission->value], ['display_name' => $permission->displayName()])->id)->all());

    return [$company, $owner, $member, $ownerRole];
}

it('replaces only same-company custom roles while preserving owner and other-company roles', function () {
    [$company, $owner, $member, $ownerRole] = phase2b1Company();
    $other = CompanyFactory::new()->create();
    $other->users()->attach($member);
    $old = RoleFactory::new()->create(['company_id' => $company->id]);
    $new = RoleFactory::new()->create(['company_id' => $company->id]);
    $foreign = RoleFactory::new()->create(['company_id' => $other->id]);
    $member->roles()->attach($old, ['company_id' => $company->id]);
    $member->roles()->attach($foreign, ['company_id' => $other->id]);
    app(PromoteCompanyOwner::class)->handle($company, $member, $owner);
    app(AssignMemberRoles::class)->handle($company, $member, $owner, [$new->id]);
    expect($member->roles()->whereKey($ownerRole)->exists())->toBeTrue()->and($member->roles()->whereKey($old)->exists())->toBeFalse()
        ->and($member->roles()->whereKey($new)->exists())->toBeTrue()->and($member->roles()->whereKey($foreign)->exists())->toBeTrue()
        ->and($other->users()->whereKey($member)->exists())->toBeTrue();
});

it('supports multiple owners and protects the last owner', function () {
    [$company, $owner, $member] = phase2b1Company();
    app(PromoteCompanyOwner::class)->handle($company, $member, $owner);
    app(DemoteCompanyOwner::class)->handle($company, $owner, $member);
    expect(fn () => app(DemoteCompanyOwner::class)->handle($company, $member, $member))->toThrow(DomainException::class)
        ->and($company->accessEvents()->where('event_type', CompanyAccessEventType::OwnerPromoted)->exists())->toBeTrue();
});

it('rejects generic owner removal and allows safe voluntary departure', function () {
    [$company, $owner, $member] = phase2b1Company();
    expect(fn () => app(RemoveCompanyMember::class)->handle($company, $owner, $owner))->toThrow(DomainException::class);
    app(LeaveCompany::class)->handle($company, $member);
    expect($company->users()->whereKey($member)->exists())->toBeFalse()
        ->and($company->accessEvents()->where('event_type', CompanyAccessEventType::MemberLeft)->exists())->toBeTrue()
        ->and(fn () => app(LeaveCompany::class)->handle($company, $owner))->toThrow(DomainException::class);
});

it('renders searchable paginated member administration with secure forms', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Access UI'], $owner);
    $member = UserFactory::new()->create(['name' => 'Searchable Member', 'email' => 'member@example.com']);
    $company->users()->attach($member);
    $response = $this->actingAs($owner)->get(route('company.members.index', [$company, 'search' => 'member@example.com']));
    $response->assertOk()->assertSee('Searchable Member')->assertSee('name="search"', false)
        ->assertSee(route('company.members.roles.update', [$company, $member->uuid]), false)->assertSee('name="_token"', false)->assertSee('value="PUT"', false)
        ->assertSee('<table', false)->assertSee('<article', false);
});

it('allows assigning zero custom roles while preserving the owner role', function () {
    [$company, $owner, $member, $ownerRole] = phase2b1Company();
    $customRole = RoleFactory::new()->create(['company_id' => $company->id]);
    $member->roles()->attach($customRole, ['company_id' => $company->id, 'assigned_by' => $owner->id]);
    app(PromoteCompanyOwner::class)->handle($company, $member, $owner);

    app(AssignMemberRoles::class)->handle($company, $member, $owner, []);

    expect($member->rolesForCompany($company)->where('is_system', false)->count())->toBe(0)
        ->and($member->roles()->whereKey($ownerRole)->exists())->toBeTrue();
});

it('rejects assigning a foreign company role', function () {
    [$company, $owner, $member] = phase2b1Company();
    $otherCompany = CompanyFactory::new()->create();
    $foreignRole = RoleFactory::new()->create(['company_id' => $otherCompany->id]);

    expect(fn () => app(AssignMemberRoles::class)->handle($company, $member, $owner, [$foreignRole->id]))
        ->toThrow(DomainException::class)
        ->and($member->roles()->whereKey($foreignRole)->exists())->toBeFalse();
});

it('rejects assigning the protected owner role through generic role assignment', function () {
    [$company, $owner, $member, $ownerRole] = phase2b1Company();

    expect(fn () => app(AssignMemberRoles::class)->handle($company, $member, $owner, [$ownerRole->id]))
        ->toThrow(DomainException::class)
        ->and($member->roles()->whereKey($ownerRole)->exists())->toBeFalse();
});

it('rejects assigning roles to a non-member target', function () {
    [$company, $owner] = phase2b1Company();
    $nonMember = UserFactory::new()->create();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);

    expect(fn () => app(AssignMemberRoles::class)->handle($company, $nonMember, $owner, [$role->id]))
        ->toThrow(DomainException::class)
        ->and($nonMember->roles()->count())->toBe(0);
});

it('gives the original creator no ownership privilege after safe demotion', function () {
    $creator = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Original Creator'], $creator);
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    app(PromoteCompanyOwner::class)->handle($company, $member, $creator);

    app(DemoteCompanyOwner::class)->handle($company, $creator, $member);

    expect($creator->can('manageOwnership', $company))->toBeFalse()
        ->and($company->users()->whereKey($creator)->exists())->toBeTrue();
});

it('allows an owner to depart when another owner remains', function () {
    [$company, $owner, $member, $ownerRole] = phase2b1Company();
    app(PromoteCompanyOwner::class)->handle($company, $member, $owner);

    app(LeaveCompany::class)->handle($company, $owner);

    expect($company->users()->whereKey($owner)->exists())->toBeFalse()
        ->and($member->roles()->whereKey($ownerRole)->exists())->toBeTrue()
        ->and($company->accessEvents()->where('event_type', CompanyAccessEventType::MemberLeft)->count())->toBe(1);
});

it('rejects departure by the last owner without recording history', function () {
    [$company, $owner] = phase2b1Company();

    expect(fn () => app(LeaveCompany::class)->handle($company, $owner))->toThrow(DomainException::class)
        ->and($company->users()->whereKey($owner)->exists())->toBeTrue()
        ->and($company->accessEvents()->count())->toBe(0);
});

it('rejects generic removal of an owner without recording history', function () {
    [$company, $owner] = phase2b1Company();

    expect(fn () => app(RemoveCompanyMember::class)->handle($company, $owner, $owner))->toThrow(DomainException::class)
        ->and($company->users()->whereKey($owner)->exists())->toBeTrue()
        ->and($company->accessEvents()->count())->toBe(0);
});

it('removes an ordinary member only from the route company', function () {
    [$company, $owner, $member] = phase2b1Company();
    $otherCompany = CompanyFactory::new()->create();
    $otherCompany->users()->attach($member);
    $routeRole = RoleFactory::new()->create(['company_id' => $company->id]);
    $otherRole = RoleFactory::new()->create(['company_id' => $otherCompany->id]);
    $member->roles()->attach($routeRole, ['company_id' => $company->id, 'assigned_by' => $owner->id]);
    $member->roles()->attach($otherRole, ['company_id' => $otherCompany->id, 'assigned_by' => $owner->id]);

    app(RemoveCompanyMember::class)->handle($company, $member, $owner);

    expect($company->users()->whereKey($member)->exists())->toBeFalse()
        ->and($otherCompany->users()->whereKey($member)->exists())->toBeTrue()
        ->and($member->roles()->whereKey($routeRole)->exists())->toBeFalse()
        ->and($member->roles()->whereKey($otherRole)->exists())->toBeTrue();
});

it('allows an ordinary member to leave only the route company', function () {
    [$company, $owner, $member] = phase2b1Company();
    $otherCompany = CompanyFactory::new()->create();
    $otherCompany->users()->attach($member);
    $routeRole = RoleFactory::new()->create(['company_id' => $company->id]);
    $otherRole = RoleFactory::new()->create(['company_id' => $otherCompany->id]);
    $member->roles()->attach($routeRole, ['company_id' => $company->id, 'assigned_by' => $owner->id]);
    $member->roles()->attach($otherRole, ['company_id' => $otherCompany->id, 'assigned_by' => $owner->id]);

    app(LeaveCompany::class)->handle($company, $member);

    expect($company->users()->whereKey($member)->exists())->toBeFalse()
        ->and($otherCompany->users()->whereKey($member)->exists())->toBeTrue()
        ->and($member->roles()->whereKey($routeRole)->exists())->toBeFalse()
        ->and($member->roles()->whereKey($otherRole)->exists())->toBeTrue();
});

it('returns 403 before nested member resolution for an unauthorized route company', function () {
    $company = CompanyFactory::new()->create();
    $foreignMember = UserFactory::new()->create();

    $this->actingAs(UserFactory::new()->create())
        ->get(route('company.members.remove', [$company, $foreignMember->uuid]))
        ->assertForbidden();
});

it('returns 404 for a foreign nested member after route company authorization', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Authorized Company'], $owner);
    $otherCompany = CompanyFactory::new()->create();
    $foreignMember = UserFactory::new()->create();
    $otherCompany->users()->attach($foreignMember);

    $this->actingAs($owner)
        ->get(route('company.members.remove', [$company, $foreignMember->uuid]))
        ->assertNotFound();
});

it('rejects non-owner system roles through generic assignment', function () {
    [$company, $owner, $member] = phase2b1Company();
    $system = RoleFactory::new()->create(['company_id' => $company->id, 'is_system' => true, 'name' => 'protected']);
    expect(fn () => app(AssignMemberRoles::class)->handle($company, $member, $owner, [$system->id]))->toThrow(DomainException::class);
    expect($member->roles()->count())->toBe(0);
    expect($company->accessEvents()->count())->toBe(0);
});

it('enforces route-company authorization before resolving nested members on write routes', function (string $routeName, string $method) {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Write Boundary'], $owner);
    $outsider = UserFactory::new()->create();
    $foreign = UserFactory::new()->create();
    $url = route($routeName, [$company, $foreign->uuid]);

    $this->actingAs($outsider)->$method($url, ['role_ids' => []])->assertForbidden();
    $this->actingAs($owner)->$method($url, ['role_ids' => []])->assertNotFound();
    expect($company->accessEvents()->count())->toBe(0);
})->with([
    ['company.members.roles.update', 'put'],
    ['company.members.destroy', 'delete'],
    ['company.owners.store', 'post'],
    ['company.owners.destroy', 'delete'],
]);

it('preserves role filters pagination multiple badges and escaped identity in both layouts', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Responsive Company'], $owner);
    $role = RoleFactory::new()->create(['company_id' => $company->id, 'display_name' => 'Reviewer']);
    $secondRole = RoleFactory::new()->create(['company_id' => $company->id, 'display_name' => 'Editor']);
    $members = UserFactory::new()->count(13)->create(['name' => 'Filtered <script>member</script>']);
    foreach ($members as $member) {
        $company->users()->attach($member);
        $member->roles()->attach([$role->id => ['company_id' => $company->id], $secondRole->id => ['company_id' => $company->id]]);
    }
    $response = $this->actingAs($owner)->get(route('company.members.index', [$company, 'search' => 'Filtered', 'role' => $role->uuid]));
    $response->assertOk()->assertSee('Responsive Company')->assertSee('Reviewer')->assertSee('Editor')
        ->assertSee('md:hidden', false)->assertSee('md:block', false)
        ->assertSee('Filtered &lt;script&gt;member&lt;/script&gt;', false)->assertDontSee('<script>member</script>', false)
        ->assertViewHas('members', fn ($page): bool => $page->total() === 13 && $page->count() === 12 && str_contains($page->nextPageUrl(), 'search=Filtered') && str_contains($page->nextPageUrl(), 'role='.$role->uuid));

    $this->get(route('company.members.index', [$company, 'search' => 'no-match']))->assertSee('No members match these filters.');
    $foreignCompany = CompanyFactory::new()->create();
    $foreignRole = RoleFactory::new()->create(['company_id' => $foreignCompany->id]);
    $this->get(route('company.members.index', [$company, 'role' => $foreignRole->uuid]))->assertSessionHasErrors('role');
});

it('hides privileged member controls from a read-only member', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Read Only'], $owner);
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $permission = Permission::query()->where('name', 'company.members.view')->sole();
    $role->permissions()->attach($permission);
    $member->roles()->attach($role, ['company_id' => $company->id]);

    $this->actingAs($member)->get(route('company.members.index', $company))->assertOk()
        ->assertDontSee('Save roles')->assertDontSee('Promote owner')->assertDontSee('Demote owner')
        ->assertDontSee(route('company.members.remove', [$company, $owner->uuid]), false);
});

it('shows accessible feedback on company access pages', function (string $page) {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Feedback'], $owner);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $parameters = match ($page) {
        'company.roles.edit' => [$company, $role->uuid],
        'company.members.remove', 'company.owners.promote', 'company.owners.demote' => [$company, $owner->uuid],
        default => [$company],
    };
    $errors = (new ViewErrorBag)->put('default', new MessageBag(['access' => 'The operation was rejected.', 'permission_ids.0' => 'Choose an approved permission.']));
    $this->actingAs($owner)->withSession(['success' => 'Access updated.', 'error' => 'Please try again.', 'errors' => ['default' => ['format' => ':message', 'messages' => $errors->getBag('default')->getMessages()]]])
        ->get(route($page, $parameters))->assertOk()->assertSee('Access updated.')->assertSee('Please try again.')
        ->assertSee('The operation was rejected.')->assertSee('Choose an approved permission.')
        ->assertSee('role="status"', false)->assertSee('role="alert"', false);
})->with(['company.members.index', 'company.members.remove', 'company.membership.leave', 'company.owners.promote', 'company.owners.demote', 'company.roles.index', 'company.roles.create', 'company.roles.edit']);
