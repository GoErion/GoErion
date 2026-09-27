<?php

declare(strict_types=1);

use App\Enums\SupplierPermission;
use App\Models\Auth\Permission;
use App\Models\Supplier\Supplier;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\SupplierAddressFactory;
use Database\Factories\SupplierContactFactory;
use Database\Factories\SupplierFactory;
use Database\Factories\UserFactory;
use Database\Seeders\PermissionSeeder;
use Tests\Support\RefreshSupplierDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshSupplierDatabase::class);

it('keeps the five permissions independent and scoped to Company membership', function () {
    [$owner, $company] = SupplierFixture::owner();
    [$otherOwner, $other] = SupplierFixture::owner();
    $abilities = ['viewAny' => SupplierPermission::View, 'create' => SupplierPermission::Create, 'update' => SupplierPermission::Update, 'archive' => SupplierPermission::Archive, 'manageTerms' => SupplierPermission::TermsManage];
    foreach (SupplierPermission::cases() as $permission) {
        $member = SupplierFixture::member($company, [$permission->value]);
        foreach ($abilities as $ability => $required) {
            expect($member->can($ability, [Supplier::class, $company]))->toBe($required === $permission);
            expect($member->can($ability, [Supplier::class, $other]))->toBeFalse();
        }
    }
    $unverified = UserFactory::new()->unverified()->create();
    $company->users()->attach($unverified);
    $unverified->roles()->attach($company->ownerRole, ['company_id' => $company->id]);
    expect($unverified->can('create', [Supplier::class, $company]))->toBeFalse();
});

it('grants only system owners and preserves later grants on permission rollback', function () {
    [$owner, $company] = SupplierFixture::owner();
    $other = CompanyFactory::new()->create();
    $namedOwner = RoleFactory::new()->create(['company_id' => $other->id, 'name' => 'owner', 'is_system' => false]);
    $member = SupplierFixture::member($company);
    $migration = require glob(database_path('migrations/*_grant_supplier_permissions_to_owner_roles.php'))[0];
    $migration->up();
    $migration->up();
    app(PermissionSeeder::class)->run();
    app(PermissionSeeder::class)->run();
    $names = array_column(SupplierPermission::cases(), 'value');
    expect($company->ownerRole->permissions()->whereIn('name', $names)->count())->toBe(5);
    expect($namedOwner->permissions()->count())->toBe(0);
    expect($member->roles()->sole()->permissions()->count())->toBe(0);
    expect(Permission::query()->assignableToCustomRoles()->whereIn('name', $names)->count())->toBe(5);
    $role = $member->roles()->sole();
    $role->permissions()->attach(Permission::query()->where('name', 'suppliers.view')->sole());
    $migration->down();
    expect($member->hasCompanyPermission($company, 'suppliers.view'))->toBeTrue();
    $response = $this->actingAs($owner)->get(route('company.roles.create', $company));
    foreach ($names as $name) {
        $response->assertSee($name);
    }
});

it('protects every Supplier route from guests and unauthorized members', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $contact = SupplierContactFactory::new()->create(['supplier_id' => $supplier->id]);
    $address = SupplierAddressFactory::new()->create(['supplier_id' => $supplier->id]);
    $routes = [
        ['get', 'index', [$company]], ['get', 'create', [$company]], ['post', 'store', [$company]],
        ['get', 'show', [$company, $supplier]], ['get', 'edit', [$company, $supplier]], ['patch', 'update', [$company, $supplier]],
        ['patch', 'archive', [$company, $supplier]], ['patch', 'restore', [$company, $supplier]], ['patch', 'commercial-terms.update', [$company, $supplier]],
    ];
    foreach (['contacts' => $contact, 'addresses' => $address] as $type => $child) {
        array_push($routes, ['get', "$type.create", [$company, $supplier]], ['post', "$type.store", [$company, $supplier]], ['get', "$type.edit", [$company, $supplier, $child]], ['patch', "$type.update", [$company, $supplier, $child]], ['delete', "$type.destroy", [$company, $supplier, $child]], ['patch', "$type.".($type === 'contacts' ? 'primary' : 'default'), [$company, $supplier, $child]]);
    }
    foreach ($routes as [$method, $name, $args]) {
        $this->{$method}(route('company.suppliers.'.$name, $args))->assertRedirect(route('login'));
    }
    $actor = SupplierFixture::member($company);
    $this->actingAs($actor);
    foreach ($routes as [$method, $name, $args]) {
        $this->{$method}(route('company.suppliers.'.$name, $args))->assertForbidden();
    }
    $this->actingAs(UserFactory::new()->unverified()->create())->get(route('company.suppliers.index', $company))->assertRedirect(route('verification.notice'));
});

it('hides foreign Supplier UUIDs and wrong-parent children after Company authorization', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $foreign = SupplierFactory::new()->create();
    $contact = SupplierContactFactory::new()->create(['supplier_id' => $foreign->id]);
    $address = SupplierAddressFactory::new()->create(['supplier_id' => $foreign->id]);
    $this->actingAs($owner)->get(route('company.suppliers.show', [$company, $foreign]))->assertNotFound();
    $this->get(route('company.suppliers.contacts.edit', [$company, $supplier, $contact]))->assertNotFound();
    $this->get(route('company.suppliers.addresses.edit', [$company, $supplier, $address]))->assertNotFound();
    $this->get(route('company.suppliers.index', $foreign->company))->assertForbidden();
});

it('separates ordinary updates from terms and permits terms-only operators', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $editor = SupplierFixture::member($company, ['suppliers.update']);
    $this->actingAs($editor)->patch(route('company.suppliers.update', [$company, $supplier]), ['name' => 'Changed', 'payment_terms_days' => 0])->assertSessionHasErrors('payment_terms_days');
    $this->patch(route('company.suppliers.commercial-terms.update', [$company, $supplier]), ['currency' => 'USD', 'payment_terms_days' => 30])->assertForbidden();
    $terms = SupplierFixture::member($company, ['suppliers.terms.manage']);
    $this->actingAs($terms)->patch(route('company.suppliers.commercial-terms.update', [$company, $supplier]), ['currency' => 'usd', 'payment_terms_days' => 3650, 'lead_time_days' => null])->assertRedirect();
    expect($supplier->fresh()->currency)->toBe('USD')->and($supplier->fresh()->payment_terms_days)->toBe(3650);
});

it('rejects submitted Supplier identity changes without altering the profile', function (string $field, mixed $value) {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $before = $supplier->fresh()->getAttributes();

    $this->actingAs($owner)->patch(route('company.suppliers.update', [$company, $supplier]), [
        'name' => 'Attempted rename', $field => $value,
    ])->assertSessionHasErrors($field);

    expect($supplier->fresh()->getAttributes())->toBe($before);
})->with([['code', 'FORGED-001'], ['company_id', 999], ['uuid', 'forged']]);

it('rejects submitted child ownership instead of trusting request relationships', function (string $child, string $field) {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $foreign = SupplierFactory::new()->create();
    $values = $child === 'contacts' ? ['name' => 'Attempt'] : ['type' => 'billing', 'line_1' => 'Attempt'];
    $values[$field] = $field === 'company_id' ? $foreign->company_id : $foreign->id;

    $this->actingAs($owner)->post(route('company.suppliers.'.$child.'.store', [$company, $supplier]), $values)
        ->assertSessionHasErrors($field);

    expect($supplier->{$child}()->count())->toBe(0);
    expect($foreign->{$child}()->count())->toBe(0);
})->with(['contacts', 'addresses'])->with(['company_id', 'supplier_id']);
