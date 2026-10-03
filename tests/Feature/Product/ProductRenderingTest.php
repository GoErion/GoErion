<?php

declare(strict_types=1);

use Database\Factories\ProductFactory;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

it('renders malformed scalar old input safely in every refactored form', function (string $entity, string $mode) {
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $record = match ($entity) {
        'product' => $product, 'category' => $product->category, 'unit' => $product->unit,
        'brand' => $company->brands()->create(['name' => 'Brand', 'code' => 'B']),
    };
    $url = route($entity.'.'.$mode, $mode === 'edit' ? [$company, $record] : $company);
    $this->actingAs($owner)->withSession(['_old_input' => ['name' => ['bad'], 'description' => new stdClass, 'code' => ['bad'], 'symbol' => ['bad'], 'parent_id' => ['bad'], 'category_id' => ['bad'], 'product_type' => ['bad']]])->get($url)->assertOk()->assertSee('for="name"', false)->assertDontSee('Array to string');
})->with([['product', 'create'], ['product', 'edit'], ['category', 'create'], ['category', 'edit'], ['brand', 'create'], ['brand', 'edit'], ['unit', 'create'], ['unit', 'edit']]);

it('preserves feedback after malformed profile submission and wraps long identities', function () {
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id, 'sku' => str_repeat('S', 150)]);
    $url = route('product.edit', [$company, $product]);
    $this->actingAs($owner)->from($url)->followingRedirects()->patch(route('product.update', [$company, $product]), ['name' => ['bad'], 'description' => '<script>unsafe()</script>', 'category_id' => $product->category_id, 'unit_id' => $product->unit_id])
        ->assertOk()->assertSee('role="alert"', false)->assertDontSee('<script>unsafe()</script>', false)->assertSee($product->sku)->assertSee('min-w-0')->assertSee('break-words')->assertSee('name="_token"', false)->assertSee('value="PATCH"', false)->assertDontSee('name="sku"', false)->assertDontSee('name="track_stock"', false);
});

it('renders only authorized lifecycle controls and keeps catalog templates free of database calls', function () {
    [$owner, $company] = SupplierFixture::owner();
    $p = ProductFactory::new()->create(['company_id' => $company->id]);
    $viewer = SupplierFixture::member($company, ['products.view']);
    $this->actingAs($viewer)->get(route('product.show', [$company, $p]))->assertDontSee(route('product.edit', [$company, $p]), false)->assertDontSee(route('product.archive', [$company, $p]), false);
    $this->actingAs($owner)->get(route('product.show', [$company, $p]))->assertSee(route('product.edit', [$company, $p]), false)->assertSee(route('product.archive', [$company, $p]), false);
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views/product'))) as $file) {
        if ($file->isFile()) {
            expect(file_get_contents($file->getPathname()))->not->toMatch('/::(?:query|where|find|all)\s*\(|->(?:get|paginate)\s*\(/');
        }
    }
});

it('correction associates invalid controls and renders one Product summary after redirects', function (string $entity, string $mode, array $invalidFields) {
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $record = match ($entity) {
        'product' => $product,
        'category' => $product->category,
        'unit' => $product->unit,
        'brand' => $company->brands()->create(['name' => 'Brand', 'code' => 'B']),
    };
    $payload = match ($entity) {
        'product' => $product->only(['name', 'description', 'category_id', 'brand_id', 'unit_id']),
        'category' => $record->only(['name', 'code', 'parent_id']),
        'brand' => $record->only(['name', 'code']),
        'unit' => $record->only(['name', 'symbol']),
    };
    if ($mode === 'create') {
        $payload['name'] = 'New correction '.$entity;
    }
    foreach ($invalidFields as $field) {
        $payload[$field] = ['malformed'];
    }
    $edit = $mode === 'edit';
    $formUrl = route($entity.'.'.$mode, $edit ? [$company, $record] : $company);
    $submitUrl = route($entity.'.'.($edit ? 'update' : 'store'), $edit ? [$company, $record] : $company);
    $method = ! $edit ? 'post' : ($entity === 'brand' ? 'put' : 'patch');
    $page = $this->actingAs($owner)->from($formUrl)->followingRedirects()->{$method}($submitUrl, $payload)->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($page->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    foreach ($invalidFields as $field) {
        $controls = $xpath->query('//*[self::input or self::select or self::textarea][@name="'.$field.'"]');
        expect($controls->length)->toBe(1);
        $control = $controls->item(0);
        $errorId = $entity.'-'.$field.'-error';
        expect($control->getAttribute('aria-invalid'))->toBe('true');
        expect(explode(' ', $control->getAttribute('aria-describedby')))->toContain($errorId);
        $errors = $xpath->query('//*[@id="'.$errorId.'"]');
        expect($errors->length)->toBe(1)->and(trim($errors->item(0)->textContent))->not->toBe('');
        expect($xpath->query('//label[@for="'.$control->getAttribute('id').'"]')->length)->toBe(1);
    }
    $validField = $entity === 'product' ? 'unit_id' : 'name';
    $valid = $xpath->query('//*[self::input or self::select or self::textarea][@name="'.$validField.'"]')->item(0);
    expect($valid->hasAttribute('aria-invalid'))->toBeFalse()->and($valid->getAttribute('aria-describedby'))->not->toContain($entity.'-'.$validField.'-error');
    foreach ($xpath->query('//*[@aria-describedby]') as $control) {
        foreach (preg_split('/\s+/', trim($control->getAttribute('aria-describedby'))) as $id) {
            expect($xpath->query('//*[@id="'.$id.'"]')->length)->toBe(1);
        }
    }
    $ids = [];
    foreach ($xpath->query('//*[@id]') as $element) {
        $ids[] = $element->getAttribute('id');
    }
    expect(count($ids))->toBe(count(array_unique($ids)));
    if ($entity === 'product') {
        expect($xpath->query('//form//div[@role="alert"]')->length)->toBe(1);
    }
})->with([
    ['product', 'create', ['name', 'description', 'category_id', 'brand_id']],
    ['product', 'edit', ['name', 'description', 'category_id', 'brand_id']],
    ['category', 'create', ['code', 'parent_id']],
    ['category', 'edit', ['code', 'parent_id']],
    ['brand', 'create', ['code']],
    ['brand', 'edit', ['code']],
    ['unit', 'create', ['symbol']],
    ['unit', 'edit', ['symbol']],
]);
