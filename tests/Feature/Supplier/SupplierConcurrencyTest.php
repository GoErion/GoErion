<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Actions\Supplier\CreateSupplier;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\RefreshSupplierDatabase;

uses(RefreshSupplierDatabase::class);

function requireSupplierConcurrencyDatabase(): void
{
    if (DB::connection()->getDriverName() === 'sqlite') {
        test()->markTestSkipped('Equivalent tests run against disposable MySQL database goerion_supplier_phase4a_test.');
    }

    expect(app()->environment())->toBe('testing')
        ->and(DB::connection()->getDriverName())->toBe('mysql')
        ->and(DB::selectOne('SELECT DATABASE() AS name')->name)->toBe('goerion_supplier_phase4a_test');
}

/** @param list<list<string>> $arguments
 * @return list<array{status: string, code: ?string}>
 */
function runSupplierWorkers(array $arguments, string $lockTable, int $lockId): array
{
    $connection = DB::connection();
    $environment = [
        'APP_ENV' => 'testing', 'DB_URL' => '', 'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'goerion_supplier_phase4a_test',
        'DB_HOST' => (string) config('database.connections.mysql.host'),
        'DB_PORT' => (string) config('database.connections.mysql.port'),
        'DB_USERNAME' => (string) config('database.connections.mysql.username'),
        'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
    ];
    $workers = array_map(static function (array $argument) use ($environment): Process {
        $worker = new Process([PHP_BINARY, base_path('tests/Support/SupplierConcurrencyWorker.php'), ...$argument], base_path(), $environment);
        $worker->setTimeout(20);

        return $worker;
    }, $arguments);

    $results = [];
    try {
        $connection->beginTransaction();
        $connection->table($lockTable)->where('id', $lockId)->lockForUpdate()->first();
        foreach ($workers as $worker) {
            $worker->start();
        }
        $deadline = microtime(true) + 8;
        while (microtime(true) < $deadline && count(array_filter($workers, static fn (Process $worker): bool => str_contains($worker->getOutput(), 'STARTED'))) < count($workers)) {
            usleep(20_000);
        }
        $connectionIds = [];
        foreach ($workers as $worker) {
            expect($worker->getOutput())->toContain('STARTED')
                ->and($worker->isRunning())->toBeTrue();
            preg_match('/STARTED (\d+)/', $worker->getOutput(), $match);
            $connectionIds[] = (int) $match[1];
        }
        expect(array_unique($connectionIds))->toHaveCount(count($workers));
        $parentConnectionId = (int) $connection->selectOne('SELECT CONNECTION_ID() AS id')->id;
        $waited = [];
        $deadline = microtime(true) + 8;
        while (microtime(true) < $deadline && count($waited) < count($workers)) {
            $waited = $connection->table('performance_schema.data_lock_waits as waits')
                ->join('performance_schema.threads as waiting', 'waiting.THREAD_ID', '=', 'waits.REQUESTING_THREAD_ID')
                ->join('performance_schema.threads as blocking', 'blocking.THREAD_ID', '=', 'waits.BLOCKING_THREAD_ID')
                ->join('performance_schema.data_locks as locks', 'locks.ENGINE_LOCK_ID', '=', 'waits.REQUESTING_ENGINE_LOCK_ID')
                ->where('locks.OBJECT_SCHEMA', 'goerion_supplier_phase4a_test')
                ->where('locks.OBJECT_NAME', $lockTable)
                ->whereIn('waiting.PROCESSLIST_ID', $connectionIds)
                ->where('blocking.PROCESSLIST_ID', $parentConnectionId)
                ->pluck('waiting.PROCESSLIST_ID')->unique()->all();
            if (count($waited) < count($workers)) {
                usleep(20_000);
            }
        }
        expect($waited)->toHaveCount(count($workers));
        $connection->commit();

        foreach ($workers as $worker) {
            $worker->wait();
            expect($worker->getExitCode())->toBe(0, $worker->getErrorOutput());
            expect($worker->getErrorOutput())->toBe('');
            $lines = array_values(array_filter(explode("\n", trim($worker->getOutput()))));
            $results[] = json_decode($lines[array_key_last($lines)], true, flags: JSON_THROW_ON_ERROR);
        }
    } finally {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop();
            }
        }
        DB::disconnect();
        expect(DB::transactionLevel())->toBe(0);
        foreach ($connectionIds ?? [] as $connectionId) {
            expect(DB::table('performance_schema.threads')->where('PROCESSLIST_ID', $connectionId)->exists())->toBeFalse();
        }
        DB::disconnect();
    }

    return $results;
}

it('serializes first-use and existing supplier number allocation on independent MySQL connections', function (bool $primeSequence) {
    requireSupplierConcurrencyDatabase();
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Numbers '.Str::uuid()], $owner);
    if ($primeSequence) {
        app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Primer']);
    }

    $results = runSupplierWorkers([
        ['create', (string) $company->id, '0', 'One', (string) $owner->id],
        ['create', (string) $company->id, '0', 'Two', (string) $owner->id],
    ], 'companies', $company->id);

    $codes = array_column($results, 'code');
    expect(array_column($results, 'status'))->toBe(['succeeded', 'succeeded'])
        ->and(array_unique($codes))->toHaveCount(2)
        ->and($company->numberSequences()->where('type', 'supplier')->count())->toBe(1)
        ->and($company->suppliers()->count())->toBe($primeSequence ? 3 : 2);
    foreach ($codes as $code) {
        expect($code)->toMatch('/^SUP-\d{4}-\d{6}$/');
    }
})->with([false, true]);

it('serializes competing contact primary and address default changes on independent MySQL connections', function (string $operation) {
    requireSupplierConcurrencyDatabase();
    $owner = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Details '.Str::uuid()], $owner);
    $supplier = app(CreateSupplier::class)->handle($company, $owner, ['name' => 'Concurrent Details']);
    $children = $operation === 'primary'
        ? [$supplier->contacts()->create(['name' => 'One']), $supplier->contacts()->create(['name' => 'Two'])]
        : [$supplier->addresses()->create(['type' => 'billing', 'line_1' => 'One']), $supplier->addresses()->create(['type' => 'billing', 'line_1' => 'Two'])];

    $results = runSupplierWorkers([
        [$operation, (string) $company->id, (string) $supplier->id, (string) $children[0]->id, (string) $owner->id],
        [$operation, (string) $company->id, (string) $supplier->id, (string) $children[1]->id, (string) $owner->id],
    ], 'suppliers', $supplier->id);

    expect(array_column($results, 'status'))->toBe(['succeeded', 'succeeded']);
    $query = $operation === 'primary' ? $supplier->contacts()->where('is_primary', true) : $supplier->addresses()->where('type', 'billing')->where('is_default', true);
    expect($query->count())->toBe(1);
})->with(['primary', 'default']);
