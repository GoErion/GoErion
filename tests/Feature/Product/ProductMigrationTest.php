<?php

declare(strict_types=1);

use Database\Factories\ProductFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\RefreshProductDatabase;

uses(RefreshProductDatabase::class);
function productTenantMigration(): object
{
    return require glob(database_path('migrations/*_strengthen_product_phase5a_tenant_integrity.php'))[0];
}

it('enforces Company classification and child composite constraints for direct writes', function () {
    $product = ProductFactory::new()->create();
    $foreign = ProductFactory::new()->create();
    $brand = $foreign->company->brands()->create(['name' => 'Foreign', 'code' => 'F']);
    foreach (['category_id' => $foreign->category_id, 'unit_id' => $foreign->unit_id, 'brand_id' => $brand->id] as $key => $value) {
        expect(fn () => DB::table('products')->where('id', $product->id)->update([$key => $value]))->toThrow(QueryException::class);
    }
    expect(fn () => DB::table('categories')->where('id', $product->category_id)->update(['parent_id' => $foreign->category_id]))->toThrow(QueryException::class);
    expect(fn () => $product->prices()->create(['company_id' => $foreign->company_id, 'price_type' => 'retail', 'price' => '1.2345', 'currency' => 'KES']))->toThrow(QueryException::class);
    expect(fn () => $product->barcodes()->create(['company_id' => $foreign->company_id, 'barcode' => 'X', 'type' => 'CODE128']))->toThrow(QueryException::class);
    $ownBrand = $product->company->brands()->create(['name' => 'Own', 'code' => 'O']);
    $product->update(['brand_id' => $ownBrand->id]);
    expect(fn () => $ownBrand->delete())->toThrow(QueryException::class);
});

it('preserves legacy data and cost prices across supported rollback and reapply', function () {
    $product = ProductFactory::new()->create();
    $price = $product->prices()->create(['company_id' => $product->company_id, 'price_type' => 'cost', 'price' => '12.3456', 'currency' => 'KES']);
    $barcode = $product->barcodes()->create(['company_id' => $product->company_id, 'barcode' => 'KEEP', 'type' => 'CODE128']);
    $before = DB::table('products')->get();
    $m = productTenantMigration();
    $m->down();
    expect(Schema::hasColumn('product_barcodes', 'company_id'))->toBeFalse()->and(Schema::hasColumn('brands', 'is_active'))->toBeFalse();
    $m->up();
    expect(DB::table('products')->get())->toEqual($before);
    expect($price->fresh()->price)->toBe('12.3456')->and($price->fresh()->price_type->value)->toBe('cost')->and($barcode->fresh()->company_id)->toBe($product->company_id);
    expect((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
});

it('refuses ambiguous rollback before losing archive state or Company barcodes', function (string $scenario) {
    $product = ProductFactory::new()->create();
    if ($scenario === 'archive') {
        $product->category->update(['is_active' => false]);
    } else {
        foreach ([$product, ProductFactory::new()->create()] as $p) {
            $p->barcodes()->create(['company_id' => $p->company_id, 'barcode' => 'SAME', 'type' => 'CODE128']);
        }
    }
    expect(fn () => productTenantMigration()->down())->toThrow(RuntimeException::class, 'Rollback');
    expect(Schema::hasColumn('product_barcodes', 'company_id'))->toBeTrue()->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
})->with(['archive', 'barcode']);

it('refuses invalid legacy classifications hierarchy and prices before DDL', function (string $scenario) {
    $product = ProductFactory::new()->create();
    $foreign = ProductFactory::new()->create();
    $m = productTenantMigration();
    $m->down();
    if ($scenario === 'classification') {
        DB::table('products')->where('id', $product->id)->update(['unit_id' => $foreign->unit_id]);
    } elseif ($scenario === 'parent') {
        DB::table('categories')->where('id', $product->category_id)->update(['parent_id' => $foreign->category_id]);
    } elseif ($scenario === 'cycle') {
        DB::table('categories')->where('id', $product->category_id)->update(['parent_id' => $product->category_id]);
    } elseif ($scenario === 'depth') {
        $parent = $product->category_id;
        foreach (range(1, 3) as $i) {
            $parent = $product->company->categories()->create(['name' => 'Depth', 'code' => 'D'.$i, 'parent_id' => $parent])->id;
        }
    } else {
        $product->prices()->create(['price_type' => 'cost', 'price' => $scenario === 'decimal' ? '-1' : '1', 'currency' => $scenario === 'currency' ? 'kes' : 'KES']);
    }
    expect(fn () => $m->up())->toThrow(RuntimeException::class);
    expect(Schema::hasColumn('categories', 'is_active'))->toBeFalse()->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
})->with(['classification', 'parent', 'cycle', 'depth', 'currency', 'decimal']);

it('restores FK state and the entire SQLite migration after a late failure', function () {
    $m = productTenantMigration();
    $m->down();
    DB::statement('PRAGMA foreign_keys = OFF');
    DB::table('supplier_contacts')->insert(['uuid' => (string) Str::uuid(), 'supplier_id' => 99999, 'name' => 'Orphan']);
    DB::statement('PRAGMA foreign_keys = ON');
    expect(fn () => $m->up())->toThrow(RuntimeException::class, 'foreign_key_check');
    expect(Schema::hasColumn('categories', 'is_active'))->toBeFalse()->and(Schema::hasColumn('product_prices', 'company_id'))->toBeFalse()->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
});

it('retains the original disabled SQLite foreign-key state after a failed preflight', function () {
    $m = productTenantMigration();
    $m->down();
    $product = ProductFactory::new()->create();
    DB::table('categories')->where('id', $product->category_id)->update(['parent_id' => $product->category_id]);
    DB::statement('PRAGMA foreign_keys = OFF');
    try {
        expect(fn () => $m->up())->toThrow(RuntimeException::class);
        expect((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(0);
    } finally {
        DB::statement('PRAGMA foreign_keys = ON');
    }
});

it('certifies actual MySQL composite keys and decimal boundaries', function () {
    if (config('database.default') === 'sqlite') {
        $this->markTestSkipped('MySQL-only Product composite DDL and decimal(19,4) certification deferred.');
    }
    $keys = DB::table('information_schema.KEY_COLUMN_USAGE')->whereRaw('TABLE_SCHEMA = DATABASE()')->whereIn('CONSTRAINT_NAME', ['products_category_company_fk', 'products_unit_company_fk', 'products_brand_company_fk', 'categories_parent_company_fk', 'product_prices_product_company_fk', 'product_barcodes_product_company_fk'])->count();
    expect($keys)->toBe(12);
    $p = ProductFactory::new()->create();
    $price = $p->prices()->create(['company_id' => $p->company_id, 'price_type' => 'retail', 'price' => '999999999999999.9999', 'currency' => 'KES']);
    expect($price->fresh()->price)->toBe('999999999999999.9999');
});

it('rejects duplicate legacy Company barcodes before adding any columns', function () {
    $product = ProductFactory::new()->create();
    $m = productTenantMigration();
    $m->down();
    Schema::table('product_barcodes', fn (Blueprint $table) => $table->dropUnique('product_barcodes_barcode_unique'));
    foreach (['one', 'two'] as $suffix) {
        DB::table('product_barcodes')->insert(['uuid' => (string) Str::uuid(), 'product_id' => $product->id, 'barcode' => 'DUPLICATE', 'type' => 'CODE128']);
    }
    expect(fn () => $m->up())->toThrow(RuntimeException::class, 'Duplicate Company barcode');
    expect(Schema::hasColumn('product_barcodes', 'company_id'))->toBeFalse()->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
});
