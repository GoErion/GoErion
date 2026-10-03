<?php

declare(strict_types=1);

use App\Actions\Product\ArchiveProduct;
use App\Actions\Product\RestoreProduct;
use App\Actions\Product\UpdateProduct;
use Database\Factories\ProductFactory;
use Database\Factories\SupplierFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

function lifecycleContext(): array
{
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id]);

    return [$owner, $company, $product, $product->only(['name', 'description', 'category_id', 'brand_id', 'unit_id'])];
}

it('requires view and the operation permission for HTTP and direct Product mutations', function (string $operation, array $permissions, bool $allowed) {
    [$owner, $company, $product, $values] = lifecycleContext();
    $actor = SupplierFixture::member($company, $permissions);
    $action = match ($operation) {
        'update' => UpdateProduct::class,
        'archive' => ArchiveProduct::class,
        'restore' => RestoreProduct::class,
    };
    if ($operation === 'update') {
        $edit = $this->actingAs($actor)->get(route('product.edit', [$company, $product]));
        if ($allowed) {
            $edit->assertOk();
        } else {
            $edit->assertForbidden();
        }
    }
    $response = $this->actingAs($actor)->patch(route('product.'.$operation, [$company, $product]), $operation === 'update' ? $values : []);
    $direct = fn () => $operation === 'update' ? app($action)->handle($company, $product, $actor, $values) : app($action)->handle($company, $product, $actor);
    if ($allowed) {
        $response->assertRedirect(route('product.show', [$company, $product]));
        expect($direct()->id)->toBe($product->id);
    } else {
        $response->assertForbidden();
        expect($direct)->toThrow(AuthorizationException::class);
    }
})->with([
    ['update', [], false], ['update', ['products.view'], false], ['update', ['products.update'], false], ['update', ['products.view', 'products.update'], true],
    ['archive', ['products.view'], false], ['archive', ['products.archive'], false], ['archive', ['products.view', 'products.archive'], true],
    ['restore', ['products.view'], false], ['restore', ['products.archive'], false], ['restore', ['products.view', 'products.archive'], true],
]);

it('rejects guest outsider foreign UUID and stale direct authority', function () {
    [$owner, $company, $product, $values] = lifecycleContext();
    $this->get(route('product.edit', [$company, $product]))->assertRedirect(route('login'));
    [$outsider] = SupplierFixture::owner();
    $this->actingAs($outsider)->get(route('product.edit', [$company, $product]))->assertForbidden();
    $foreign = ProductFactory::new()->create();
    foreach (['update', 'archive', 'restore'] as $verb) {
        $this->actingAs($outsider)->patch(route('product.'.$verb, [$company, $product]), $values)->assertForbidden();
        $this->actingAs($owner)->patch(route('product.'.$verb, [$company, $foreign]), $values)->assertNotFound();
    }
    $this->get(route('product.edit', [$company, $foreign]))->assertNotFound();
    $actor = SupplierFixture::member($company, ['products.view', 'products.update', 'products.archive']);
    $actor->load('roles.permissions');
    $actor->roles()->detach();
    expect(fn () => app(UpdateProduct::class)->handle($company, $product, $actor, $values))->toThrow(AuthorizationException::class);
    foreach ([ArchiveProduct::class, RestoreProduct::class] as $action) {
        expect(fn () => app($action)->handle($company, $product, $actor))->toThrow(AuthorizationException::class);
        expect(fn () => app($action)->handle($company, $foreign, $owner))->toThrow(ModelNotFoundException::class);
    }
});

it('preserves protected fields and children during archived profile correction and idempotent lifecycle', function () {
    [$owner, $company, $product, $values] = lifecycleContext();
    $product->update(['product_type' => 'service', 'track_stock' => true, 'track_serial' => true, 'track_batch' => true]);
    $price = $product->prices()->create(['company_id' => $company->id, 'price_type' => 'retail', 'price' => '9.1234', 'currency' => 'KES']);
    $barcode = $product->barcodes()->create(['company_id' => $company->id, 'barcode' => 'SAFE', 'type' => 'CODE128']);
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $product->suppliers()->attach($supplier, ['company_id' => $company->id, 'cost' => '7.1234']);
    $before = $product->fresh()->only(['company_id', 'sku', 'product_type', 'track_stock', 'track_serial', 'track_batch']);
    $pivot = DB::table('product_suppliers')->first();
    $this->actingAs($owner);
    foreach (range(1, 2) as $i) {
        $this->patch(route('product.archive', [$company, $product]))->assertRedirect();
    }
    $this->patch(route('product.update', [$company, $product]), [...$values, 'name' => 'Corrected'])->assertRedirect();
    expect($product->fresh()->is_active)->toBeFalse()->and($product->fresh()->name)->toBe('Corrected')->and($product->fresh()->only(array_keys($before)))->toBe($before);
    expect($price->fresh()->price)->toBe('9.1234')->and($barcode->fresh()->barcode)->toBe('SAFE')->and(DB::table('product_suppliers')->first())->toEqual($pivot);
    $this->get(route('product.show', [$company, $product]))->assertSee('Archived Product');
    foreach (range(1, 2) as $i) {
        $this->patch(route('product.restore', [$company, $product]))->assertRedirect();
    }
    expect($product->fresh()->is_active)->toBeTrue();
});

it('rejects every protected profile field through HTTP and direct Actions', function (string $field) {
    [$owner, $company, $product, $values] = lifecycleContext();
    $payload = [...$values, $field => 'forged'];
    $this->actingAs($owner)->patch(route('product.update', [$company, $product]), $payload)->assertSessionHasErrors($field);
    expect(fn () => app(UpdateProduct::class)->handle($company, $product, $owner, $payload))->toThrow(ValidationException::class);
})->with(['sku', 'company_id', 'uuid', 'prefix', 'type', 'product_type', 'track_stock', 'track_serial', 'track_batch', 'is_active', 'prices', 'barcodes', 'suppliers', 'supplier_id', 'supplier_ids']);

it('retains current archived classifications but refuses a different archived or foreign selection', function (string $relation, string $field) {
    [$owner, $company, $product, $values] = lifecycleContext();
    $attributes = $relation === 'units' ? ['name' => 'Other', 'symbol' => 'OTH'] : ['name' => 'Other', 'code' => 'OTH'];
    if ($relation === 'brands') {
        $current = $company->brands()->create(['name' => 'Current', 'code' => 'CUR']);
        $product->update(['brand_id' => $current->id]);
        $values['brand_id'] = $current->id;
    } else {
        $current = $company->{$relation}()->findOrFail($product->{$field});
    }
    $current->update(['is_active' => false]);
    $other = $company->{$relation}()->create([...$attributes, 'is_active' => false]);
    $this->actingAs($owner)->patch(route('product.update', [$company, $product]), [...$values, 'name' => 'Historical'])->assertRedirect();
    $this->get(route('product.edit', [$company, $product]))->assertSee('(Archived — current)');
    $this->patch(route('product.archive', [$company, $product]))->assertRedirect();
    $this->patch(route('product.restore', [$company, $product]))->assertRedirect();
    $this->patch(route('product.update', [$company, $product]), [...$values, $field => $other->id])->assertSessionHasErrors($field);
    expect(fn () => app(UpdateProduct::class)->handle($company, $product, $owner, [...$values, $field => $other->id]))->toThrow(ValidationException::class);
    $foreignCompany = ProductFactory::new()->create()->company;
    $foreign = $foreignCompany->{$relation}()->create($attributes);
    $this->patch(route('product.update', [$company, $product]), [...$values, $field => $foreign->id])->assertSessionHasErrors($field);
})->with([['categories', 'category_id'], ['brands', 'brand_id'], ['units', 'unit_id']]);
