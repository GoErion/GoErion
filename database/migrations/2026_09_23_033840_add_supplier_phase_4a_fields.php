<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** MySQL multi-statement DDL is not atomic. Preflight before DDL and deploy with
     * a verified backup. Inspect partial schema after interruption; do not blindly retry.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $this->run(fn () => $this->migrate());
    }

    private function migrate(): void
    {
        foreach (DB::table('companies')->get(['id', 'currency']) as $company) {
            if (! preg_match('/^[A-Za-z]{3}$/D', (string) $company->currency)) {
                throw new RuntimeException('Invalid Company currency; Supplier migration refused.');
            }
        }
        foreach (DB::table('suppliers')->get() as $supplier) {
            if (trim($supplier->name) === '' || trim($supplier->code) === '' || $supplier->payment_terms_days < 0 || $supplier->payment_terms_days > 3650
                || ! in_array((int) $supplier->is_active, [0, 1], true)
                || ! DB::table('companies')->where('id', $supplier->company_id)->exists()) {
                throw new RuntimeException('Invalid legacy Supplier; migration refused.');
            }
            if (str_starts_with(strtoupper($supplier->code), 'SUP-') && ! preg_match('/^SUP-[0-9]{4}-[0-9]{6,}$/D', $supplier->code)) {
                throw new RuntimeException('Malformed recognized Supplier code; migration refused.');
            }
        }
        if (DB::table('supplier_addresses')->whereNotIn('type', ['billing', 'shipping', 'other'])->exists()) {
            throw new RuntimeException('Unknown Supplier address type; migration refused.');
        }
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->string('legal_name')->nullable();
            $table->string('tax_pin', 50)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->text('notes')->nullable();
            $table->index(['company_id', 'is_active', 'name'], 'suppliers_company_status_name_index');
        });
        foreach (DB::table('companies')->get(['id', 'currency']) as $company) {
            DB::table('suppliers')->where('company_id', $company->id)->update(['currency' => strtoupper($company->currency)]);
        }
        Schema::table('suppliers', fn (Blueprint $table) => $table->string('currency', 3)->nullable(false)->change());
        Schema::table('supplier_contacts', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false);
            $table->index(['supplier_id', 'is_primary'], 'supplier_contacts_parent_primary_index');
        });
        Schema::table('supplier_addresses', fn (Blueprint $table) => $table->index(['supplier_id', 'type', 'is_default'], 'supplier_addresses_parent_type_default_index'));
    }

    public function down(): void
    {
        throw new RuntimeException('Supplier Phase 4A profile migration is forward-only: removing populated profile and primary-contact fields would destroy business data. Use a reviewed forward fix.');
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
