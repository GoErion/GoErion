<?php

declare(strict_types=1);

use App\Actions\Auth\DeleteAccount;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\PromoteCompanyOwner;
use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\CompanyAccessEvent;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('rejects sole-owner account deletion without changing access or logging out', function () {
    $user = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Protected'], $user);
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->from(route('profile.edit'))->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirectToRoute('profile.edit')->assertSessionHasErrorsIn('userDeletion', ['password' => 'Your account is the last owner of a company. Add another owner or transfer ownership before deleting your account.']);
    $this->assertAuthenticatedAs($user);
    expect($user->fresh())->not->toBeNull();
    expect($company->users()->pluck('users.id')->all())->toBe([$user->id]);
    expect($company->accessEvents()->count())->toBe(0);
    $this->get(route('profile.edit'))->assertOk();
});

it('deletes a co-owner while retaining another owner and access history', function () {
    $user = UserFactory::new()->create();
    $remaining = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Co-owned'], $user);
    $company->users()->attach($remaining);
    app(PromoteCompanyOwner::class)->handle($company, $remaining, $user);
    $promotion = $company->accessEvents()->sole();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->withSession(['sensitive-state' => 'value']);
    $token = session()->token();
    $sessionId = session()->getId();
    $this->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirectToRoute('marketing')->assertSessionMissing('sensitive-state');
    $this->assertGuest();
    expect(session()->token())->not->toBe($token);
    expect(session()->getId())->not->toBe($sessionId);
    expect($user->fresh())->toBeNull();
    expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$remaining->id]);
    expect($promotion->fresh()->actor_user_id)->toBeNull();
    $departure = $company->accessEvents()->where('event_type', CompanyAccessEventType::MemberLeft)->sole();
    expect($departure->actor_user_id)->toBeNull()->and($departure->subject_user_id)->toBeNull()
        ->and($departure->metadata)->toBe(['reason' => 'account_deleted']);
});

it('records every ordinary membership departure without affecting unrelated companies', function () {
    $user = UserFactory::new()->create();
    $owner = UserFactory::new()->create();
    $first = app(CreateCompany::class)->handle(['company_name' => 'First'], $owner);
    $second = app(CreateCompany::class)->handle(['company_name' => 'Second'], $owner);
    $unrelated = app(CreateCompany::class)->handle(['company_name' => 'Unrelated'], $owner);
    $first->users()->attach($user);
    $second->users()->attach($user);
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirectToRoute('marketing');
    foreach ([$first, $second] as $company) {
        expect($company->users()->pluck('users.id')->all())->toBe([$owner->id]);
        expect($company->accessEvents()->sole()->metadata)->toBe(['reason' => 'account_deleted']);
    }
    expect($unrelated->accessEvents()->count())->toBe(0);
    expect($unrelated->users()->pluck('users.id')->all())->toBe([$owner->id]);
    expect($user->fresh())->toBeNull();
});

it('rejects all deletion changes when one of several companies has no other owner', function () {
    $user = UserFactory::new()->create();
    $other = UserFactory::new()->create();
    $shared = app(CreateCompany::class)->handle(['company_name' => 'Shared'], $other);
    $shared->users()->attach($user);
    app(PromoteCompanyOwner::class)->handle($shared, $user, $other);
    app(CreateCompany::class)->handle(['company_name' => 'Sole'], $user);
    $before = DB::table('company_access_events')->get()->toArray();
    $assignments = DB::table('user_roles')->get()->toArray();
    expect(fn () => app(DeleteAccount::class)->handle($user))->toThrow(DomainException::class);
    expect(DB::table('company_access_events')->get()->toArray())->toEqual($before);
    expect(DB::table('user_roles')->get()->toArray())->toEqual($assignments);
    expect($user->companies()->count())->toBe(2);
    expect($user->fresh())->not->toBeNull();
});

it('rolls back departures and deletion when a persistence step fails', function (string $failure) {
    $user = UserFactory::new()->create();
    $owner = UserFactory::new()->create();
    foreach (['First', 'Second'] as $name) {
        app(CreateCompany::class)->handle(['company_name' => $name], $owner)->users()->attach($user);
    }
    $eventName = $failure === 'history' ? 'eloquent.created: '.CompanyAccessEvent::class : 'eloquent.deleted: '.User::class;
    $dispatcher = clone Event::getFacadeRoot();
    Event::listen($eventName, function (): never {
        throw new RuntimeException('Persistence failure.');
    });
    try {
        expect(fn () => app(DeleteAccount::class)->handle($user))->toThrow(RuntimeException::class, 'Persistence failure.');
    } finally {
        Event::swap($dispatcher);
        User::setEventDispatcher($dispatcher);
    }
    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
    expect(DB::table('company_user')->where('user_id', $user->id)->count())->toBe(2);
    expect(CompanyAccessEvent::query()->count())->toBe(0);
})->with(['history', 'deletion']);

it('requires the current password for account deletion', function () {
    $user = UserFactory::new()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->delete(route('profile.destroy'), ['password' => 'incorrect'])
        ->assertSessionHasErrorsIn('userDeletion', 'password');
    expect($user->fresh())->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('deletes an owner across multiple companies only when each retains another owner', function () {
    $user = UserFactory::new()->create();
    $remaining = UserFactory::new()->create();
    $companies = [];
    foreach (['First owned company', 'Second owned company'] as $name) {
        $company = app(CreateCompany::class)->handle(['company_name' => $name], $user);
        $company->users()->attach($remaining);
        app(PromoteCompanyOwner::class)->handle($company, $remaining, $user);
        $companies[] = $company;
    }
    app(DeleteAccount::class)->handle($user);
    foreach ($companies as $company) {
        expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$remaining->id]);
        expect($company->accessEvents()->where('event_type', CompanyAccessEventType::MemberLeft)->count())->toBe(1);
    }
    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('keeps the account when creation of a departure event is cancelled', function () {
    $user = UserFactory::new()->create();
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Cancelled history'], $owner);
    $company->users()->attach($user);
    $dispatcher = clone Event::getFacadeRoot();
    Event::listen('eloquent.creating: '.CompanyAccessEvent::class, fn (): bool => false);
    try {
        expect(fn () => app(DeleteAccount::class)->handle($user))->toThrow(RuntimeException::class, 'Account departure history could not be recorded.');
    } finally {
        Event::swap($dispatcher);
        User::setEventDispatcher($dispatcher);
    }
    expect($user->fresh())->not->toBeNull();
    expect($company->users()->whereKey($user)->exists())->toBeTrue();
    expect($company->accessEvents()->count())->toBe(0);
});

it('enumerates current memberships instead of loaded account relationships', function () {
    $user = UserFactory::new()->create();
    $user->load('companies');
    $company = app(CreateCompany::class)->handle(['company_name' => 'Added after loading'], $user);
    expect(fn () => app(DeleteAccount::class)->handle($user))->toThrow(DomainException::class);
    expect($user->fresh())->not->toBeNull();
    expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$user->id]);
    expect($company->accessEvents()->count())->toBe(0);
});

it('does not create a company for an account that has been deleted', function () {
    $user = UserFactory::new()->create();
    app(DeleteAccount::class)->handle($user);
    expect(fn () => app(CreateCompany::class)->handle(['company_name' => 'Must not exist'], $user))
        ->toThrow(ModelNotFoundException::class);
    expect(DB::table('companies')->count())->toBe(0);
    expect(DB::table('company_user')->count())->toBe(0);
    expect(DB::table('roles')->count())->toBe(0);
    expect(DB::table('user_roles')->count())->toBe(0);
    expect(DB::table('company_access_events')->count())->toBe(0);
});
