<?php

declare(strict_types=1);

use App\Models\Company\Branch;
use App\Models\Company\Warehouse;
use App\Models\Company\WarehouseLocation;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('forbids non-members from company settings branch and warehouse endpoints', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();

    $this->actingAs($user)->get(route('settings.edit', $company))->assertForbidden();
    $this->actingAs($user)->put(route('settings', $company), ['currency' => 'KES'])->assertForbidden();
    $this->actingAs($user)->get(route('branch.create', $company))->assertForbidden();
    $this->actingAs($user)->post(route('branch.store', $company), ['branch_name' => 'Main', 'code' => 'MAIN'])->assertForbidden();
    $this->actingAs($user)->get(route('warehouse.create', $company))->assertForbidden();
    $this->actingAs($user)->post(route('warehouse', $company), ['branch_id' => 1, 'warehouse_name' => 'Main', 'code' => 'MAIN'])->assertForbidden();
});

it('forbids non-members from warehouse location endpoints using the derived company', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'branch_name' => 'Main',
        'code' => 'MAIN',
    ]);
    $warehouse = Warehouse::query()->create([
        'branch_id' => $branch->id,
        'warehouse_name' => 'Main Warehouse',
        'code' => 'MAIN',
    ]);

    $this->actingAs($user)->get(route('location.create', $warehouse))->assertForbidden();
    $this->actingAs($user)->post(route('location', $warehouse), ['name' => 'A1', 'code' => 'A1'])->assertForbidden();
});

it('rejects a branch id belonging to another company', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $otherCompany = CompanyFactory::new()->create();
    $member->companies()->attach($company);
    $otherBranch = Branch::query()->create([
        'company_id' => $otherCompany->id,
        'branch_name' => 'Other',
        'code' => 'OTHER',
    ]);

    $this->actingAs($member)->post(route('warehouse', $company), [
        'branch_id' => $otherBranch->id,
        'warehouse_name' => 'Injected Warehouse',
        'code' => 'INJECTED',
    ])->assertInvalid(['branch_id']);

    expect(Warehouse::query()->count())->toBe(0);
});

it('creates a branch for an authorized company and redirects to its overview', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $member->companies()->attach($company);

    $response = $this->actingAs($member)->post(route('branch.store', $company), [
        'branch_name' => 'Main Branch',
        'code' => 'MAIN',
    ]);

    $response->assertRedirect(route('home', $company));
    $branch = Branch::query()->sole();
    expect($branch->company_id)->toBe($company->id)
        ->and($branch->branch_name)->toBe('Main Branch');
});

it('creates a warehouse through a branch in the authorized company and redirects to its overview', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $member->companies()->attach($company);
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'branch_name' => 'Main Branch',
        'code' => 'MAIN',
    ]);

    $response = $this->actingAs($member)->post(route('warehouse', $company), [
        'branch_id' => $branch->id,
        'warehouse_name' => 'Main Warehouse',
        'code' => 'MAIN',
    ]);

    $response->assertRedirect(route('home', $company));
    $warehouse = Warehouse::query()->sole();
    expect($warehouse->branch_id)->toBe($branch->id)
        ->and($warehouse->branch->company_id)->toBe($company->id);
});

it('creates a warehouse location for the derived company and redirects to its overview', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $member->companies()->attach($company);
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'branch_name' => 'Main Branch',
        'code' => 'MAIN',
    ]);
    $warehouse = Warehouse::query()->create([
        'branch_id' => $branch->id,
        'warehouse_name' => 'Main Warehouse',
        'code' => 'MAIN',
    ]);

    $response = $this->actingAs($member)->post(route('location', $warehouse), [
        'name' => 'A1',
        'code' => 'A1',
    ]);

    $response->assertRedirect(route('home', $company));
    $location = WarehouseLocation::query()->sole();
    expect($location->warehouse_id)->toBe($warehouse->id)
        ->and($location->warehouse->branch->company_id)->toBe($company->id);
});

it('exposes only the approved invitation entry review acceptance and company management routes', function () {
    expect(Route::has('invitations.create'))->toBeFalse()
        ->and(Route::has('invitations.store'))->toBeFalse()
        ->and(Route::getRoutes()->getByName('invitations.accept')->methods())->toBe(['POST'])
        ->and(Route::has('invitations.confirm'))->toBeFalse();
    $routes = collect(Route::getRoutes())->filter(fn ($route) => str_contains($route->getName() ?? '', 'invitations.'));
    expect($routes->map(fn ($route) => $route->getName())->sort()->values()->all())->toBe([
        'company.invitations.cancel', 'company.invitations.create', 'company.invitations.index', 'company.invitations.resend', 'company.invitations.store',
        'invitations.accept', 'invitations.entry', 'invitations.review',
    ]);
    foreach ($routes as $route) {
        if (str_starts_with($route->getName(), 'company.') || $route->getName() === 'invitations.accept') {
            expect($route->gatherMiddleware())->toContain('auth', 'auth.session', 'verified');
        }
    }
});

it('redirects an otherwise authorized unverified user away from every company endpoint', function () {
    $user = UserFactory::new()->unverified()->create();
    $company = CompanyFactory::new()->create(['company_name' => 'Unchanged Company']);
    $user->companies()->attach($company);
    $permission = DB::table('permissions')->where('name', 'company.update')->first();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role, ['company_id' => $company->id]);
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'branch_name' => 'Existing Branch',
        'code' => 'EXISTING',
    ]);
    $warehouse = Warehouse::query()->create([
        'branch_id' => $branch->id,
        'warehouse_name' => 'Existing Warehouse',
        'code' => 'EXISTING',
    ]);
    $verificationNotice = route('verification.notice');

    $responses = [
        $this->actingAs($user)->get(route('home', $company)),
        $this->actingAs($user)->get(route('company.edit', $company)),
        $this->actingAs($user)->patch(route('company.update', $company), ['company_name' => 'Changed Company']),
        $this->actingAs($user)->get(route('settings.edit', $company)),
        $this->actingAs($user)->put(route('settings', $company), ['currency' => 'USD']),
        $this->actingAs($user)->get(route('branch.create', $company)),
        $this->actingAs($user)->post(route('branch.store', $company), ['branch_name' => 'Injected Branch', 'code' => 'INJECTED']),
        $this->actingAs($user)->get(route('warehouse.create', $company)),
        $this->actingAs($user)->post(route('warehouse', $company), [
            'branch_id' => $branch->id,
            'warehouse_name' => 'Injected Warehouse',
            'code' => 'INJECTED',
        ]),
        $this->actingAs($user)->get(route('location.create', $warehouse)),
        $this->actingAs($user)->post(route('location', $warehouse), ['name' => 'Injected Location', 'code' => 'INJECTED']),
    ];

    foreach ($responses as $response) {
        $response->assertRedirect($verificationNotice);
    }

    expect($company->fresh()->company_name)->toBe('Unchanged Company')
        ->and(DB::table('company_settings')->count())->toBe(0)
        ->and(Branch::query()->count())->toBe(1)
        ->and(Warehouse::query()->count())->toBe(1)
        ->and(WarehouseLocation::query()->count())->toBe(0);
});
