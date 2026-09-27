<?php

declare(strict_types=1);

use App\Actions\Supplier\ArchiveSupplier;
use App\Actions\Supplier\CreateSupplier;
use App\Actions\Supplier\CreateSupplierAddress;
use App\Actions\Supplier\CreateSupplierContact;
use App\Actions\Supplier\RemoveSupplierAddress;
use App\Actions\Supplier\RemoveSupplierContact;
use App\Actions\Supplier\RestoreSupplier;
use App\Actions\Supplier\SetDefaultSupplierAddress;
use App\Actions\Supplier\SetPrimarySupplierContact;
use App\Actions\Supplier\UpdateSupplier;
use App\Actions\Supplier\UpdateSupplierAddress;
use App\Actions\Supplier\UpdateSupplierContact;
use App\Models\Supplier\Supplier;
use Database\Factories\SupplierFactory;
use Illuminate\Support\Facades\Event;
use Tests\Support\RefreshSupplierDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshSupplierDatabase::class);

it('creates minimal and full profiles with normalized commercial defaults and correct redirects', function () {
    [$owner, $company] = SupplierFixture::owner();
    $this->travelTo(now()->setDate(2026, 12, 31));
    $this->actingAs($owner)->post(route('company.suppliers.store', $company), ['name' => '  Hardware Supply  '])->assertRedirect();
    $supplier = $company->suppliers()->sole();
    expect($supplier->name)->toBe('Hardware Supply')->and($supplier->code)->toBe('SUP-2026-000001')->and($supplier->currency)->toBe('KES')->and($supplier->payment_terms_days)->toBe(0)->and($supplier->lead_time_days)->toBeNull();
    $response = $this->post(route('company.suppliers.store', $company), ['name' => 'Full Supply', 'legal_name' => 'Full Supply Ltd', 'tax_pin' => 'P123', 'registration_number' => 'R123', 'email' => ' SALES@EXAMPLE.COM ', 'phone' => '+254 (0) 700-123', 'website' => 'https://example.com', 'notes' => 'Delivery instructions', 'currency' => 'usd', 'payment_terms_days' => 30, 'lead_time_days' => 0]);
    $full = $company->suppliers()->where('name', 'Full Supply')->sole();
    $response->assertRedirect(route('company.suppliers.show', [$company, $full]));
    expect($full->email)->toBe('sales@example.com')->and($full->phone)->toBe('+254 (0) 700-123')->and($full->currency)->toBe('USD')->and($full->lead_time_days)->toBe(0)->and($full->notes)->toBe('Delivery instructions');
    $this->travelTo(now()->setDate(2027, 1, 1));
    expect(app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Next year'])->code)->toBe('SUP-2027-000003');
    [$otherOwner, $other] = SupplierFixture::owner();
    expect(app(CreateSupplier::class)->handle($other, $otherOwner, ['name' => 'Independent'])->code)->toBe('SUP-2027-000001');
});

it('initializes above all legacy years and skips collisions without changing codes', function () {
    [$owner, $company] = SupplierFixture::owner();
    SupplierFactory::new()->create(['company_id' => $company->id, 'code' => 'SUP-2020-000250']);
    SupplierFactory::new()->create(['company_id' => $company->id, 'code' => 'MANUAL-999999']);
    $supplier = app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Generated']);
    expect($supplier->code)->toBe('SUP-'.now()->year.'-000251');
    $company->numberSequences()->where('type', 'supplier')->update(['next_number' => 251]);
    $second = app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Collision']);
    expect($second->code)->toBe('SUP-'.now()->year.'-000252');
    app(UpdateSupplier::class)->handle($company, $supplier, $owner, ['name' => 'Renamed']);
    app(ArchiveSupplier::class)->handle($company, $supplier, $owner);
    app(RestoreSupplier::class)->handle($company, $supplier, $owner);
    expect($supplier->fresh()->code)->toBe($supplier->code);
});

it('rolls back failed creation and number allocation', function (bool $existing) {
    [$owner, $company] = SupplierFixture::owner();
    if ($existing) {
        app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Before']);
    }
    Event::listen('eloquent.creating: '.Supplier::class, fn () => throw new RuntimeException('Controlled failure'));
    try {
        expect(fn () => app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Fails']))->toThrow(RuntimeException::class, 'Controlled failure');
    } finally {
        Event::forget('eloquent.creating: '.Supplier::class);
    }
    expect($company->suppliers()->count())->toBe($existing ? 1 : 0);
    expect($company->numberSequences()->where('type', 'supplier')->value('next_number'))->toBe($existing ? 2 : null);
    expect(app(CreateSupplier::class)->handle($company, $owner, ['name' => 'After'])->code)->toBe('SUP-'.now()->year.($existing ? '-000002' : '-000001'));
})->with([false, true]);

it('rejects invalid or exhausted counters without resetting them', function (int $counter, string $prefix) {
    [$owner, $company] = SupplierFixture::owner();
    $company->numberSequences()->create(['type' => 'supplier', 'prefix' => $prefix, 'next_number' => $counter]);
    expect(fn () => app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Rejected']))->toThrow(DomainException::class);
    expect($company->suppliers()->count())->toBe(0);
    expect($company->numberSequences()->sole()->next_number)->toBe($counter);
})->with([[0, 'SUP'], [-1, 'SUP'], [2147483647, 'SUP'], [1, 'WRONG']]);

it('rejects malformed profile and commercial input without allocating a number', function (string $field, mixed $value) {
    [$owner, $company] = SupplierFixture::owner();
    $this->actingAs($owner)->post(route('company.suppliers.store', $company), ['name' => 'Valid', $field => $value])->assertSessionHasErrors($field);
    expect($company->suppliers()->count())->toBe(0);
    expect($company->numberSequences()->count())->toBe(0);
})->with([
    ['name', '   '], ['name', str_repeat('x', 256)], ['email', 'invalid'], ['website', 'ftp://example.com'], ['website', 'javascript:alert(1)'],
    ['tax_pin', str_repeat('x', 51)], ['registration_number', str_repeat('x', 101)], ['notes', str_repeat('x', 5001)],
    ['currency', 'US'], ['currency', '123'], ['payment_terms_days', -1], ['payment_terms_days', 3651], ['payment_terms_days', 1.5],
    ['lead_time_days', -1], ['lead_time_days', 3651], ['company_id', 999], ['uuid', 'forged'], ['code', 'FORGED'], ['is_active', false],
]);

it('maintains one primary through contact creation editing selection and removal', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $create = app(CreateSupplierContact::class);
    $first = $create->handle($company, $supplier, $owner, ['name' => 'First']);
    $second = $create->handle($company, $supplier, $owner, ['name' => 'Second']);
    expect($first->is_primary)->toBeTrue()->and($second->is_primary)->toBeFalse();
    app(UpdateSupplierContact::class)->handle($company, $supplier, $second, $owner, ['name' => 'Edited', 'email' => ' TEST@EXAMPLE.COM ']);
    app(SetPrimarySupplierContact::class)->handle($company, $supplier, $second, $owner);
    expect($supplier->contacts()->where('is_primary', true)->sole()->id)->toBe($second->id);
    app(RemoveSupplierContact::class)->handle($company, $supplier, $second, $owner);
    expect($first->fresh()->is_primary)->toBeTrue();
    app(RemoveSupplierContact::class)->handle($company, $supplier, $first, $owner);
    expect($supplier->contacts()->count())->toBe(0);
});

it('repairs both address types and promotes the oldest remaining default', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $create = app(CreateSupplierAddress::class);
    $first = $create->handle($company, $supplier, $owner, ['type' => 'billing', 'line_1' => 'First']);
    $second = $create->handle($company, $supplier, $owner, ['type' => 'billing', 'line_1' => 'Second']);
    $shipping = $create->handle($company, $supplier, $owner, ['type' => 'shipping', 'line_1' => 'Depot']);
    app(UpdateSupplierAddress::class)->handle($company, $supplier, $first, $owner, ['type' => 'shipping', 'line_1' => 'Moved']);
    expect($second->fresh()->is_default)->toBeTrue()->and($shipping->fresh()->is_default)->toBeTrue()->and($first->fresh()->is_default)->toBeFalse();
    app(SetDefaultSupplierAddress::class)->handle($company, $supplier, $first, $owner);
    app(RemoveSupplierAddress::class)->handle($company, $supplier, $first, $owner);
    expect($shipping->fresh()->is_default)->toBeTrue();
    app(UpdateSupplierAddress::class)->handle($company, $supplier, $second, $owner, ['type' => 'other', 'line_1' => 'Office']);
    expect($supplier->addresses()->where('type', 'billing')->count())->toBe(0)->and($second->fresh()->is_default)->toBeTrue();
    app(RemoveSupplierAddress::class)->handle($company, $supplier, $second, $owner);
    expect($supplier->addresses()->where('type', 'other')->count())->toBe(0);
});

it('renders all forms escaped details filters and pagination with UUID links', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id, 'name' => '<script>alert(1)</script>'.str_repeat('long', 30), 'notes' => '<script>bad()</script>']);
    $contact = app(CreateSupplierContact::class)->handle($company, $supplier, $owner, ['name' => 'Contact']);
    $address = app(CreateSupplierAddress::class)->handle($company, $supplier, $owner, ['type' => 'billing', 'line_1' => 'Address']);
    $this->actingAs($owner);
    foreach (['index' => [$company], 'create' => [$company], 'edit' => [$company, $supplier], 'contacts.create' => [$company, $supplier], 'contacts.edit' => [$company, $supplier, $contact], 'addresses.create' => [$company, $supplier], 'addresses.edit' => [$company, $supplier, $address]] as $name => $args) {
        $this->get(route('company.suppliers.'.$name, $args))->assertOk();
    }
    $this->get(route('company.suppliers.show', [$company, $supplier]))->assertSee(e($supplier->name), false)->assertDontSee('<script>', false)->assertSee('break-words', false);
    SupplierFactory::new()->count(17)->create(['company_id' => $company->id, 'name' => 'Find supplier']);
    SupplierFactory::new()->create(['name' => 'Hidden foreign']);
    $this->get(route('company.suppliers.index', [$company, 'search' => 'Find', 'status' => 'all']))->assertSee('page=2', false)->assertSee('search=Find', false)->assertDontSee('Hidden foreign');
    $this->from(route('company.suppliers.create', $company))->post(route('company.suppliers.store', $company), ['name' => '', 'email' => 'bad'])->assertSessionHasErrors(['name', 'email'])->assertSessionHasInput('email', 'bad');
});
