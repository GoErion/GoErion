<?php

declare(strict_types=1);

use Tests\Support\SupplierDatabaseGuard;

it('rejects nonexact database names before any connection or cleanup', function (string $database) {
    expect(fn () => SupplierDatabaseGuard::cleanupTargets($database, []))->toThrow(RuntimeException::class);
    expect(fn () => SupplierDatabaseGuard::assertRunnable('testing', ['driver' => 'mysql', 'database' => $database]))->toThrow(RuntimeException::class);
})->with([
    'goerion', 'erion', 'erions', 'goblog', 'goerion_db',
    'goerion_customer_phase3a_disposable_test', 'goerion_customer_phase3a_test',
    '', 'goerion_supplier_test', 'goerion_supplier_phase4a_test_other',
    'GOERION_SUPPLIER_PHASE4A_TEST', 'goerion_supplier_phase4a_test ', '%',
]);

it('allows only in-memory SQLite while MySQL isolation is unverified', function () {
    SupplierDatabaseGuard::assertRunnable('testing', ['driver' => 'sqlite', 'database' => ':memory:']);

    expect(fn () => SupplierDatabaseGuard::assertRunnable('testing', [
        'driver' => 'mysql', 'database' => SupplierDatabaseGuard::DATABASE,
    ]))->toThrow(RuntimeException::class, 'disabled');
});

it('rejects unsafe SQLite configuration before opening a connection', function (string $environment, array $changes) {
    expect(fn () => SupplierDatabaseGuard::assertRunnable($environment, [
        'driver' => 'sqlite', 'database' => ':memory:', ...$changes,
    ]))->toThrow(RuntimeException::class);
})->with([
    ['production', []], ['testing', ['database' => 'database/database.sqlite']],
    ['testing', ['url' => 'mysql://invalid']], ['testing', ['prefix' => 'foreign.']],
    ['testing', ['read' => []]], ['testing', ['write' => []]], ['testing', ['direct' => []]],
]);

it('generates only unqualified targets from the exact Supplier schema', function () {
    $targets = SupplierDatabaseGuard::cleanupTargets(SupplierDatabaseGuard::DATABASE, [
        ['table_schema' => SupplierDatabaseGuard::DATABASE, 'table_name' => 'suppliers'],
        ['table_schema' => SupplierDatabaseGuard::DATABASE, 'table_name' => 'supplier_contacts'],
        ['table_schema' => SupplierDatabaseGuard::DATABASE, 'table_name' => 'migrations'],
    ]);

    expect($targets)->toBe(['suppliers', 'supplier_contacts']);
});

it('rejects the entire cleanup plan when any target is foreign or qualified', function (string $schema, string $table) {
    expect(fn () => SupplierDatabaseGuard::cleanupTargets(SupplierDatabaseGuard::DATABASE, [
        ['table_schema' => SupplierDatabaseGuard::DATABASE, 'table_name' => 'suppliers'],
        ['table_schema' => $schema, 'table_name' => $table],
    ]))->toThrow(RuntimeException::class);
})->with([
    ['goerion', 'suppliers'], ['erion', 'users'],
    [SupplierDatabaseGuard::DATABASE, 'goerion.suppliers'],
    [SupplierDatabaseGuard::DATABASE, 'goerion_supplier_phase4a_test.suppliers'],
    [SupplierDatabaseGuard::DATABASE, '`suppliers`'],
    [SupplierDatabaseGuard::DATABASE, 'suppliers; DROP TABLE users'],
    [SupplierDatabaseGuard::DATABASE, '%'], [SupplierDatabaseGuard::DATABASE, ''],
]);

it('requires explicit candidate endpoint values without treating them as isolation proof', function () {
    $explicit = ['host' => '127.0.0.1', 'port' => '13306', 'database' => SupplierDatabaseGuard::DATABASE, 'username' => 'supplier_disposable'];
    $configuration = ['driver' => 'mysql', ...$explicit];
    SupplierDatabaseGuard::assertMysqlCandidate($configuration, $explicit);

    foreach (array_keys($explicit) as $key) {
        $missing = $explicit;
        unset($missing[$key]);
        expect(fn () => SupplierDatabaseGuard::assertMysqlCandidate($configuration, $missing))->toThrow(RuntimeException::class);
    }

    expect(fn () => SupplierDatabaseGuard::assertRunnable('testing', $configuration))->toThrow(RuntimeException::class, 'disabled');
});

it('rejects privileged and ambiguous candidate configurations', function (array $changes) {
    $explicit = ['host' => '127.0.0.1', 'port' => '13306', 'database' => SupplierDatabaseGuard::DATABASE, 'username' => 'supplier_disposable', ...$changes];

    expect(fn () => SupplierDatabaseGuard::assertMysqlCandidate(['driver' => 'mysql', ...$explicit], $explicit))->toThrow(RuntimeException::class);
})->with([
    [['username' => 'root']], [['port' => '0']], [['port' => '65536']],
    [['host' => 'localhost;dbname=foreign']], [['url' => 'mysql://invalid']],
    [['unix_socket' => '/invalid/socket']], [['read' => []]], [['write' => []]],
    [['options' => [1002 => 'USE foreign']]],
]);
