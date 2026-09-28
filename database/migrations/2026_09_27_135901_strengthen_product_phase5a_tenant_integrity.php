<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** MySQL DDL implicitly commits: use a maintenance window and verified backup.
     * Preflight is read-only, but interrupted MySQL DDL requires manual inspection;
     * it is not automatically restartable. SQLite rebuilds are transactional.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $this->run(function (Connection $db): void {
            $this->preflight($db);
            $schema = $db->getSchemaBuilder();
            foreach (['categories', 'brands', 'units'] as $table) {
                $schema->table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->boolean('is_active')->default(true);
                    $blueprint->unique(['id', 'company_id'], $table.'_id_company_unique');
                });
            }
            $schema->table('categories', function (Blueprint $table): void {
                $table->dropForeign(['parent_id']);
                $table->foreign(['parent_id', 'company_id'], 'categories_parent_company_fk')->references(['id', 'company_id'])->on('categories')->restrictOnDelete();
            });
            $schema->table('products', function (Blueprint $table): void {
                foreach (['category', 'unit', 'brand'] as $name) {
                    $table->dropForeign([$name.'_id']);
                    $table->foreign([$name.'_id', 'company_id'], 'products_'.$name.'_company_fk')->references(['id', 'company_id'])->on(match ($name) {
                        'category' => 'categories', 'unit' => 'units', 'brand' => 'brands'
                    })->restrictOnDelete();
                }
                $table->index(['company_id', 'created_at', 'id'], 'products_company_listing_index');
            });
            foreach (['product_prices', 'product_barcodes'] as $name) {
                $schema->table($name, fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
                $db->table($name)->orderBy('id')->eachById(function (object $row) use ($db, $name): void {
                    $db->table($name)->where('id', $row->id)->update(['company_id' => $db->table('products')->where('id', $row->product_id)->value('company_id')]);
                });
                $schema->table($name, function (Blueprint $table) use ($name): void {
                    $table->unsignedBigInteger('company_id')->nullable(false)->change();
                    $table->dropForeign(['product_id']);
                    $table->foreign(['product_id', 'company_id'], $name.'_product_company_fk')->references(['id', 'company_id'])->on('products')->cascadeOnDelete();
                    if ($name === 'product_barcodes') {
                        $table->dropUnique('product_barcodes_barcode_unique');
                        $table->unique(['company_id', 'barcode'], 'product_barcodes_company_barcode_unique');
                    }
                });
            }
        });
    }

    /** Refuse rollback if it would discard archive state or cannot restore global
     * barcode uniqueness. Otherwise company columns are derivable and removable;
     * all original business values and timestamps are preserved.
     */
    public function down(): void
    {
        $this->run(function (Connection $db): void {
            foreach (['categories', 'brands', 'units'] as $name) {
                if ($db->table($name)->where('is_active', false)->exists()) {
                    throw new RuntimeException('Rollback would discard classification archive state; use a forward correction.');
                }
            }
            if ($db->table('product_barcodes')->select('barcode')->groupBy('barcode')->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException('Rollback cannot restore global barcode uniqueness.');
            }
            $schema = $db->getSchemaBuilder();
            foreach (['product_prices', 'product_barcodes'] as $name) {
                $schema->table($name, function (Blueprint $table) use ($name, $db): void {
                    $table->dropForeign($db->getDriverName() === 'sqlite' ? ['product_id', 'company_id'] : $name.'_product_company_fk');
                    if ($name === 'product_barcodes') {
                        $table->dropUnique('product_barcodes_company_barcode_unique');
                        $table->unique('barcode', 'product_barcodes_barcode_unique');
                    }
                    $table->dropColumn('company_id');
                    $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                });
            }
            $schema->table('products', function (Blueprint $table) use ($db): void {
                foreach (['category', 'unit', 'brand'] as $name) {
                    $table->dropForeign($db->getDriverName() === 'sqlite' ? [$name.'_id', 'company_id'] : 'products_'.$name.'_company_fk');
                    $foreign = $table->foreign($name.'_id')->references('id')->on(match ($name) {
                        'category' => 'categories', 'unit' => 'units', 'brand' => 'brands'
                    });
                    if ($name === 'brand') {
                        $foreign->nullOnDelete();
                    } else {
                        $foreign->restrictOnDelete();
                    }
                }
                $table->dropIndex('products_company_listing_index');
            });
            $schema->table('categories', function (Blueprint $table) use ($db): void {
                $table->dropForeign($db->getDriverName() === 'sqlite' ? ['parent_id', 'company_id'] : 'categories_parent_company_fk');
                $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            });
            foreach (['categories', 'brands', 'units'] as $name) {
                $schema->table($name, function (Blueprint $table) use ($name): void {
                    $table->dropUnique($name.'_id_company_unique');
                    $table->dropColumn('is_active');
                });
            }
        });
    }

    private function preflight(Connection $db): void
    {
        foreach (['category' => 'categories', 'unit' => 'units', 'brand' => 'brands'] as $key => $table) {
            if ($db->table('products as p')->leftJoin($table.' as c', 'c.id', '=', 'p.'.$key.'_id')->whereNotNull('p.'.$key.'_id')->where(fn ($q) => $q->whereNull('c.id')->orWhereColumn('p.company_id', '<>', 'c.company_id'))->exists()) {
                throw new RuntimeException('Invalid Product classification Company; no DDL performed.');
            }
        }
        $categories = $db->table('categories')->get()->keyBy('id');
        foreach ($categories as $category) {
            $id = $category->id;
            $seen = [];
            $depth = 0;
            while ($id !== null) {
                $node = $categories->get($id);
                if ($node === null || $node->company_id !== $category->company_id || isset($seen[$id]) || ++$depth > 3) {
                    throw new RuntimeException('Invalid category Company, cycle or depth; no DDL performed.');
                }
                $seen[$id] = true;
                $id = $node->parent_id;
            }
        }
        foreach (['product_prices', 'product_barcodes'] as $name) {
            if ($db->table($name.' as c')->leftJoin('products as p', 'p.id', '=', 'c.product_id')->whereNull('p.id')->exists()) {
                throw new RuntimeException('Orphan Product child; no DDL performed.');
            }
        }
        if ($db->table('product_barcodes as b')->join('products as p', 'p.id', '=', 'b.product_id')->select('p.company_id', 'b.barcode')->groupBy('p.company_id', 'b.barcode')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate Company barcode; no DDL performed.');
        }
        foreach ($db->table('product_prices')->cursor() as $price) {
            if (! preg_match('/\A[A-Z]{3}\z/D', $price->currency) || ! preg_match('/\A[0-9]{1,15}(?:\.[0-9]{1,4})?\z/D', (string) $price->price)) {
                throw new RuntimeException('Invalid legacy price currency or decimal; no DDL performed.');
            }
        }
    }

    private function run(Closure $operation): void
    {
        $db = DB::connection();
        if ($db->getDriverName() !== 'sqlite') {
            $operation($db);

            return;
        }
        if ($db->transactionLevel() !== 0) {
            throw new RuntimeException('Product tenant migration requires no outer SQLite transaction.');
        }
        $original = (int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys;
        try {
            $db->statement('PRAGMA foreign_keys = OFF');
            if ((int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys !== 0) {
                throw new RuntimeException('Could not disable SQLite foreign keys.');
            }
            $db->transaction(function () use ($db, $operation): void {
                $operation($db);
                if ($db->select('PRAGMA foreign_key_check') !== []) {
                    throw new RuntimeException('Product migration failed foreign_key_check.');
                }
            });
        } finally {
            $db->statement('PRAGMA foreign_keys = '.($original === 1 ? 'ON' : 'OFF'));
            if ((int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys !== $original) {
                throw new RuntimeException('Could not restore SQLite foreign keys.');
            }
        }
    }
};
