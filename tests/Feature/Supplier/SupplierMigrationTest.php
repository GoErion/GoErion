<?php

declare(strict_types=1);

use Database\Factories\CompanyFactory;
use Database\Factories\ProductFactory;
use Database\Factories\SupplierFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\RefreshSupplierDatabase;

uses(RefreshSupplierDatabase::class);

function supplierMigration(string $suffix): object
{
    return require glob(database_path('migrations/*_'.$suffix.'.php'))[0];
}

it('preserves every catalog value through tenant rollback and reapply with both cascades', function () {
    $company = CompanyFactory::new()->create();
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $row = ['product_id' => $product->id, 'supplier_id' => $supplier->id, 'company_id' => $company->id, 'supplier_sku' => 'SKU-λ', 'cost' => '123456789012345.1234', 'created_at' => '2025-01-02 03:04:05', 'updated_at' => '2025-02-03 04:05:06'];
    DB::table('product_suppliers')->insert($row);
    $columns = ['product_id', 'supplier_id', 'supplier_sku', 'cost', 'created_at', 'updated_at'];
    $before = DB::table('product_suppliers')->first($columns);
    $migration = supplierMigration('strengthen_product_supplier_tenant_constraints');
    $migration->down();
    expect(Schema::hasColumn('product_suppliers', 'company_id'))->toBeFalse();
    expect(DB::table('product_suppliers')->first($columns))->toEqual($before);
    $migration->up();
    expect(DB::table('product_suppliers')->first($columns))->toEqual($before);
    expect(DB::table('product_suppliers')->value('company_id'))->toBe($company->id);
    $supplier->delete();
    expect(DB::table('product_suppliers')->count())->toBe(0);
    $supplier = SupplierFactory::new()->create(['company_id' => $company->id]);
    DB::table('product_suppliers')->insert([...$row, 'supplier_id' => $supplier->id]);
    $product->delete();
    expect(DB::table('product_suppliers')->count())->toBe(0);
    $company->delete();
    expect($supplier->fresh())->toBeNull();
});

it('rejects foreign tenant links before any tenant migration DDL', function () {
    $migration = supplierMigration('strengthen_product_supplier_tenant_constraints');
    $migration->down();
    $supplier = SupplierFactory::new()->create();
    $product = ProductFactory::new()->create();
    DB::table('product_suppliers')->insert(['product_id' => $product->id, 'supplier_id' => $supplier->id]);
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Invalid legacy');
    expect(Schema::hasTable('product_suppliers_phase4a'))->toBeFalse();
    expect(Schema::hasColumn('product_suppliers', 'company_id'))->toBeFalse();
    expect(DB::table('product_suppliers')->count())->toBe(1);
});

it('normalizes primary and default choices deterministically without changing timestamps', function () {
    $supplier = SupplierFactory::new()->create();
    $contacts = collect([false, true, true])->map(fn ($primary) => $supplier->contacts()->create(['name' => 'Contact', 'is_primary' => $primary]));
    $addresses = collect([false, true, true])->map(fn ($default) => $supplier->addresses()->create(['type' => 'billing', 'line_1' => 'Address', 'is_default' => $default]));
    $shipping = $supplier->addresses()->create(['type' => 'shipping', 'line_1' => 'Depot']);
    $timestamps = DB::table('supplier_contacts')->pluck('updated_at', 'id');
    $migration = supplierMigration('normalize_supplier_contact_and_address_defaults');
    $migration->up();
    $migration->up();
    $migration->down();
    expect($supplier->contacts()->where('is_primary', true)->sole()->id)->toBe($contacts[1]->id);
    expect($supplier->addresses()->where('type', 'billing')->where('is_default', true)->sole()->id)->toBe($addresses[1]->id);
    expect($shipping->fresh()->is_default)->toBeTrue();
    expect(DB::table('supplier_contacts')->pluck('updated_at', 'id'))->toEqual($timestamps);
});

it('refuses profile rollback before changing any business data', function () {
    $supplier = SupplierFactory::new()->create(['legal_name' => 'Preserved Ltd', 'notes' => 'Business information']);
    $before = $supplier->fresh()->getAttributes();
    expect(fn () => supplierMigration('add_supplier_phase_4a_fields')->down())->toThrow(RuntimeException::class, 'forward-only');
    expect($supplier->fresh()->getAttributes())->toBe($before);
});

it('upgrades populated legacy profiles without losing children or timestamps', function () {
    $this->guardSupplierDatabase();
    DB::purge();
    foreach (glob(database_path('migrations/*.php')) as $file) {
        if (str_contains($file, '2026_09_23_')) {
            break;
        }
        (require $file)->up();
    }
    $company = CompanyFactory::new()->create();
    $supplierId = DB::table('suppliers')->insertGetId(['uuid' => (string) Str::uuid(), 'company_id' => $company->id, 'name' => 'Legacy', 'code' => 'legacy-manual', 'created_at' => '2020-01-01 00:00:00']);
    DB::table('supplier_contacts')->insert(['uuid' => (string) Str::uuid(), 'supplier_id' => $supplierId, 'name' => 'Original']);
    DB::table('supplier_addresses')->insert(['uuid' => (string) Str::uuid(), 'supplier_id' => $supplierId, 'type' => 'other', 'line_1' => 'Original']);
    $before = DB::table('suppliers')->first();
    supplierMigration('add_supplier_phase_4a_fields')->up();
    supplierMigration('normalize_supplier_contact_and_address_defaults')->up();
    foreach ((array) $before as $key => $value) {
        expect(DB::table('suppliers')->value($key))->toBe($value);
    }
    expect(DB::table('suppliers')->value('currency'))->toBe('KES');
    expect(DB::table('supplier_contacts')->count())->toBe(1);
    expect((int) DB::table('supplier_contacts')->value('is_primary'))->toBe(1);
    expect(DB::table('supplier_addresses')->count())->toBe(1);
});

it('restores SQLite foreign keys and data on a late integrity failure', function () {
    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('SQLite-specific transactional rebuild.');
    }
    $migration = supplierMigration('strengthen_product_supplier_tenant_constraints');
    $migration->down();
    DB::statement('PRAGMA foreign_keys = OFF');
    DB::table('supplier_contacts')->insert(['uuid' => (string) Str::uuid(), 'supplier_id' => 999999, 'name' => 'Orphan']);
    DB::statement('PRAGMA foreign_keys = ON');
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'foreign_key_check');
    expect((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
    expect(Schema::hasColumn('product_suppliers', 'company_id'))->toBeFalse();
    expect(Schema::hasTable('product_suppliers_phase4a'))->toBeFalse();
    expect(DB::table('supplier_contacts')->count())->toBe(1);
    DB::table('supplier_contacts')->delete();
    $migration->up();
    expect(DB::select('PRAGMA foreign_key_check'))->toBe([]);
});

it('defines actual MySQL composite keys and retains exact decimal precision', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Requires direct MySQL.');
    }
    $keys = DB::table('information_schema.KEY_COLUMN_USAGE')->whereRaw('TABLE_SCHEMA = DATABASE()')->where('TABLE_NAME', 'product_suppliers')->whereIn('CONSTRAINT_NAME', ['product_suppliers_product_company_fk', 'product_suppliers_supplier_company_fk'])->count();
    expect($keys)->toBe(4);
    $indexes = collect(Schema::getIndexes('product_suppliers'))->pluck('name')->all();
    expect($indexes)->toContain('product_suppliers_product_company_index', 'product_suppliers_supplier_company_index');
    $supplier = SupplierFactory::new()->create();
    $product = ProductFactory::new()->create(['company_id' => $supplier->company_id]);
    DB::table('product_suppliers')->insert(['product_id' => $product->id, 'supplier_id' => $supplier->id, 'company_id' => $supplier->company_id, 'cost' => '123456789012345.1234']);
    expect(DB::table('product_suppliers')->value('cost'))->toBe('123456789012345.1234');
    $supplier->update(['code' => 'Mixed-Case']);
    expect(fn () => SupplierFactory::new()->create(['company_id' => $supplier->company_id, 'code' => 'mixed-case']))->toThrow(QueryException::class);
});
