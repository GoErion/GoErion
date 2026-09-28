<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\Company\Company;
use Database\Factories\UserFactory;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('renders company onboarding for a verified user', function () {
    $user = UserFactory::new()->create();

    $this->actingAs($user)->get(route('company.create'))
        ->assertOk()
        ->assertSee('Create your company')
        ->assertSee('name="company_name"', false)
        ->assertSee('required', false);
});

it('requires authentication and verification for company creation', function () {
    $this->get(route('company.create'))->assertRedirect(route('login'));
    $this->post(route('company.store'), ['company_name' => 'Guest Company'])->assertRedirect(route('login'));

    $unverifiedUser = UserFactory::new()->unverified()->create();
    $this->actingAs($unverifiedUser)->get(route('company.create'))->assertRedirect(route('verification.notice'));
    $this->actingAs($unverifiedUser)->post(route('company.store'), ['company_name' => 'Unverified Company'])
        ->assertRedirect(route('verification.notice'));

    expect(Company::query()->count())->toBe(0);
});

it('creates a company with only its name and redirects to its uuid overview', function () {
    $creator = UserFactory::new()->create();

    $response = $this->actingAs($creator)->post(route('company.store'), ['company_name' => 'Acme']);

    $company = Company::query()->sole();
    $response->assertRedirect(route('home', $company));
    expect($company->getRouteKeyName())->toBe('uuid');
    expect($company->legal_name)->toBeNull()
        ->and($company->registration_number)->toBeNull()
        ->and($company->tax_number)->toBeNull()
        ->and($company->email)->toBeNull()
        ->and($company->phone)->toBeNull()
        ->and($company->address)->toBeNull();
});

it('allows duplicate company names and optional identity values', function () {
    $creator = UserFactory::new()->create();
    $values = [
        'company_name' => 'Shared Name',
        'legal_name' => 'Shared Legal Name',
        'registration_number' => 'REG-1',
        'tax_number' => 'TAX-1',
    ];

    $this->actingAs($creator)->post(route('company.store'), $values)->assertRedirect();
    $this->actingAs($creator)->post(route('company.store'), $values)->assertRedirect();

    expect(Company::query()->where($values)->count())->toBe(2);
});

it('derives protected company and owner data on the server', function () {
    $creator = UserFactory::new()->create();
    $otherUser = UserFactory::new()->create();

    $this->actingAs($creator)->post(route('company.store'), [
        'company_name' => 'Protected Company',
        'uuid' => '00000000-0000-0000-0000-000000000000',
        'company_id' => 999,
        'owner_id' => $otherUser->id,
        'status' => 'archived',
        'currency' => 'USD',
        'roles' => [999],
        'permissions' => [999],
    ])->assertRedirect();

    $company = Company::query()->sole();
    $ownerRole = $company->roles()->where('name', 'owner')->sole();
    expect($company->uuid)->not->toBe('00000000-0000-0000-0000-000000000000')
        ->and($company->status)->toBe('active')
        ->and($company->currency)->toBe('kes')
        ->and($creator->companies()->whereKey($company)->exists())->toBeTrue()
        ->and($otherUser->companies()->whereKey($company)->exists())->toBeFalse()
        ->and($ownerRole->company_id)->toBe($company->id)
        ->and($creator->roles()->whereKey($ownerRole)->exists())->toBeTrue()
        ->and($otherUser->roles()->whereKey($ownerRole)->exists())->toBeFalse()
        ->and($ownerRole->permissions()->pluck('name')->sort()->values()->all())->toBe([
            'company.invitations.manage', 'company.members.manage', 'company.members.view',
            'company.ownership.manage', 'company.roles.manage', 'company.roles.view', 'company.update',
            'customers.archive', 'customers.create', 'customers.credit.manage',
            'customers.groups.manage', 'customers.update', 'customers.view',
            'products.archive', 'products.barcodes.manage', 'products.classifications.manage', 'products.cost.view', 'products.create', 'products.prices.manage', 'products.suppliers.manage', 'products.update', 'products.view', 'suppliers.archive', 'suppliers.create', 'suppliers.terms.manage', 'suppliers.update', 'suppliers.view',
        ]);
});

it('creates no operational setup records', function () {
    $creator = UserFactory::new()->create();

    $this->actingAs($creator)->post(route('company.store'), ['company_name' => 'Foundation Only']);

    expect(DB::table('branches')->count())->toBe(0)
        ->and(DB::table('warehouses')->count())->toBe(0)
        ->and(DB::table('warehouse_locations')->count())->toBe(0)
        ->and(DB::table('company_settings')->count())->toBe(0)
        ->and(DB::table('number_sequences')->count())->toBe(0);
});

it('rolls back the complete company creation when owner role creation fails', function () {
    $creator = UserFactory::new()->create();
    $existingPermission = Permission::query()->where('name', 'company.update')->sole();
    Event::listen('eloquent.creating: '.Role::class, fn () => throw new RuntimeException('Controlled role failure'));

    try {
        expect(fn () => app(CreateCompany::class)->handle(['company_name' => 'Rolled Back'], $creator))
            ->toThrow(RuntimeException::class, 'Controlled role failure');
    } finally {
        Event::forget('eloquent.creating: '.Role::class);
    }

    expect(Company::query()->count())->toBe(0)
        ->and(DB::table('company_user')->count())->toBe(0)
        ->and(Role::query()->count())->toBe(0)
        ->and(Permission::query()->whereKey($existingPermission)->exists())->toBeTrue()
        ->and(DB::table('user_roles')->count())->toBe(0)
        ->and(DB::table('role_permissions')->count())->toBe(0);
});

it('rolls back company creation when permission creation fails', function () {
    $creator = UserFactory::new()->create();
    Permission::query()->where('name', 'company.update')->delete();
    Event::listen('eloquent.creating: '.Permission::class, fn () => throw new RuntimeException('Controlled permission failure'));

    try {
        expect(fn () => app(CreateCompany::class)->handle(['company_name' => 'Rolled Back'], $creator))
            ->toThrow(RuntimeException::class, 'Controlled permission failure');
    } finally {
        Event::forget('eloquent.creating: '.Permission::class);
    }

    expect(Company::query()->count())->toBe(0)
        ->and(DB::table('company_user')->count())->toBe(0)
        ->and(Role::query()->count())->toBe(0)
        ->and(Permission::query()->count())->toBe(26)
        ->and(DB::table('user_roles')->count())->toBe(0)
        ->and(DB::table('role_permissions')->count())->toBe(0);
});

it('rolls back company creation when owner permission attachment fails', function () {
    $creator = UserFactory::new()->create();
    $existingPermission = Permission::query()->where('name', 'company.update')->sole();
    Event::listen(QueryExecuted::class, function (QueryExecuted $query): void {
        if (str_contains($query->sql, 'role_permissions')) {
            throw new RuntimeException('Controlled role permission failure');
        }
    });

    try {
        expect(fn () => app(CreateCompany::class)->handle(['company_name' => 'Rolled Back'], $creator))
            ->toThrow(RuntimeException::class, 'Controlled role permission failure');
    } finally {
        Event::forget(QueryExecuted::class);
    }

    expect(Company::query()->count())->toBe(0)
        ->and(DB::table('company_user')->count())->toBe(0)
        ->and(Role::query()->count())->toBe(0)
        ->and(Permission::query()->whereKey($existingPermission)->exists())->toBeTrue()
        ->and(DB::table('user_roles')->count())->toBe(0)
        ->and(DB::table('role_permissions')->count())->toBe(0);
});

it('rolls back company creation when creator role assignment fails', function () {
    $creator = UserFactory::new()->create();
    $existingPermission = Permission::query()->where('name', 'company.update')->sole();
    Event::listen(QueryExecuted::class, function (QueryExecuted $query): void {
        if (str_contains($query->sql, 'user_roles')) {
            throw new RuntimeException('Controlled user role failure');
        }
    });

    try {
        expect(fn () => app(CreateCompany::class)->handle(['company_name' => 'Rolled Back'], $creator))
            ->toThrow(RuntimeException::class, 'Controlled user role failure');
    } finally {
        Event::forget(QueryExecuted::class);
    }

    expect(Company::query()->count())->toBe(0)
        ->and(DB::table('company_user')->count())->toBe(0)
        ->and(Role::query()->count())->toBe(0)
        ->and(Permission::query()->whereKey($existingPermission)->exists())->toBeTrue()
        ->and(DB::table('user_roles')->count())->toBe(0)
        ->and(DB::table('role_permissions')->count())->toBe(0);
});

it('allows one user to create multiple companies', function () {
    $creator = UserFactory::new()->create();

    $this->actingAs($creator)->post(route('company.store'), ['company_name' => 'First']);
    $this->actingAs($creator)->post(route('company.store'), ['company_name' => 'Second']);

    expect($creator->companies()->count())->toBe(2)
        ->and($creator->roles()->where('name', 'owner')->count())->toBe(2);
});

it('validates company profile fields', function () {
    $creator = UserFactory::new()->create();

    $this->actingAs($creator)->post(route('company.store'), [
        'company_name' => '',
        'email' => 'not-an-email',
    ])->assertInvalid(['company_name', 'email']);

    expect(Company::query()->count())->toBe(0);
});
