<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait RefreshCustomerDatabase
{
    use RefreshDatabase {
        refreshDatabase as refreshSqliteDatabase;
    }

    public function refreshDatabase(): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            $this->refreshSqliteDatabase();

            return;
        }

        $this->guardCustomerDatabase($connection);
        $tables = $this->customerTables();
        $this->beforeApplicationDestroyed(function () use ($tables): void {
            $connection = DB::connection();
            $this->guardCustomerDatabase($connection);
            $this->truncateCustomerTables($connection, $tables);
            DB::disconnect();
        });

        $schema = $connection->getSchemaBuilder();
        if (! $schema->hasTable('customer_group_memberships')) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());
            $this->app[Kernel::class]->setArtisan(null);
        }

        if ($connection->table('migrations')->whereIn('migration', [
            '2026_09_21_175427_move_customer_groups_to_memberships',
            '2026_09_21_183946_add_customer_phase_3a_fields',
            '2026_09_21_183947_grant_customer_permissions_to_owner_roles',
            '2026_09_22_023730_normalize_customer_address_defaults',
        ])->count() !== 4) {
            throw new RuntimeException('Customer MySQL test schema is incomplete.');
        }

        if (! $schema->hasColumns('customers', ['tax_pin'])
            || ! $schema->hasColumns('customer_contacts', ['is_primary'])
            || ! $schema->hasColumns('customer_group_memberships', ['customer_id', 'customer_group_id', 'company_id'])) {
            throw new RuntimeException('Customer MySQL test schema does not contain the required Phase 3A columns.');
        }

        $foreignKeys = $connection->table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'customer_group_memberships')
            ->whereIn('CONSTRAINT_NAME', [
                'customer_group_memberships_customer_company_fk',
                'customer_group_memberships_group_company_fk',
            ])
            ->orderBy('CONSTRAINT_NAME')->orderBy('ORDINAL_POSITION')
            ->get(['CONSTRAINT_NAME', 'COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'])
            ->map(fn (object $key): string => implode(':', [
                $key->CONSTRAINT_NAME, $key->COLUMN_NAME, $key->REFERENCED_TABLE_NAME, $key->REFERENCED_COLUMN_NAME,
            ]))->all();
        if ($foreignKeys !== [
            'customer_group_memberships_customer_company_fk:customer_id:customers:id',
            'customer_group_memberships_customer_company_fk:company_id:customers:company_id',
            'customer_group_memberships_group_company_fk:customer_group_id:customer_groups:id',
            'customer_group_memberships_group_company_fk:company_id:customer_groups:company_id',
        ]) {
            throw new RuntimeException('Customer MySQL test schema does not contain the required composite tenant foreign keys.');
        }

        $this->truncateCustomerTables($connection, $tables);
    }

    private function guardCustomerDatabase(Connection $connection): void
    {
        if ($connection->getDatabaseName() !== 'goerion_customer_phase3a_test'
            || $connection->selectOne('SELECT DATABASE() AS name')->name !== 'goerion_customer_phase3a_test') {
            throw new RuntimeException('Customer MySQL tests require the disposable Customer Phase 3A database.');
        }
    }

    /** @return list<string> */
    private function customerTables(): array
    {
        return [
            'company_access_events', 'company_invitation_roles', 'company_invitations',
            'customer_addresses', 'customer_contacts', 'customer_group_memberships',
            'customers', 'customer_groups', 'number_sequences', 'role_permissions',
            'user_roles', 'company_user', 'roles', 'permissions', 'companies', 'users',
        ];
    }

    /** @param list<string> $tables */
    private function truncateCustomerTables(Connection $connection, array $tables): void
    {
        $schema = $connection->getSchemaBuilder();
        $schema->withoutForeignKeyConstraints(function () use ($connection, $tables): void {
            foreach ($tables as $table) {
                if ($connection->getSchemaBuilder()->hasTable($table)) {
                    $connection->table($table)->truncate();
                }
            }
        });
    }
}
