<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Actions\Customer\CreateCustomer;
use App\Enums\CustomerPermission;
use App\Models\Auth\Permission;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerGroupMembership;
use Database\Factories\CustomerAddressFactory;
use Database\Factories\CustomerContactFactory;
use Database\Factories\CustomerFactory;
use Database\Factories\CustomerGroupFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('requires company membership and the Customer permission before exposing records', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Customer Tenant'], $owner);
    $outsider = UserFactory::new()->create();
    $member = UserFactory::new()->create();
    $company->users()->attach($member);

    $this->get(route('company.customers.index', $company))->assertRedirect(route('login'));
    $this->actingAs($outsider)->get(route('company.customers.index', $company))->assertForbidden();
    $this->actingAs($member)->get(route('company.customers.index', $company))->assertForbidden();
    $this->actingAs($owner)->get(route('company.customers.index', $company))->assertOk()->assertSee('Customers');
});

it('protects group, contact and address endpoints from guests and unauthorized members', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Protected Details'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    $member = UserFactory::new()->create();
    $company->users()->attach($member);
    $paths = [
        route('company.customer-groups.index', $company),
        route('company.customers.contacts.create', [$company, $customer]),
        route('company.customers.addresses.create', [$company, $customer]),
    ];

    foreach ($paths as $path) {
        $this->get($path)->assertRedirect(route('login'));
    }
    foreach ($paths as $path) {
        $this->actingAs($member)->get($path)->assertForbidden();
    }
});

it('numbers new customers, preserves codes, and rejects foreign or inactive group input', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Numbered'], $owner);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other'], UserFactory::new()->create());
    $group = CustomerGroupFactory::new()->create(['company_id' => $company->id]);
    $foreignGroup = CustomerGroupFactory::new()->create(['company_id' => $other->id]);

    $this->actingAs($owner)->post(route('company.customers.store', $company), [
        'name' => 'Acme', 'email' => 'Sales@Example.COM', 'customer_group_id' => $group->id,
    ])->assertRedirect();
    $customer = Customer::query()->sole();
    expect($customer->code)->toMatch('/^CUS-\d{4}-000001$/')
        ->and($customer->email)->toBe('sales@example.com')
        ->and($customer->group?->id)->toBe($group->id)
        ->and($customer->credit_limit)->toBe('0.0000')
        ->and($customer->payment_terms_days)->toBe(0);

    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => 'Acme Updated', 'code' => 'FORGED',
    ])->assertSessionHasErrors('code');
    expect($customer->fresh()->code)->toBe($customer->code);
    $this->actingAs($owner)->post(route('company.customers.store', $company), [
        'name' => 'Foreign group', 'customer_group_id' => $foreignGroup->id,
    ])->assertSessionHasErrors('customer_group_id');
    $group->update(['is_active' => false]);
    $this->actingAs($owner)->post(route('company.customers.store', $company), [
        'name' => 'Inactive group', 'customer_group_id' => $group->id,
    ])->assertSessionHasErrors('customer_group_id');
    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => 'Acme Updated', 'customer_group_id' => $group->id,
    ])->assertRedirect();
    expect($customer->fresh()->group?->id)->toBe($group->id);
    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => 'Acme Again',
    ])->assertRedirect();
    expect($customer->fresh()->group?->id)->toBe($group->id);
});

it('skips reserved legacy codes and allows the same generated code in another company', function () {
    $firstOwner = UserFactory::new()->create();
    $secondOwner = UserFactory::new()->create();
    $firstCompany = app(CreateCompany::class)->handle(['company_name' => 'First Numbers'], $firstOwner);
    $secondCompany = app(CreateCompany::class)->handle(['company_name' => 'Second Numbers'], $secondOwner);
    $reserved = sprintf('CUS-%d-000001', now()->year);
    CustomerFactory::new()->create(['company_id' => $firstCompany->id, 'code' => $reserved]);

    $this->actingAs($firstOwner)->post(route('company.customers.store', $firstCompany), ['name' => 'First New'])->assertRedirect();
    $this->actingAs($secondOwner)->post(route('company.customers.store', $secondCompany), ['name' => 'Second New'])->assertRedirect();

    expect($firstCompany->customers()->where('name', 'First New')->sole()->code)->toBe(sprintf('CUS-%d-000002', now()->year))
        ->and($secondCompany->customers()->where('name', 'Second New')->sole()->code)->toBe($reserved);
});

it('assigns, replaces and removes a single company group through Customer updates', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Memberships'], $owner);
    $first = CustomerGroupFactory::new()->create(['company_id' => $company->id]);
    $second = CustomerGroupFactory::new()->create(['company_id' => $company->id]);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    expect($customer->group)->toBeNull();

    foreach ([$first, $second] as $group) {
        $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
            'name' => $customer->name, 'customer_group_id' => $group->id,
        ])->assertRedirect();
        expect($customer->fresh()->group?->id)->toBe($group->id)
            ->and($customer->groupMembership()->count())->toBe(1);
    }

    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => $customer->name, 'customer_group_id' => null,
    ])->assertRedirect();
    expect($customer->fresh()->group)->toBeNull()
        ->and($customer->groupMembership()->count())->toBe(0);
});

it('rejects cross-company group changes and forged ownership without changing a Customer', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Tenant A'], $owner);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Tenant B'], $owner);
    $foreignGroup = CustomerGroupFactory::new()->create(['company_id' => $other->id]);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => 'Forged', 'customer_group_id' => $foreignGroup->id,
    ])->assertSessionHasErrors('customer_group_id');
    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => 'Forged', 'company_id' => $other->id,
    ])->assertSessionHasErrors('company_id');
    expect($customer->fresh()->name)->not->toBe('Forged')
        ->and($customer->group)->toBeNull();
});

it('rolls back Customer creation and its number when group assignment fails', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Atomic Customer'], $owner);
    $group = CustomerGroupFactory::new()->create(['company_id' => $company->id]);
    Event::listen('eloquent.creating: '.CustomerGroupMembership::class, fn () => throw new RuntimeException('Controlled membership failure'));

    try {
        expect(fn () => app(CreateCustomer::class)->handle($company, $owner, [
            'name' => 'Rolled Back', 'customer_group_id' => $group->id,
        ]))->toThrow(RuntimeException::class, 'Controlled membership failure');
    } finally {
        Event::forget('eloquent.creating: '.CustomerGroupMembership::class);
    }

    expect($company->customers()->count())->toBe(0)
        ->and($company->numberSequences()->where('type', 'customer')->exists())->toBeFalse();
});

it('separates commercial permissions from ordinary Customer updates', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Terms'], $owner);
    $editor = UserFactory::new()->create();
    $company->users()->attach($editor);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $role->permissions()->attach(Permission::query()->where('name', CustomerPermission::Update->value)->sole());
    $editor->roles()->attach($role, ['company_id' => $company->id, 'assigned_by' => $owner->id]);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($editor)->patch(route('company.customers.update', [$company, $customer]), [
        'name' => 'Edited', 'credit_limit' => '500.0000',
    ])->assertSessionHasErrors('credit_limit');
    $this->actingAs($editor)->patch(route('company.customers.commercial-terms.update', [$company, $customer]), [
        'credit_limit' => '500.0000', 'payment_terms_days' => 30,
    ])->assertForbidden();
    $this->actingAs($owner)->patch(route('company.customers.commercial-terms.update', [$company, $customer]), [
        'credit_limit' => '500.1234', 'payment_terms_days' => 3650,
    ])->assertRedirect();
    $customer->refresh();
    expect($customer->credit_limit)->toBe('500.1234')
        ->and($customer->payment_terms_days)->toBe(3650);
});

it('validates commercial zero values, decimal precision and payment term bounds', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Terms Validation'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($owner)->patch(route('company.customers.commercial-terms.update', [$company, $customer]), [
        'credit_limit' => '0', 'payment_terms_days' => 0,
    ])->assertRedirect();
    expect($customer->fresh()->credit_limit)->toBe('0.0000')
        ->and($customer->payment_terms_days)->toBe(0);

    foreach ([['1.23456', 0], ['-1', 0], [1.25, 0], ['10', -1], ['10', 3651]] as [$limit, $days]) {
        $response = $this->actingAs($owner)->patch(route('company.customers.commercial-terms.update', [$company, $customer]), [
            'credit_limit' => $limit, 'payment_terms_days' => $days,
        ]);
        $response->assertSessionHasErrors();
    }
    expect($customer->fresh()->credit_limit)->toBe('0.0000')
        ->and($customer->payment_terms_days)->toBe(0);
});

it('requires credit permission to submit commercial values during creation', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Credit Create'], $owner);
    $creator = UserFactory::new()->create();
    $company->users()->attach($creator);
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $role->permissions()->attach(Permission::query()->where('name', CustomerPermission::Create->value)->sole());
    $creator->roles()->attach($role, ['company_id' => $company->id, 'assigned_by' => $owner->id]);

    $this->actingAs($creator)->post(route('company.customers.store', $company), [
        'name' => 'Forged Credit', 'credit_limit' => '100.0000', 'payment_terms_days' => 30,
    ])->assertSessionHasErrors(['credit_limit', 'payment_terms_days']);
    expect($company->customers()->count())->toBe(0);
    $this->actingAs($creator)->post(route('company.customers.store', $company), [
        'name' => 'Default Credit',
    ])->assertRedirect();
    expect($company->customers()->sole()->credit_limit)->toBe('0.0000');

    $this->actingAs($owner)->post(route('company.customers.store', $company), [
        'name' => 'Approved Credit', 'credit_limit' => '100.1234', 'payment_terms_days' => 30,
    ])->assertRedirect();
    expect($company->customers()->where('name', 'Approved Credit')->sole()->credit_limit)->toBe('100.1234');
});

it('serializes primary contacts and per-type default addresses', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Details'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($owner)->post(route('company.customers.contacts.store', [$company, $customer]), ['name' => 'First'])->assertRedirect();
    $this->actingAs($owner)->post(route('company.customers.contacts.store', [$company, $customer]), ['name' => 'Second'])->assertRedirect();
    $first = $customer->contacts()->where('name', 'First')->sole();
    $second = $customer->contacts()->where('name', 'Second')->sole();
    expect($first->is_primary)->toBeTrue()->and($second->is_primary)->toBeFalse();
    $this->actingAs($owner)->patch(route('company.customers.contacts.primary', [$company, $customer, $second]))->assertRedirect();
    expect($second->fresh()->is_primary)->toBeTrue();
    $this->actingAs($owner)->delete(route('company.customers.contacts.destroy', [$company, $customer, $second]))->assertRedirect();
    expect($first->fresh()->is_primary)->toBeTrue();

    $this->actingAs($owner)->post(route('company.customers.addresses.store', [$company, $customer]), [
        'type' => 'billing', 'line_1' => 'First street',
    ])->assertRedirect();
    $this->actingAs($owner)->post(route('company.customers.addresses.store', [$company, $customer]), [
        'type' => 'billing', 'line_1' => 'Second street', 'is_default' => false,
    ])->assertRedirect();
    $firstAddress = $customer->addresses()->where('line_1', 'First street')->sole();
    $secondAddress = $customer->addresses()->where('line_1', 'Second street')->sole();
    expect($firstAddress->is_default)->toBeTrue()->and($secondAddress->is_default)->toBeFalse();
    $this->actingAs($owner)->patch(route('company.customers.addresses.default', [$company, $customer, $secondAddress]))->assertRedirect();
    expect($secondAddress->fresh()->is_default)->toBeTrue();
    $this->actingAs($owner)->delete(route('company.customers.addresses.destroy', [$company, $customer, $secondAddress]))->assertRedirect();
    expect($firstAddress->fresh()->is_default)->toBeTrue();
});

it('validates contact and address ownership, types and edited values', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Validated Details'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($owner)->post(route('company.customers.contacts.store', [$company, $customer]), [
        'name' => 'Forged', 'customer_id' => 999,
    ])->assertSessionHasErrors('customer_id');
    $this->actingAs($owner)->post(route('company.customers.addresses.store', [$company, $customer]), [
        'type' => 'warehouse', 'line_1' => 'Bad Lane',
    ])->assertSessionHasErrors('type');
    expect($customer->contacts()->count())->toBe(0)
        ->and($customer->addresses()->count())->toBe(0);

    $this->actingAs($owner)->post(route('company.customers.contacts.store', [$company, $customer]), [
        'name' => 'Good', 'email' => 'GOOD@EXAMPLE.COM',
    ])->assertRedirect();
    $contact = $customer->contacts()->sole();
    expect($contact->email)->toBe('good@example.com');
    $this->actingAs($owner)->patch(route('company.customers.contacts.update', [$company, $customer, $contact]), [
        'name' => 'Updated', 'phone' => '+254001234567',
    ])->assertRedirect();
    $contact->refresh();
    expect($contact->name)->toBe('Updated')
        ->and($contact->phone)->toBe('+254001234567');

    $this->actingAs($owner)->post(route('company.customers.addresses.store', [$company, $customer]), [
        'type' => 'other', 'line_1' => 'Good Lane', 'is_default' => false,
    ])->assertRedirect();
    $address = $customer->addresses()->sole();
    expect($address->is_default)->toBeTrue();
    $this->actingAs($owner)->patch(route('company.customers.addresses.update', [$company, $customer, $address]), [
        'type' => 'other', 'line_1' => 'Updated Lane',
    ])->assertRedirect();
    expect($address->fresh()->line_1)->toBe('Updated Lane');
});

it('keeps cross-company nested records hidden after company authorization', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'First'], $owner);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Second'], $owner);
    $foreign = CustomerFactory::new()->create(['company_id' => $other->id]);

    $this->actingAs($owner)->get(route('company.customers.show', [$company, $foreign]))->assertNotFound();
    $this->actingAs($owner)->patch(route('company.customers.update', [$company, $foreign]), ['name' => 'Forged'])->assertNotFound();
    expect($foreign->fresh()->name)->not->toBe('Forged');
});

it('renders company-scoped Customer and group pages with UUID links and form labels', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Rendered'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    $group = CustomerGroupFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($owner)->get(route('company.customers.create', $company))
        ->assertOk()->assertSee('name="name"', false)->assertSee('name="customer_group_id"', false);
    $this->actingAs($owner)->get(route('company.customers.show', [$company, $customer]))
        ->assertOk()->assertSee($customer->name)->assertSee($customer->uuid);
    $this->actingAs($owner)->get(route('company.customers.edit', [$company, $customer]))->assertOk();
    $this->actingAs($owner)->get(route('company.customer-groups.index', $company))->assertOk()->assertSee($group->name);
    $this->actingAs($owner)->get(route('company.customer-groups.create', $company))->assertOk();
    $this->actingAs($owner)->get(route('company.customer-groups.edit', [$company, $group]))->assertOk();
    $this->actingAs($owner)->get(route('company.customers.contacts.create', [$company, $customer]))->assertOk();
    $this->actingAs($owner)->get(route('company.customers.addresses.create', [$company, $customer]))->assertOk();
});

it('renders long Customer details without truncation in wrapping containers', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Long Customer Details'], $owner);
    $longValue = str_repeat('A', 200);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id, 'name' => $longValue]);
    CustomerContactFactory::new()->create(['customer_id' => $customer->id, 'name' => $longValue]);
    CustomerAddressFactory::new()->create(['customer_id' => $customer->id, 'line_1' => $longValue]);

    $this->actingAs($owner)->get(route('company.customers.show', [$company, $customer]))
        ->assertOk()
        ->assertSee($longValue)
        ->assertSee('break-words', false);
});

it('shows validation errors and retains Customer form input', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Validation UI'], $owner);

    $this->actingAs($owner)->followingRedirects()->from(route('company.customers.create', $company))
        ->post(route('company.customers.store', $company), [
            'name' => '', 'email' => 'invalid', 'phone' => '0012345',
        ])->assertOk()->assertSee('The name field is required.')
        ->assertSee('value="0012345"', false)
        ->assertSee('for="name"', false);
    expect($company->customers()->count())->toBe(0);
});

it('filters customer search, status and group while preserving pagination parameters', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Filters'], $owner);
    $group = CustomerGroupFactory::new()->create(['company_id' => $company->id]);
    CustomerFactory::new()->count(16)->create(['company_id' => $company->id]);
    $archived = CustomerFactory::new()->create(['company_id' => $company->id, 'name' => 'Archived Needle', 'is_active' => false]);
    $grouped = CustomerFactory::new()->create(['company_id' => $company->id, 'name' => 'Grouped Needle']);
    $grouped->groupMembership()->create(['customer_group_id' => $group->id, 'company_id' => $company->id]);

    $this->actingAs($owner)->get(route('company.customers.index', $company))->assertOk()
        ->assertSee('page=2', false)->assertDontSee('Archived Needle');
    $this->actingAs($owner)->get(route('company.customers.index', [$company, 'status' => 'archived', 'search' => 'Needle']))
        ->assertOk()->assertSee($archived->name)->assertDontSee($grouped->name);
    $this->actingAs($owner)->get(route('company.customers.index', [$company, 'status' => 'all', 'search' => 'Needle', 'group' => $group->uuid]))
        ->assertOk()->assertSee($grouped->name)->assertDontSee($archived->name);
});

it('archives and restores customers and groups without releasing their codes', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Lifecycle'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    $group = CustomerGroupFactory::new()->create(['company_id' => $company->id]);

    $this->actingAs($owner)->patch(route('company.customers.archive', [$company, $customer]))->assertRedirect();
    $this->actingAs($owner)->patch(route('company.customer-groups.archive', [$company, $group]))->assertRedirect();
    expect($customer->fresh()->is_active)->toBeFalse()->and($group->fresh()->is_active)->toBeFalse();
    $this->actingAs($owner)->get(route('company.customers.index', [$company, 'status' => 'archived']))->assertOk()->assertSee($customer->name);
    $this->actingAs($owner)->get(route('company.customer-groups.index', [$company, 'status' => 'archived']))->assertOk()->assertSee($group->name);

    $this->actingAs($owner)->patch(route('company.customers.restore', [$company, $customer]))->assertRedirect();
    $this->actingAs($owner)->patch(route('company.customer-groups.restore', [$company, $group]))->assertRedirect();
    expect($customer->fresh()->is_active)->toBeTrue()->and($group->fresh()->is_active)->toBeTrue()
        ->and($customer->code)->toBe($customer->fresh()->code)
        ->and($group->code)->toBe($group->fresh()->code);
});

it('manages groups with company-scoped codes and prohibits forged ownership', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Group Management'], $owner);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Other Groups'], $owner);
    $foreign = CustomerGroupFactory::new()->create(['company_id' => $other->id, 'code' => 'RETAIL']);

    $this->actingAs($owner)->post(route('company.customer-groups.store', $company), [
        'name' => 'Retail', 'code' => 'retail',
    ])->assertRedirect();
    $group = $company->customerGroups()->sole();
    expect($group->code)->toBe('RETAIL')
        ->and($foreign->fresh()->code)->toBe('RETAIL');

    $this->actingAs($owner)->post(route('company.customer-groups.store', $company), [
        'name' => 'Duplicate', 'code' => 'RETAIL',
    ])->assertSessionHasErrors('code');
    $this->actingAs($owner)->patch(route('company.customer-groups.update', [$company, $group]), [
        'name' => 'Updated Retail', 'company_id' => $other->id,
    ])->assertSessionHasErrors('company_id');
    expect($group->fresh()->name)->toBe('Retail');

    $this->actingAs($owner)->patch(route('company.customer-groups.update', [$company, $group]), [
        'name' => 'Updated Retail',
    ])->assertRedirect();
    expect($group->fresh()->name)->toBe('Updated Retail');
});

it('returns 404 for foreign nested groups contacts and addresses', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Left'], $owner);
    $other = app(CreateCompany::class)->handle(['company_name' => 'Right'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    $foreign = CustomerFactory::new()->create(['company_id' => $other->id]);
    $group = CustomerGroupFactory::new()->create(['company_id' => $other->id]);
    $contact = $foreign->contacts()->create(['name' => 'Foreign']);
    $address = $foreign->addresses()->create(['type' => 'billing', 'line_1' => 'Foreign lane']);

    $this->actingAs($owner)->get(route('company.customer-groups.edit', [$company, $group]))->assertNotFound();
    $this->actingAs($owner)->get(route('company.customers.contacts.edit', [$company, $foreign, $contact]))->assertNotFound();
    $this->actingAs($owner)->get(route('company.customers.addresses.edit', [$company, $foreign, $address]))->assertNotFound();
    $this->actingAs($owner)->delete(route('company.customers.contacts.destroy', [$company, $customer, $contact]))->assertNotFound();
    $this->actingAs($owner)->delete(route('company.customers.addresses.destroy', [$company, $customer, $address]))->assertNotFound();
});

it('repairs address defaults when moving an address to another type', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Moves'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    $first = $customer->addresses()->create(['type' => 'billing', 'line_1' => 'First', 'is_default' => true]);
    $second = $customer->addresses()->create(['type' => 'billing', 'line_1' => 'Second', 'is_default' => false]);

    $this->actingAs($owner)->patch(route('company.customers.addresses.update', [$company, $customer, $first]), [
        'type' => 'shipping', 'line_1' => 'First',
    ])->assertRedirect();
    $first->refresh();
    expect($first->is_default)->toBeTrue()
        ->and($first->type->value)->toBe('shipping')
        ->and($second->fresh()->is_default)->toBeTrue();
});

it('keeps one default in each type when a default address moves into an occupied type', function () {
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Occupied Moves'], $owner);
    $customer = CustomerFactory::new()->create(['company_id' => $company->id]);
    $moving = $customer->addresses()->create(['type' => 'billing', 'line_1' => 'Moving', 'is_default' => true]);
    $remaining = $customer->addresses()->create(['type' => 'billing', 'line_1' => 'Remaining', 'is_default' => false]);
    $shipping = $customer->addresses()->create(['type' => 'shipping', 'line_1' => 'Existing', 'is_default' => true]);

    $this->actingAs($owner)->patch(route('company.customers.addresses.update', [$company, $customer, $moving]), [
        'type' => 'shipping', 'line_1' => 'Moving', 'is_default' => true,
    ])->assertRedirect();

    expect($moving->fresh()->is_default)->toBeTrue()
        ->and($remaining->fresh()->is_default)->toBeTrue()
        ->and($shipping->fresh()->is_default)->toBeFalse()
        ->and($customer->addresses()->where('type', 'shipping')->where('is_default', true)->count())->toBe(1)
        ->and($customer->addresses()->where('type', 'billing')->where('is_default', true)->count())->toBe(1);
});
