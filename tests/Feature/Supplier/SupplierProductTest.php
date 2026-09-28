<?php

declare(strict_types=1);

use App\Actions\Supplier\ArchiveSupplier;
use App\Actions\Supplier\CreateSupplierAddress;
use App\Actions\Supplier\CreateSupplierContact;
use App\Actions\Supplier\RestoreSupplier;
use App\Actions\Supplier\UpdateSupplier;
use App\Models\Supplier\Supplier;
use Database\Factories\ProductFactory;
use Database\Factories\SupplierFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshSupplierDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshSupplierDatabase::class);

it('enforces composite tenant foreign keys for inserts and updates', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $foreign = SupplierFactory::new()->create();
    $row = ['product_id' => $product->id, 'supplier_id' => $supplier->id, 'company_id' => $company->id, 'supplier_sku' => 'SUP-SKU', 'cost' => '10.1234'];
    DB::table('product_suppliers')->insert($row);
    expect(fn () => DB::table('product_suppliers')->insert([...$row, 'supplier_id' => $foreign->id]))->toThrow(QueryException::class);
    expect(fn () => DB::table('product_suppliers')->where('product_id', $product->id)->update(['company_id' => $foreign->company_id]))->toThrow(QueryException::class);
    expect(fn () => DB::table('suppliers')->where('id', $supplier->id)->update(['company_id' => $foreign->company_id]))->toThrow(QueryException::class);
    expect($product->suppliers()->sole()->pivot->cost)->toBe('10.1234');
    $product->suppliers()->updateExistingPivot($supplier->id, ['supplier_sku' => 'UPDATED']);
    expect(DB::table('product_suppliers')->value('supplier_sku'))->toBe('UPDATED');
});

it('archives without detaching associations and restores new-association eligibility', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $product->suppliers()->attach($supplier, ['company_id' => $company->id, 'supplier_sku' => 'SKU', 'cost' => '10.1234']);
    $contact = app(CreateSupplierContact::class)->handle($company, $supplier, $owner, ['name' => 'Contact']);
    $address = app(CreateSupplierAddress::class)->handle($company, $supplier, $owner, ['type' => 'billing', 'line_1' => 'Address']);
    $before = DB::table('product_suppliers')->first();
    app(ArchiveSupplier::class)->handle($company, $supplier, $owner);
    app(ArchiveSupplier::class)->handle($company, $supplier, $owner);
    expect(Supplier::query()->eligibleForAssociation($company)->whereKey($supplier)->exists())->toBeFalse();
    expect(DB::table('product_suppliers')->first())->toEqual($before);
    expect($product->suppliers()->count())->toBe(1);
    expect($contact->fresh())->not->toBeNull();
    expect($address->fresh())->not->toBeNull();
    app(UpdateSupplier::class)->handle($company, $supplier, $owner, ['name' => 'Corrected archived profile']);
    $this->actingAs($owner)->get(route('company.suppliers.show', [$company, $supplier]))->assertSee('Archived')->assertSee('10.1234')->assertSee('currency not assigned');
    app(RestoreSupplier::class)->handle($company, $supplier, $owner);
    expect(Supplier::query()->eligibleForAssociation($company)->whereKey($supplier)->exists())->toBeTrue();
});

it('protects Product Supplier exposure without hiding unrelated Product information', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id, 'name' => 'Secret supplier']);
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $product->suppliers()->attach($supplier, ['company_id' => $company->id, 'cost' => '10.1234']);
    [$outsider] = SupplierFixture::owner();
    $this->actingAs($outsider)->get(route('product.show', [$company, $product]))->assertForbidden();
    $member = SupplierFixture::member($company, ['products.view']);
    $this->actingAs($member)->get(route('product.show', [$company, $product]))->assertSee($product->name)->assertDontSee('Secret supplier')->assertDontSee('10.1234');
    $viewer = SupplierFixture::member($company, ['suppliers.view', 'products.view', 'products.cost.view']);
    $this->actingAs($viewer)->get(route('product.show', [$company, $product]))->assertSee('Secret supplier')->assertSee('10.1234')->assertSee('currency not assigned');
    $foreignProduct = ProductFactory::new()->create();
    $this->get(route('product.show', [$company, $foreignProduct]))->assertNotFound();
});

it('does not expose corrupt foreign Supplier links even when constraints were bypassed', function () {
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $foreign = SupplierFactory::new()->create(['name' => 'Foreign secret']);
    DB::getSchemaBuilder()->withoutForeignKeyConstraints(fn () => DB::table('product_suppliers')->insert(['product_id' => $product->id, 'supplier_id' => $foreign->id, 'company_id' => $company->id]));
    try {
        $this->actingAs($owner)->get(route('product.show', [$company, $product]))->assertDontSee('Foreign secret');
    } finally {
        DB::table('product_suppliers')->where('product_id', $product->id)->delete();
    }
});

it('correction Supplier links require Product view while cost stays separately protected', function () {
    [$owner, $company] = SupplierFixture::owner();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $product = ProductFactory::new()->create(['company_id' => $company->id, 'name' => 'Context product']);
    $product->suppliers()->attach($supplier, ['company_id' => $company->id, 'cost' => '987.1234']);
    $url = route('company.suppliers.show', [$company, $supplier]);
    $productUrl = route('product.show', [$company, $product]);
    foreach ([false, true] as $cost) {
        $viewer = SupplierFixture::member($company, ['suppliers.view', ...($cost ? ['products.cost.view'] : [])]);
        $page = $this->actingAs($viewer)->get($url)->assertOk()->assertSee('Context product')->assertDontSee($productUrl, false);
        if ($cost) {
            $page->assertSee('987.1234');
        } else {
            $page->assertDontSee('987.1234');
        }
    }
    $viewer = SupplierFixture::member($company, ['suppliers.view', 'products.view']);
    $this->actingAs($viewer)->get($url)->assertOk()->assertSee('href="'.$productUrl.'"', false)->assertDontSee('987.1234');
    [$outsider] = SupplierFixture::owner();
    $this->actingAs($outsider)->get($url)->assertForbidden();
});
