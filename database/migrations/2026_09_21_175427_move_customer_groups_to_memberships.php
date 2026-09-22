<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL DDL is not fully transactional. Preflight and copy verification reduce risk but
     * cannot make these schema changes atomic. Deploy with a verified database backup in a
     * controlled maintenance window. If interrupted, inspect the actual schema and restore
     * from backup or use a reviewed recovery procedure; never blindly rerun up() or down().
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'sqlite') {
            $this->runSqlite($connection, fn (): mixed => $this->migrateUp($connection));

            return;
        }

        $this->migrateUp($connection);
    }

    public function down(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'sqlite') {
            $this->runSqlite($connection, fn (): mixed => $this->migrateDown($connection));

            return;
        }

        $this->migrateDown($connection);
    }

    private function migrateUp(Connection $connection): void
    {
        $schema = $connection->getSchemaBuilder();
        $violations = $connection->table('customers as c')
            ->join('customer_groups as g', 'g.id', '=', 'c.customer_group_id')
            ->whereNotNull('c.customer_group_id')
            ->whereColumn('c.company_id', '<>', 'g.company_id')
            ->count();

        if ($violations > 0) {
            throw new RuntimeException("Cannot migrate customer groups: {$violations} cross-company assignments exist.");
        }

        $expected = $connection->table('customers')->whereNotNull('customer_group_id')->count();
        $schema->table('customers', fn (Blueprint $table) => $table->unique(['id', 'company_id'], 'customers_id_company_id_unique'));
        $schema->table('customer_groups', fn (Blueprint $table) => $table->unique(['id', 'company_id'], 'customer_groups_id_company_id_unique'));
        $schema->create('customer_group_memberships', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id')->primary();
            $table->unsignedBigInteger('customer_group_id');
            $table->unsignedBigInteger('company_id');
            $table->index(['customer_group_id', 'company_id'], 'customer_group_memberships_group_company_index');
            $table->foreign(['customer_id', 'company_id'], 'customer_group_memberships_customer_company_fk')
                ->references(['id', 'company_id'])->on('customers')->cascadeOnDelete();
            $table->foreign(['customer_group_id', 'company_id'], 'customer_group_memberships_group_company_fk')
                ->references(['id', 'company_id'])->on('customer_groups')->cascadeOnDelete();
        });

        $connection->table('customers')->whereNotNull('customer_group_id')->orderBy('id')
            ->chunkById(500, function ($customers) use ($connection): void {
                $connection->table('customer_group_memberships')->insert($customers->map(fn ($customer): array => [
                    'customer_id' => $customer->id,
                    'customer_group_id' => $customer->customer_group_id,
                    'company_id' => $customer->company_id,
                ])->all());
            });

        if ($connection->table('customer_group_memberships')->count() !== $expected) {
            throw new RuntimeException('Customer group assignment copy count mismatch; legacy column retained.');
        }

        if ($connection->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCustomers($connection, false);
        } else {
            $schema->table('customers', function (Blueprint $table): void {
                $table->dropForeign('customers_customer_group_id_foreign');
                $table->dropColumn('customer_group_id');
            });
        }

        if ($connection->table('customer_group_memberships')->count() !== $expected) {
            throw new RuntimeException('Customer group assignments changed during schema conversion.');
        }
    }

    private function migrateDown(Connection $connection): void
    {
        $schema = $connection->getSchemaBuilder();

        if ($connection->getDriverName() === 'sqlite') {
            $schema->table('customers', fn (Blueprint $table) => $table->unsignedBigInteger('customer_group_id')->nullable());
        } else {
            $schema->table('customers', fn (Blueprint $table) => $table->unsignedBigInteger('customer_group_id')->nullable());
        }

        $expected = $connection->table('customer_group_memberships')->count();
        $connection->table('customer_group_memberships')->orderBy('customer_id')
            ->chunkById(500, function ($memberships) use ($connection): void {
                foreach ($memberships as $membership) {
                    $updated = $connection->table('customers')
                        ->where('id', $membership->customer_id)
                        ->where('company_id', $membership->company_id)
                        ->update(['customer_group_id' => $membership->customer_group_id]);

                    if ($updated !== 1) {
                        throw new RuntimeException('Cannot restore a Customer group assignment to its original Company.');
                    }
                }
            }, 'customer_id');

        $restored = $connection->table('customer_group_memberships as m')
            ->join('customers as c', function ($join): void {
                $join->on('c.id', '=', 'm.customer_id')->on('c.company_id', '=', 'm.company_id');
            })
            ->whereColumn('c.customer_group_id', 'm.customer_group_id')
            ->count();

        if ($restored !== $expected || $connection->table('customers')->whereNotNull('customer_group_id')->count() !== $expected) {
            throw new RuntimeException('Customer group assignment restoration mismatch; membership table retained.');
        }

        $schema->drop('customer_group_memberships');

        if ($connection->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCustomers($connection, true);
        } else {
            $schema->table('customers', fn (Blueprint $table) => $table->foreign('customer_group_id', 'customers_customer_group_id_foreign')
                ->references('id')->on('customer_groups')->nullOnDelete());
            $schema->table('customers', fn (Blueprint $table) => $table->dropUnique('customers_id_company_id_unique'));
        }

        $schema->table('customer_groups', fn (Blueprint $table) => $table->dropUnique('customer_groups_id_company_id_unique'));
    }

    private function runSqlite(Connection $connection, Closure $operation): void
    {
        if ($connection->transactionLevel() !== 0) {
            throw new RuntimeException('SQLite Customer group migration requires no outer transaction.');
        }

        $original = (int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys;
        $failure = null;

        try {
            $connection->statement('PRAGMA foreign_keys = OFF');
            if ((int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys !== 0) {
                throw new RuntimeException('Could not disable SQLite foreign keys before migration transaction.');
            }

            $connection->transaction(function () use ($connection, $operation): void {
                $operation();
                $violations = $connection->select('PRAGMA foreign_key_check');
                if ($violations !== []) {
                    throw new RuntimeException('SQLite Customer group migration failed foreign_key_check: '.count($violations).' violations.');
                }
            });
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                $connection->statement('PRAGMA foreign_keys = '.($original === 1 ? 'ON' : 'OFF'));
                if ((int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys !== $original) {
                    throw new RuntimeException('SQLite foreign_keys state was not restored after Customer group migration.');
                }
            } catch (Throwable $restoreException) {
                if ($failure === null) {
                    $failure = $restoreException;
                }
            }
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    private function rebuildSqliteCustomers(Connection $connection, bool $withGroup): void
    {
        $schema = $connection->getSchemaBuilder();
        $schema->create('customers_rebuilt', function (Blueprint $table) use ($withGroup): void {
            $table->id();
            $table->uuid('uuid')->unique($withGroup ? 'customers_rebuilt_down_uuid_unique' : 'customers_rebuilt_up_uuid_unique');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            if ($withGroup) {
                $table->foreignId('customer_group_id')->nullable()->constrained()->nullOnDelete();
            }
            $table->string('name');
            $table->string('code');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('credit_limit', 19, 4)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code'], $withGroup ? 'customers_rebuilt_down_company_code_unique' : 'customers_rebuilt_up_company_code_unique');
            if (! $withGroup) {
                $table->unique(['id', 'company_id'], 'customers_rebuilt_id_company_id_unique');
            }
        });

        $columns = 'id, uuid, company_id, name, code, email, phone, credit_limit, payment_terms_days, is_active, created_at, updated_at';
        $group = $withGroup ? ', customer_group_id' : '';
        $connection->statement("INSERT INTO customers_rebuilt ({$columns}{$group}) SELECT {$columns}{$group} FROM customers");
        $schema->drop('customers');
        $schema->rename('customers_rebuilt', 'customers');
    }
};
