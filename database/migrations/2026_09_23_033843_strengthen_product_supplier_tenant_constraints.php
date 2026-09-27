<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** MySQL DDL spans implicit commits. Use a verified backup and a maintenance
     * window. After interruption inspect original/copy tables before any recovery.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $this->run(function (Connection $connection): void {
            $this->preflight($connection);
            $schema = $connection->getSchemaBuilder();
            $schema->table('products', fn (Blueprint $table) => $table->unique(['id', 'company_id'], 'products_id_company_id_unique'));
            $schema->table('suppliers', fn (Blueprint $table) => $table->unique(['id', 'company_id'], 'suppliers_id_company_id_unique'));
            $this->rebuild($connection, true);
        });
    }

    public function down(): void
    {
        $this->run(function (Connection $connection): void {
            $this->preflight($connection);
            $this->rebuild($connection, false);
            $connection->getSchemaBuilder()->table('products', fn (Blueprint $table) => $table->dropUnique('products_id_company_id_unique'));
            $connection->getSchemaBuilder()->table('suppliers', fn (Blueprint $table) => $table->dropUnique('suppliers_id_company_id_unique'));
        });
    }

    private function preflight(Connection $connection): void
    {
        $invalid = $connection->table('product_suppliers as ps')->leftJoin('products as p', 'p.id', '=', 'ps.product_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'ps.supplier_id')
            ->where(fn ($query) => $query->whereNull('p.id')->orWhereNull('s.id')->orWhereColumn('p.company_id', '<>', 's.company_id'))->exists();
        $duplicates = $connection->table('product_suppliers')->select('product_id', 'supplier_id')->groupBy('product_id', 'supplier_id')->havingRaw('COUNT(*) > 1')->exists();
        if ($invalid || $duplicates) {
            throw new RuntimeException('Invalid legacy ProductSupplier parents, Company alignment or duplicate pairs; no DDL performed.');
        }
        if ($connection->getSchemaBuilder()->hasColumn('product_suppliers', 'company_id') && $connection->table('product_suppliers as ps')->join('products as p', 'p.id', '=', 'ps.product_id')->whereColumn('ps.company_id', '<>', 'p.company_id')->exists()) {
            throw new RuntimeException('Invalid ProductSupplier Company; rollback refused.');
        }
    }

    private function rebuild(Connection $connection, bool $tenant): void
    {
        $schema = $connection->getSchemaBuilder();
        $temporary = $tenant ? 'product_suppliers_phase4a' : 'product_suppliers_legacy';
        if ($schema->hasTable($temporary)) {
            throw new RuntimeException('A ProductSupplier migration copy already exists. Inspect interrupted DDL before proceeding.');
        }
        $schema->create($temporary, function (Blueprint $table) use ($tenant): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('supplier_id');
            if ($tenant) {
                $table->unsignedBigInteger('company_id');
            }
            $table->string('supplier_sku')->nullable();
            $table->decimal('cost', 19, 4)->nullable();
            $table->timestamps();
            $table->primary(['product_id', 'supplier_id']);
            if ($tenant) {
                $table->index(['product_id', 'company_id'], 'product_suppliers_product_company_index');
                $table->index(['supplier_id', 'company_id'], 'product_suppliers_supplier_company_index');
                $table->foreign(['product_id', 'company_id'], 'product_suppliers_product_company_fk')->references(['id', 'company_id'])->on('products')->cascadeOnDelete();
                $table->foreign(['supplier_id', 'company_id'], 'product_suppliers_supplier_company_fk')->references(['id', 'company_id'])->on('suppliers')->cascadeOnDelete();
            } else {
                $table->foreign('product_id', 'product_suppliers_product_id_foreign')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('supplier_id', 'product_suppliers_supplier_id_foreign')->references('id')->on('suppliers')->cascadeOnDelete();
            }
        });
        $columns = ['product_id', 'supplier_id', 'supplier_sku', 'cost', 'created_at', 'updated_at'];
        $query = $connection->table('product_suppliers as ps')->select(array_map(fn ($column) => 'ps.'.$column, $columns));
        if ($tenant) {
            $columns[] = 'company_id';
            $query->join('products as p', 'p.id', '=', 'ps.product_id')->addSelect('p.company_id');
        }
        $connection->table($temporary)->insertUsing($columns, $query);
        $original = $connection->table('product_suppliers')->orderBy('product_id')->orderBy('supplier_id')->get(array_slice($columns, 0, 6));
        $copied = $connection->table($temporary)->orderBy('product_id')->orderBy('supplier_id')->get(array_slice($columns, 0, 6));
        if ($original->toJson() !== $copied->toJson()) {
            throw new RuntimeException('ProductSupplier copy verification failed; original table retained.');
        }
        $schema->drop('product_suppliers');
        $schema->rename($temporary, 'product_suppliers');
    }

    private function run(Closure $operation): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            $operation($connection);

            return;
        }
        if ($connection->transactionLevel() !== 0) {
            throw new RuntimeException('SQLite Supplier tenant migration requires no outer transaction.');
        }
        $original = (int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys;
        try {
            $connection->statement('PRAGMA foreign_keys = OFF');
            if ((int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys !== 0) {
                throw new RuntimeException('Could not disable SQLite foreign keys.');
            }
            $connection->transaction(function () use ($connection, $operation): void {
                $operation($connection);
                if ($connection->select('PRAGMA foreign_key_check') !== []) {
                    throw new RuntimeException('Supplier migration failed foreign_key_check.');
                }
            });
        } finally {
            $connection->statement('PRAGMA foreign_keys = '.($original === 1 ? 'ON' : 'OFF'));
            if ((int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys !== $original) {
                throw new RuntimeException('Could not restore SQLite foreign_keys state.');
            }
        }
    }
};
