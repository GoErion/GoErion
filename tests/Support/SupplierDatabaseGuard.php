<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class SupplierDatabaseGuard
{
    public const string DATABASE = 'goerion_supplier_phase4a_test';

    /** @param array<string, mixed> $configuration */
    public static function assertRunnable(string $environment, array $configuration): void
    {
        if ($environment !== 'testing' || ! empty($configuration['url'])
            || ! empty($configuration['prefix']) || isset($configuration['read'])
            || isset($configuration['write']) || isset($configuration['direct'])) {
            throw new RuntimeException('Supplier tests require an unambiguous testing connection.');
        }

        if (($configuration['driver'] ?? null) === 'sqlite'
            && ($configuration['database'] ?? null) === ':memory:') {
            return;
        }

        if (($configuration['driver'] ?? null) === 'mysql') {
            self::assertDatabase($configuration['database'] ?? '');
        }

        throw new RuntimeException('Supplier MySQL execution is disabled pending independently verified isolation and separately approved certification. Only in-memory SQLite is runnable.');
    }

    public static function assertDatabase(mixed $database): void
    {
        if ($database !== self::DATABASE) {
            throw new RuntimeException('Supplier tests require the exact disposable database.');
        }
    }

    /**
     * Validate a future certification candidate without opening a connection.
     * This does not authorize MySQL execution or establish account isolation.
     *
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $explicit
     */
    public static function assertMysqlCandidate(array $configuration, array $explicit): void
    {
        self::assertDatabase($configuration['database'] ?? null);

        foreach (['host', 'port', 'database', 'username'] as $key) {
            if (! isset($explicit[$key]) || ! is_string($explicit[$key]) || trim($explicit[$key]) === ''
                || ($configuration[$key] ?? null) !== $explicit[$key]) {
                throw new RuntimeException('Supplier MySQL requires explicit matching connection configuration.');
            }
        }

        if (($configuration['driver'] ?? null) !== 'mysql'
            || strtolower($explicit['username']) === 'root'
            || ! ctype_digit($explicit['port']) || (int) $explicit['port'] < 1 || (int) $explicit['port'] > 65535
            || preg_match('/[;=\s]/', $explicit['host'])
            || ! empty($configuration['url']) || ! empty($configuration['unix_socket'])
            || ! empty($configuration['prefix']) || ! empty($configuration['options'])
            || isset($configuration['read']) || isset($configuration['write']) || isset($configuration['direct'])) {
            throw new RuntimeException('Supplier MySQL candidate configuration is unsafe.');
        }
    }

    /**
     * Review-only cleanup plan. There is deliberately no cleanup executor.
     * Future discovery must use WHERE table_schema = DATABASE(), never an all-schema listing.
     *
     * @param  list<array{table_schema: string, table_name: string}>  $tables
     * @return list<string>
     */
    public static function cleanupTargets(string $database, array $tables): array
    {
        self::assertDatabase($database);
        $targets = [];

        foreach ($tables as $table) {
            if (($table['table_schema'] ?? null) !== $database
                || ! is_string($table['table_name'] ?? null)
                || ! preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]{0,63}\z/', $table['table_name'])) {
                throw new RuntimeException('Supplier cleanup plan contains an invalid or foreign table.');
            }

            if ($table['table_name'] !== 'migrations') {
                $targets[] = $table['table_name'];
            }
        }

        return array_values(array_unique($targets));
    }
}
