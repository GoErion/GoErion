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
use App\Actions\Supplier\UpdateSupplierCommercialTerms;
use App\Actions\Supplier\UpdateSupplierContact;
use Database\Factories\SupplierAddressFactory;
use Database\Factories\SupplierContactFactory;
use Database\Factories\SupplierFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshSupplierDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshSupplierDatabase::class);

function supplierMutationCall(string $operation, mixed $company, mixed $supplier, mixed $actor, mixed $contact, mixed $address): mixed
{
    return match ($operation) {
        'create' => app(CreateSupplier::class)->handle($company, $actor, ['name' => 'Attempt']),
        'update' => app(UpdateSupplier::class)->handle($company, $supplier, $actor, ['name' => 'Attempt']),
        'terms' => app(UpdateSupplierCommercialTerms::class)->handle($company, $supplier, $actor, ['currency' => 'USD', 'payment_terms_days' => 30]),
        'archive' => app(ArchiveSupplier::class)->handle($company, $supplier, $actor),
        'restore' => app(RestoreSupplier::class)->handle($company, $supplier, $actor),
        'contact.create' => app(CreateSupplierContact::class)->handle($company, $supplier, $actor, ['name' => 'Attempt']),
        'contact.update' => app(UpdateSupplierContact::class)->handle($company, $supplier, $contact, $actor, ['name' => 'Attempt']),
        'contact.remove' => app(RemoveSupplierContact::class)->handle($company, $supplier, $contact, $actor),
        'contact.primary' => app(SetPrimarySupplierContact::class)->handle($company, $supplier, $contact, $actor),
        'address.create' => app(CreateSupplierAddress::class)->handle($company, $supplier, $actor, ['type' => 'billing', 'line_1' => 'Attempt']),
        'address.update' => app(UpdateSupplierAddress::class)->handle($company, $supplier, $address, $actor, ['type' => 'other', 'line_1' => 'Attempt']),
        'address.remove' => app(RemoveSupplierAddress::class)->handle($company, $supplier, $address, $actor),
        'address.default' => app(SetDefaultSupplierAddress::class)->handle($company, $supplier, $address, $actor),
    };
}

it('rechecks direct Action authority and preserves all rows after denial', function (string $operation) {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $contact = SupplierContactFactory::new()->create(['supplier_id' => $supplier->id, 'is_primary' => true]);
    $address = SupplierAddressFactory::new()->create(['supplier_id' => $supplier->id, 'is_default' => true]);
    $actor = SupplierFixture::member($company);
    $snapshot = fn () => collect(['suppliers', 'supplier_contacts', 'supplier_addresses', 'number_sequences'])->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    $before = $snapshot();
    expect(fn () => supplierMutationCall($operation, $company, $supplier, $actor, $contact, $address))->toThrow(AuthorizationException::class);
    $owner->load('roles.permissions', 'companies');
    $company->ownerRole->permissions()->detach();
    expect(fn () => supplierMutationCall($operation, $company, $supplier, $owner, $contact, $address))->toThrow(AuthorizationException::class);
    $company->users()->detach($owner);
    expect(fn () => supplierMutationCall($operation, $company, $supplier, $owner, $contact, $address))->toThrow(AuthorizationException::class);
    expect($snapshot())->toBe($before);
})->with(['create', 'update', 'terms', 'archive', 'restore', 'contact.create', 'contact.update', 'contact.remove', 'contact.primary', 'address.create', 'address.update', 'address.remove', 'address.default']);

it('rejects foreign numeric parents in all direct nested mutations', function (string $operation) {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create();
    $contact = SupplierContactFactory::new()->create(['supplier_id' => $supplier->id]);
    $address = SupplierAddressFactory::new()->create(['supplier_id' => $supplier->id]);
    expect(fn () => supplierMutationCall($operation, $company, $supplier, $owner, $contact, $address))->toThrow(ModelNotFoundException::class);
    expect($supplier->fresh()->name)->toBe($supplier->name);
})->with(['update', 'terms', 'archive', 'restore', 'contact.create', 'contact.update', 'contact.remove', 'contact.primary', 'address.create', 'address.update', 'address.remove', 'address.default']);

it('rejects children belonging to a different Supplier in the same Company', function (string $operation) {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $other = SupplierFactory::new()->create(['company_id' => $company->id]);
    $contact = SupplierContactFactory::new()->create(['supplier_id' => $other->id]);
    $address = SupplierAddressFactory::new()->create(['supplier_id' => $other->id]);
    expect(fn () => supplierMutationCall($operation, $company, $supplier, $owner, $contact, $address))->toThrow(ModelNotFoundException::class);
})->with(['contact.update', 'contact.remove', 'contact.primary', 'address.update', 'address.remove', 'address.default']);

it('requires terms authority for explicit zero and default terms during direct creation', function () {
    [$owner, $company] = SupplierFixture::owner();
    $actor = SupplierFixture::member($company, ['suppliers.create']);
    foreach ([['payment_terms_days' => 0], ['currency' => 'KES'], ['lead_time_days' => null]] as $terms) {
        expect(fn () => app(CreateSupplier::class)->handle($company, $actor, ['name' => 'Attempt', ...$terms]))->toThrow(AuthorizationException::class);
    }
    expect($company->numberSequences()->count())->toBe(0);
    expect(app(CreateSupplier::class)->handle($company, $actor, ['name' => 'Default'])->currency)->toBe('KES');
});
