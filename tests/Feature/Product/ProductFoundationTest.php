<?php

declare(strict_types=1);

use App\Actions\Product\CreateBrand;
use App\Actions\Product\CreateCategory;
use App\Actions\Product\CreateProduct;
use App\Actions\Product\CreateUnit;
use App\Actions\Product\ReparentCategory;
use App\Actions\Product\UpdateProduct;
use App\Models\Product\Product;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Database\Factories\SupplierFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

function productContext(): array
{
    [$owner, $company] = SupplierFixture::owner();
    $company->refresh();
    $category = app(CreateCategory::class)->handle($company, $owner, ['name' => 'Hardware', 'code' => 'HW']);
    $unit = app(CreateUnit::class)->handle($company, $owner, ['name' => 'Each', 'symbol' => 'EA']);

    return [$owner, $company, ['name' => 'Bolt', 'category_id' => $category->id, 'unit_id' => $unit->id]];
}

it('rejects outsiders and members without the required permission on every existing route', function () {
    [$owner, $company, $values] = productContext();
    [$outsider] = SupplierFixture::owner();
    $member = SupplierFixture::member($company);
    foreach ([$outsider, $member] as $actor) {
        $this->actingAs($actor);
        foreach (['product.index', 'product.create', 'category.create', 'brand.index', 'brand.create', 'unit.index', 'unit.create'] as $route) {
            $this->get(route($route, $company))->assertForbidden();
        }
        foreach (['product.store' => $values, 'category.store' => ['name' => 'X', 'code' => 'X'], 'unit.store' => ['name' => 'X', 'symbol' => 'x'], 'brand.store' => ['name' => 'X', 'code' => 'X']] as $route => $payload) {
            $this->post(route($route, $company), $payload)->assertForbidden();
        }
    }
    expect($company->products()->count())->toBe(0);
});

it('redirects guests and scopes nested products before exposure', function () {
    [$owner, $company] = productContext();
    $this->get(route('product.index', $company))->assertRedirect(route('login'));
    $foreign = ProductFactory::new()->create();
    $this->actingAs($owner)->get(route('product.show', [$company, $foreign]))->assertNotFound();
});

it('creates a Product with default type and currency and retains four decimal digits', function () {
    [$owner, $company, $values] = productContext();
    $this->actingAs($owner)->post(route('product.store', $company), [...$values, 'prices' => [['price_type' => 'retail', 'price' => '12.3456']], 'barcodes' => [['barcode' => '96385074', 'type' => 'EAN']]])->assertRedirect(route('product.index', $company));
    $product = $company->products()->sole();
    expect($product->product_type->value)->toBe('physical')->and($product->track_stock)->toBeFalse()->and($product->sku)->toMatch('/^SKU-\d{4}-000001$/');
    expect($product->prices()->sole()->price)->toBe('12.3456')->and($product->prices()->sole()->currency)->toBe(strtoupper($company->currency));
    expect($product->barcodes()->sole()->company_id)->toBe($company->id);
});

it('rejects submitted identity fields and foreign classifications without consuming a number', function (string $field) {
    [$owner, $company, $values] = productContext();
    $foreign = ProductFactory::new()->create();
    $brand = $foreign->company->brands()->create(['name' => 'Other', 'code' => 'OTHER']);
    $bad = match ($field) {
        'category_id' => $foreign->category_id, 'unit_id' => $foreign->unit_id, 'brand_id' => $brand->id, 'company_id' => $foreign->company_id, default => 'FORGED'
    };
    $this->actingAs($owner)->post(route('product.store', $company), [...$values, $field => $bad])->assertSessionHasErrors($field);
    expect($company->products()->count())->toBe(0)->and($company->numberSequences()->count())->toBe(0);
})->with(['sku', 'uuid', 'company_id', 'category_id', 'unit_id', 'brand_id']);

it('rechecks direct Action authority and validates direct callers', function () {
    [$owner, $company, $values] = productContext();
    $member = SupplierFixture::member($company);
    expect(fn () => app(CreateProduct::class)->handle($company, $member, $values))->toThrow(AuthorizationException::class);
    foreach ([CreateCategory::class => ['name' => 'X', 'code' => 'X'], CreateUnit::class => ['name' => 'X', 'symbol' => 'X'], CreateBrand::class => ['name' => 'X', 'code' => 'X']] as $action => $input) {
        expect(fn () => app($action)->handle($company, $member, $input))->toThrow(AuthorizationException::class);
    }
    expect(fn () => app(CreateProduct::class)->handle($company, $owner, [...$values, 'sku' => 'MANUAL']))->toThrow(ValidationException::class);
    $creator = SupplierFixture::member($company, ['products.create']);
    expect(fn () => app(CreateProduct::class)->handle($company, $creator, [...$values, 'prices' => [['price_type' => 'retail', 'price' => '1']]]))->toThrow(AuthorizationException::class);
    expect(fn () => app(CreateProduct::class)->handle($company, $creator, [...$values, 'barcodes' => [['type' => 'CODE128', 'barcode' => 'A']]]))->toThrow(AuthorizationException::class);
});

it('keeps SKU continuous across years immutable and transactional on failed persistence', function () {
    [$owner, $company, $values] = productContext();
    $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00:00'));
    $first = app(CreateProduct::class)->handle($company, $owner, $values);
    $this->travelTo(CarbonImmutable::parse('2027-01-01 12:00:00'));
    $second = app(CreateProduct::class)->handle($company, $owner, $values);
    expect($first->sku)->toBe('SKU-2026-000001')->and($second->sku)->toBe('SKU-2027-000002');
    expect(fn () => app(UpdateProduct::class)->handle($company, $first, $owner, [...$values, 'sku' => 'OTHER']))->toThrow(ValidationException::class);
    app(UpdateProduct::class)->handle($company, $first, $owner, [...$values, 'name' => 'Updated']);
    expect($first->fresh()->sku)->toBe('SKU-2026-000001')->and($first->fresh()->track_stock)->toBeFalse();
    DB::statement("CREATE TRIGGER reject_product BEFORE INSERT ON products BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
    try {
        expect(fn () => app(CreateProduct::class)->handle($company, $owner, $values))->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER reject_product');
    }
    expect($company->numberSequences()->sole()->next_number)->toBe(3);
});

it('fails a legacy SKU collision without skipping or advancing', function () {
    [$owner, $company, $values] = productContext();
    ProductFactory::new()->create(['company_id' => $company->id, 'sku' => 'SKU-'.now()->year.'-000001']);
    expect(fn () => app(CreateProduct::class)->handle($company, $owner, $values))->toThrow(DomainException::class, 'collides');
    expect($company->numberSequences()->count())->toBe(0);
});

it('enforces classification ancestry and checks descendants during reparenting', function () {
    [$owner, $company, $values] = productContext();
    $root = $company->categories()->findOrFail($values['category_id']);
    $child = app(CreateCategory::class)->handle($company, $owner, ['name' => 'Child', 'code' => 'C', 'parent_id' => $root->id]);
    $leaf = app(CreateCategory::class)->handle($company, $owner, ['name' => 'Leaf', 'code' => 'L', 'parent_id' => $child->id]);
    expect(fn () => app(CreateCategory::class)->handle($company, $owner, ['name' => 'Fourth', 'code' => 'F', 'parent_id' => $leaf->id]))->toThrow(ValidationException::class);
    expect(fn () => app(ReparentCategory::class)->handle($company, $root, $owner, $leaf->id))->toThrow(ValidationException::class);
    expect(fn () => app(ReparentCategory::class)->handle($company, $root, $owner, $root->id))->toThrow(ValidationException::class);
    $otherRoot = app(CreateCategory::class)->handle($company, $owner, ['name' => 'Other', 'code' => 'O']);
    expect(fn () => app(ReparentCategory::class)->handle($company, $root, $owner, $otherRoot->id))->toThrow(ValidationException::class);
    $foreign = ProductFactory::new()->create();
    expect(fn () => app(CreateCategory::class)->handle($company, $owner, ['name' => 'Foreign', 'code' => 'X', 'parent_id' => $foreign->category_id]))->toThrow(ValidationException::class);
    app(ReparentCategory::class)->handle($company, $leaf, $owner, null);
    expect($leaf->fresh()->parent_id)->toBeNull();
});

it('rejects invalid selling prices through direct validation', function (array $prices) {
    [$owner, $company, $values] = productContext();
    expect(fn () => app(CreateProduct::class)->handle($company, $owner, [...$values, 'prices' => $prices]))->toThrow(ValidationException::class);
})->with([
    [[['price_type' => 'cost', 'price' => '1']]],
    [[['price_type' => 'retail', 'price' => '-1']]],
    [[['price_type' => 'retail', 'price' => 1.2]]],
    [[['price_type' => 'retail', 'price' => '1.12345']]],
    [[['price_type' => 'retail', 'price' => '1000000000000000']]],
    [[['price_type' => 'retail', 'price' => '1', 'currency' => '123']]],
    [[['price_type' => 'retail', 'price' => '1', 'currency' => 'kes'], ['price_type' => 'retail', 'price' => '2', 'currency' => 'KES']]],
]);

it('validates EAN and UPC check digits', function (string $type, string $barcode, bool $valid) {
    [$owner, $company, $values] = productContext();
    $create = fn () => app(CreateProduct::class)->handle($company, $owner, [...$values, 'barcodes' => [['type' => $type, 'barcode' => $barcode]]]);
    if ($valid) {
        expect($create()->barcodes()->sole()->barcode)->toBe($barcode);
    } else {
        expect($create)->toThrow(ValidationException::class);
    }
})->with([['EAN', '96385074', true], ['EAN', '4006381333931', true], ['UPC', '036000291452', true], ['EAN', '96385075', false], ['UPC', '036000291453', false], ['EAN', '12', false], ['CODE128', 'ABC-123', true]]);

it('allows the same barcode across Companies but rejects duplicates within one Company', function () {
    [$owner, $company, $values] = productContext();
    $input = [...$values, 'barcodes' => [['type' => 'CODE128', 'barcode' => 'SHARED']]];
    app(CreateProduct::class)->handle($company, $owner, $input);
    expect(fn () => app(CreateProduct::class)->handle($company, $owner, $input))->toThrow(ValidationException::class);
    [$other, $otherCompany, $otherValues] = productContext();
    expect(app(CreateProduct::class)->handle($otherCompany, $other, [...$otherValues, 'barcodes' => $input['barcodes']])->barcodes()->count())->toBe(1);
});

it('uses retail Company currency only and separately protects Supplier identity and costs', function () {
    [$owner, $company, $values] = productContext();
    $product = app(CreateProduct::class)->handle($company, $owner, [...$values, 'prices' => [['price_type' => 'wholesale', 'price' => '999.1234']]]);
    $product->prices()->create(['company_id' => $company->id, 'price_type' => 'cost', 'price' => '777.4321', 'currency' => 'KES']);
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id, 'name' => 'Hidden supplier']);
    $product->suppliers()->attach($supplier, ['company_id' => $company->id, 'cost' => '888.1234']);
    $viewer = SupplierFixture::member($company, ['products.view', 'suppliers.view']);
    $this->actingAs($viewer)->get(route('product.index', $company))->assertSee('No retail price.')->assertDontSee('999.1234')->assertDontSee('777.4321');
    $this->get(route('product.show', [$company, $product]))->assertSee('Hidden supplier')->assertDontSee('888.1234')->assertDontSee('777.4321');
    $this->get(route('company.suppliers.show', [$company, $supplier]))->assertDontSee('888.1234');
    $costViewer = SupplierFixture::member($company, ['products.view', 'suppliers.view', 'products.cost.view']);
    $this->actingAs($costViewer)->get(route('product.show', [$company, $product]))->assertSee('888.1234')->assertSee('777.4321')->assertSee('currency not assigned');
    $product->prices()->create(['company_id' => $company->id, 'price_type' => 'retail', 'price' => '12.3456', 'currency' => strtoupper($company->currency)]);
    $this->get(route('product.index', $company))->assertSee('12.3456')->assertDontSee('No retail price.');
});

it('renders existing authorized forms without lazy Product listing queries', function () {
    [$owner, $company, $values] = productContext();
    $this->actingAs($owner);
    foreach (['product.create', 'category.create', 'unit.create', 'unit.index', 'brand.create'] as $route) {
        $this->get(route($route, $company))->assertOk();
    }
    app(CreateProduct::class)->handle($company, $owner, $values);
    DB::enableQueryLog();
    $this->get(route('product.index', $company))->assertOk();
    $one = count(DB::getQueryLog());
    DB::flushQueryLog();
    ProductFactory::new()->count(8)->create(['company_id' => $company->id]);
    DB::flushQueryLog();
    $this->get(route('product.index', $company))->assertOk();
    $many = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($many)->toBe($one);
});

it('rejects revoked direct authority and foreign update targets', function () {
    [$owner,$company,$values] = productContext();
    $actor = SupplierFixture::member($company, ['products.create']);
    $actor->load('roles.permissions');
    $actor->roles()->detach();
    expect(fn () => app(CreateProduct::class)->handle($company, $actor, $values))->toThrow(AuthorizationException::class);
    $foreign = ProductFactory::new()->create();
    expect(fn () => app(UpdateProduct::class)->handle($company, $foreign, $owner, $values))->toThrow(ModelNotFoundException::class);
});

it('hides unauthorized controls and preserves sparse submitted row indexes', function () {
    [$owner,$company,$values] = productContext();
    $viewer = SupplierFixture::member($company, ['products.view']);
    $this->actingAs($viewer)->get(route('product.index', $company))->assertDontSee('Add Product')->assertDontSee('Add Category');
    $this->actingAs($owner)->withSession(['_old_input' => ['prices' => [2 => ['price_type' => 'retail', 'price' => '1', 'currency' => 'KES'], 8 => ['price_type' => 'dealer', 'price' => '2', 'currency' => 'KES']], 'barcodes' => [7 => ['type' => 'CODE128', 'barcode' => 'B']]]])->get(route('product.create', $company))->assertSee('let priceIndex = 2;', false)->assertSee('let barcodeIndex = 1;', false);
});

it('returns validation errors for malformed classification and nested Product input', function () {
    [$owner, $company, $values] = productContext();
    $this->actingAs($owner);
    $this->post(route('brand.store', $company), ['name' => 'Brand', 'code' => ['invalid']])->assertSessionHasErrors('code');
    $this->post(route('brand.store', $company), ['name' => ['invalid'], 'code' => 'B'])->assertSessionHasErrors('name');
    $this->post(route('unit.store', $company), ['name' => 'Unit', 'symbol' => ['invalid']])->assertSessionHasErrors('symbol');
    $this->post(route('product.store', $company), [...$values, 'prices' => ['invalid']])->assertSessionHasErrors('prices.0');
});

it('correction old input renders malformed rows after a validation redirect', function (string $field, mixed $input) {
    [$owner, $company, $values] = productContext();
    $url = route('product.create', $company);
    $response = $this->actingAs($owner)->from($url)->followingRedirects()->post(route('product.store', $company), [...$values, 'name' => '', 'description' => 'Keep this description', $field => $input]);
    $response->assertOk()->assertSee('role="alert"', false)->assertSee('The name field is required.')->assertSee('Keep this description')->assertDontSee('<script>unsafe()</script>', false);
})->with([
    ['prices', 'not-rows'], ['prices', null], ['barcodes', 'not-rows'], ['barcodes', null],
    ['prices', [2 => 'not-a-row', 9 => ['price_type' => ['bad'], 'price' => ['bad'], 'currency' => ['bad']]]],
    ['barcodes', [2 => null, 9 => ['barcode' => ['bad'], 'type' => ['bad']]]],
    ['barcodes', [9 => ['barcode' => '<script>unsafe()</script>', 'type' => 'invalid']]],
]);

it('correction old input reindexes sparse safe rows and retains their validation messages', function () {
    [$owner, $company, $values] = productContext();
    $url = route('product.create', $company);
    $response = $this->actingAs($owner)->from($url)->followingRedirects()->post(route('product.store', $company), [...$values,
        'prices' => [2 => 'bad', 8 => ['price_type' => 'retail', 'price' => '12.3456', 'currency' => 'KES'], 91 => ['price_type' => 'dealer', 'price' => ['bad'], 'currency' => 'KES']],
        'barcodes' => [7 => ['type' => 'CODE128', 'barcode' => 'KEEP-ME'], 90 => ['type' => ['bad'], 'barcode' => ['bad']]],
    ]);
    $page = $response->assertOk()->assertSee('role="alert"', false)->assertSee('prices.91.price')->assertSee('12.3456')->assertSee('KEEP-ME')->assertSee('let priceIndex = 2;', false)->assertSee('let barcodeIndex = 2;', false);
    foreach (['prices[0][price]', 'prices[1][price]', 'barcodes[0][barcode]', 'barcodes[1][barcode]'] as $name) {
        expect(substr_count($page->getContent(), 'name="'.$name.'"'))->toBe(1);
    }
    $page->assertDontSee('name="prices[91][price]"', false)->assertDontSee('name="barcodes[90][barcode]"', false);
});

it('correction old input limits display rows and rejects non-scalar presentation values', function () {
    [$owner, $company] = productContext();
    $rows = array_fill(0, 105, ['price_type' => 'retail', 'price' => '1', 'currency' => 'KES']);
    $page = $this->actingAs($owner)->withSession(['_old_input' => ['prices' => $rows, 'barcodes' => [new stdClass, ['barcode' => new stdClass, 'type' => ['bad']]]]])->get(route('product.create', $company));
    $page->assertOk()->assertSee('let priceIndex = 100;', false)->assertSee('let barcodeIndex = 1;', false);
    expect(substr_count($page->getContent(), 'name="prices['))->toBe(303);
    $page->assertSee('name="barcodes[0][barcode]"', false);
});

it('correction identity protects model mass assignment and trusted factory setup', function () {
    [$owner, $company, $values] = productContext();
    $product = app(CreateProduct::class)->handle($company, $owner, $values);
    $identity = [$product->company_id, $product->sku];
    $product->fill(['company_id' => 99999, 'sku' => 'FORGED']);
    expect([$product->company_id, $product->sku])->toBe($identity);
    $product->update(['company_id' => 99999, 'sku' => 'FORGED', 'name' => 'Safe update']);
    expect([$product->fresh()->company_id, $product->fresh()->sku])->toBe($identity);
    expect(fn () => Product::query()->create([...$values, 'company_id' => $company->id, 'sku' => 'FORGED']))->toThrow(QueryException::class);
    $ordinary = new Product(['company_id' => 99999, 'sku' => 'FORGED']);
    expect($ordinary->company_id)->toBeNull()->and($ordinary->sku)->toBeNull();
    $factory = ProductFactory::new()->create(['company_id' => $company->id, 'sku' => 'TRUSTED-FACTORY']);
    expect($factory->company_id)->toBe($company->id)->and($factory->sku)->toBe('TRUSTED-FACTORY')->and($factory->isFillable('company_id'))->toBeFalse()->and($factory->isFillable('sku'))->toBeFalse();
});

it('correction identity rejects direct and HTTP update input', function () {
    [$owner, $company, $values] = productContext();
    $product = app(CreateProduct::class)->handle($company, $owner, $values);
    $identity = [$product->company_id, $product->sku];
    foreach (['company_id' => 99999, 'sku' => 'FORGED'] as $field => $value) {
        expect(fn () => app(UpdateProduct::class)->handle($company, $product, $owner, [...$values, $field => $value]))->toThrow(ValidationException::class);
        $this->actingAs($owner)->put(route('product.show', [$company, $product]), [...$values, $field => $value])->assertStatus(405);
        $this->patch(route('product.update', [$company, $product]), [...$values, $field => $value])->assertSessionHasErrors($field);
    }
    expect([$product->fresh()->company_id, $product->fresh()->sku])->toBe($identity);
});

it('correction custom prefix preserves configuration across years and rolls back failed creation', function () {
    [$owner, $company, $values] = productContext();
    $sequence = $company->numberSequences()->create(['type' => 'product', 'prefix' => 'BOLT', 'next_number' => 17]);
    $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00:00'));
    expect(app(CreateProduct::class)->handle($company, $owner, $values)->sku)->toBe('BOLT-2026-000017');
    $this->travelTo(CarbonImmutable::parse('2027-01-01 12:00:00'));
    expect(app(CreateProduct::class)->handle($company, $owner, $values)->sku)->toBe('BOLT-2027-000018');
    DB::statement("CREATE TRIGGER reject_custom_product BEFORE INSERT ON products BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
    try {
        expect(fn () => app(CreateProduct::class)->handle($company, $owner, $values))->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER reject_custom_product');
    }
    expect($sequence->fresh()->prefix)->toBe('BOLT')->and($sequence->fresh()->next_number)->toBe(19);
});

it('correction custom prefix rejects client configuration and invalid stored prefixes', function () {
    [$owner, $company, $values] = productContext();
    $this->actingAs($owner)->post(route('product.store', $company), [...$values, 'prefix' => 'FORGED'])->assertSessionHasErrors('prefix');
    expect(fn () => app(CreateProduct::class)->handle($company, $owner, [...$values, 'prefix' => 'FORGED']))->toThrow(ValidationException::class);
    expect($company->numberSequences()->count())->toBe(0);
    expect(app(CreateProduct::class)->handle($company, $owner, $values)->sku)->toStartWith('SKU-');
    $sequence = $company->numberSequences()->sole();
    foreach (['', '   ', null] as $prefix) {
        $sequence->update(['prefix' => $prefix]);
        expect(fn () => app(CreateProduct::class)->handle($company, $owner, $values))->toThrow(DomainException::class);
        expect($sequence->fresh()->prefix)->toBe($prefix)->and($sequence->fresh()->next_number)->toBe(2);
    }
});
