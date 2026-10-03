<?php

declare(strict_types=1);

use Database\Factories\ProductFactory;
use Database\Factories\SupplierFactory;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

it('searches literal SKU name and Company-aligned barcodes without escaping the tenant boundary', function () {
    [$owner, $company] = SupplierFixture::owner();
    $p = ProductFactory::new()->create(['company_id' => $company->id, 'name' => 'Bolt 20%_special', 'sku' => 'SKU!_ONE']);
    $p->barcodes()->create(['company_id' => $company->id, 'type' => 'CODE128', 'barcode' => 'BAR%_CODE']);
    $foreign = ProductFactory::new()->create(['name' => $p->name]);
    $other = ProductFactory::new()->create(['company_id' => $company->id, 'name' => 'Bolt 200special', 'sku' => 'PLAIN']);
    foreach (['  20%_  ', 'SKU!_', 'BAR%_'] as $q) {
        $this->actingAs($owner)->get(route('product.index', [$company, 'q' => $q]))->assertOk()->assertViewHas('products', fn ($rows) => $rows->pluck('id')->all() === [$p->id]);
    }
});

it('filters classifications status and Suppliers and rejects forbidden filter submissions', function () {
    [$owner, $company] = SupplierFixture::owner();
    $p = ProductFactory::new()->create(['company_id' => $company->id]);
    $other = ProductFactory::new()->create(['company_id' => $company->id, 'is_active' => false]);
    $brand = $company->brands()->create(['name' => 'Brand', 'code' => 'B']);
    $p->update(['brand_id' => $brand->id]);
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $p->suppliers()->attach($supplier, ['company_id' => $company->id]);
    foreach (['category' => $p->category->uuid, 'unit' => $p->unit->uuid, 'brand' => $brand->uuid, 'supplier' => $supplier->uuid] as $key => $uuid) {
        $this->actingAs($owner)->get(route('product.index', [$company, $key => $uuid, 'status' => 'all']))->assertViewHas('products', fn ($rows) => $rows->pluck('id')->all() === [$p->id]);
    }
    $this->get(route('product.index', [$company, 'status' => 'archived']))->assertViewHas('products', fn ($rows) => $rows->pluck('id')->all() === [$other->id]);
    $this->get(route('product.index', [$company, 'status' => 'all']))->assertViewHas('products', fn ($rows) => $rows->total() === 2);
    $this->get(route('product.index', $company))->assertViewHas('products', fn ($rows) => $rows->pluck('id')->all() === [$p->id]);
    $viewer = SupplierFixture::member($company, ['products.view']);
    $this->actingAs($viewer)->get(route('product.index', [$company, 'supplier' => $supplier->uuid]))->assertForbidden();
    $this->get(route('product.index', $company))->assertDontSee('name="supplier"', false);
    $foreign = ProductFactory::new()->create();
    $this->get(route('product.index', [$company, 'category' => $foreign->category->uuid]))->assertSessionHasErrors('category');
});

it('validates sort search pagination and filter input', function (array $query, string $error) {
    [$owner, $company] = SupplierFixture::owner();
    $this->actingAs($owner)->get(route('product.index', [$company, ...$query]))->assertSessionHasErrors($error);
})->with([[['sort' => 'cost desc'], 'sort'], [['direction' => 'sideways'], 'direction'], [['q' => ['bad']], 'q'], [['q' => str_repeat('x', 101)], 'q'], [['status' => 'deleted'], 'status'], [['page' => 0], 'page'], [['page' => 1000001], 'page'], [['unit' => 'bad'], 'unit']]);

it('preserves filters with deterministic ties and distinguishes empty states', function () {
    [$owner, $company] = SupplierFixture::owner();
    $this->actingAs($owner)->get(route('product.index', $company))->assertSee('No products yet.');
    $products = ProductFactory::new()->count(21)->create(['company_id' => $company->id, 'name' => 'Identical']);
    $this->get(route('product.index', [$company, 'q' => 'Identical', 'sort' => 'name', 'direction' => 'asc', 'status' => 'all']))
        ->assertViewHas('products', fn ($rows) => $rows->pluck('id')->all() === $products->take(20)->pluck('id')->all())
        ->assertSee('q=Identical', false)->assertSee('sort=name', false)->assertSee('status=all', false);
    $this->get(route('product.index', [$company, 'q' => 'missing']))->assertSee('No products match the current filters.');
    $this->get(route('product.index', [$company, 'sort' => 'name', 'direction' => 'desc', 'page' => 2]))->assertViewHas('products', fn ($rows) => $rows->pluck('id')->all() === [$products->first()->id]);
});

it('keeps full-page and one-row query counts equal including authorization', function () {
    [$owner, $company] = SupplierFixture::owner();
    $p = ProductFactory::new()->create(['company_id' => $company->id]);
    $this->actingAs($owner);
    $measure = function () use ($company): int {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('product.index', $company))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };
    $one = $measure();
    ProductFactory::new()->count(19)->create(['company_id' => $company->id]);
    expect($measure())->toBe($one);
});
