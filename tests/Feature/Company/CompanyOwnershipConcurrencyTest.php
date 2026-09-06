<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Actions\Company\PromoteCompanyOwner;
use App\Enums\CompanyAccessEventType;
use App\Models\Company\Company;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

it('serializes competing owner demotion and departure on MySQL', function (string $departureOperation) {
    $connection = DB::connection();
    if ($connection->getDriverName() === 'sqlite') {
        $this->markTestSkipped('Requires MySQL database goerion_phase2b1_concurrency_test via phpunit.mysql.xml.');
    }

    expect(app()->environment())->toBe('testing');
    expect($connection->getDriverName())->toBe('mysql');
    expect($connection->selectOne('SELECT DATABASE() AS name')->name)->toBe('goerion_phase2b1_concurrency_test');

    $ownerA = UserFactory::new()->create();
    $ownerB = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Concurrent Ownership '.Str::uuid()], $ownerA);
    $company->users()->attach($ownerB);
    app(PromoteCompanyOwner::class)->handle($company, $ownerB, $ownerA);
    $initialEventId = $company->accessEvents()->max('id');
    $ownerRole = $company->ownerRole()->sole();

    $workerEnvironment = [
        'APP_ENV' => 'testing',
        'DB_URL' => '',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'goerion_phase2b1_concurrency_test',
        'DB_HOST' => (string) config('database.connections.mysql.host'),
        'DB_PORT' => (string) config('database.connections.mysql.port'),
        'DB_USERNAME' => (string) config('database.connections.mysql.username'),
        'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
    ];
    $worker = base_path('tests/Support/OwnershipConcurrencyWorker.php');
    $demote = new Process([PHP_BINARY, $worker, 'demote', (string) $company->id, (string) $ownerA->id, (string) $ownerB->id], base_path(), $workerEnvironment);
    $leave = new Process([PHP_BINARY, $worker, $departureOperation, (string) $company->id, (string) $ownerB->id, (string) $ownerB->id], base_path(), $workerEnvironment);
    $demote->setTimeout(20);
    $leave->setTimeout(20);

    $connection->beginTransaction();
    Company::query()->whereKey($company)->lockForUpdate()->firstOrFail();
    $demoteOutput = '';
    $leaveOutput = '';

    try {
        $demote->start();
        $leave->start();
        $deadline = microtime(true) + 8;
        while (microtime(true) < $deadline && (! str_contains($demoteOutput, 'STARTED demote') || ! str_contains($leaveOutput, 'STARTED '.$departureOperation))) {
            $demoteOutput .= $demote->getIncrementalOutput();
            $leaveOutput .= $leave->getIncrementalOutput();
            usleep(20_000);
        }

        expect($demoteOutput)->toContain('STARTED demote')
            ->and($leaveOutput)->toContain('STARTED '.$departureOperation)
            ->and($demote->isRunning())->toBeTrue()
            ->and($leave->isRunning())->toBeTrue();

        preg_match('/STARTED demote (\d+)/', $demoteOutput, $demoteConnection);
        preg_match('/STARTED '.preg_quote($departureOperation, '/').' (\d+)/', $leaveOutput, $leaveConnection);
        $workerIds = [(int) $demoteConnection[1], (int) $leaveConnection[1]];
        expect($workerIds[0])->not->toBe($workerIds[1]);
        $waitingWorkers = [];
        $deadline = microtime(true) + 8;
        do {
            $waitingWorkers = $connection->select(
                "SELECT DISTINCT threads.PROCESSLIST_ID AS connection_id, requested.LOCK_DATA AS company_id
                 FROM performance_schema.data_lock_waits AS waits
                 JOIN performance_schema.data_locks AS requested
                   ON requested.ENGINE_LOCK_ID = waits.REQUESTING_ENGINE_LOCK_ID AND requested.ENGINE = waits.ENGINE
                 JOIN performance_schema.threads AS threads ON threads.THREAD_ID = waits.REQUESTING_THREAD_ID
                 WHERE requested.OBJECT_SCHEMA = 'goerion_phase2b1_concurrency_test'
                   AND requested.OBJECT_NAME = 'companies' AND requested.INDEX_NAME = 'PRIMARY'
                   AND requested.LOCK_STATUS = 'WAITING'
                   AND requested.LOCK_DATA = ? AND threads.PROCESSLIST_ID IN (?, ?)",
                [(string) $company->id, ...$workerIds],
            );
            if (count($waitingWorkers) === 2) {
                break;
            }
            usleep(20_000);
        } while (microtime(true) < $deadline);
        expect($waitingWorkers)->toHaveCount(2, 'Both production Actions must be waiting on the same Company primary-key lock.');
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        $demote->stop();
        $leave->stop();
        throw $exception;
    }

    $demote->wait();
    $leave->wait();
    $demoteOutput .= $demote->getIncrementalOutput();
    $leaveOutput .= $leave->getIncrementalOutput();

    expect($demote->getExitCode())->toBe(0, $demote->getErrorOutput())
        ->and($leave->getExitCode())->toBe(0, $leave->getErrorOutput())
        ->and(trim($demote->getErrorOutput()))->toBe('')
        ->and(trim($leave->getErrorOutput()))->toBe('');

    $decodeResult = static function (string $output): array {
        $lines = array_values(array_filter(explode("\n", trim($output))));

        return json_decode($lines[array_key_last($lines)], true, flags: JSON_THROW_ON_ERROR);
    };
    $results = collect([$decodeResult($demoteOutput), $decodeResult($leaveOutput)])->keyBy('operation');

    expect($results->pluck('status')->sort()->values()->all())->toBe(['rejected', 'succeeded'])
        ->and($results->where('status', 'rejected')->first()['exception'])->toBeIn([DomainException::class, AuthorizationException::class]);

    config()->set('database.connections.phase2b1_fresh', config('database.connections.mysql'));
    DB::purge('phase2b1_fresh');
    $fresh = DB::connection('phase2b1_fresh');
    expect($fresh->selectOne('SELECT DATABASE() AS name')->name)->toBe('goerion_phase2b1_concurrency_test');
    $ownerCount = $fresh->table('user_roles')->where([
        'company_id' => $company->id,
        'role_id' => $ownerRole->id,
    ])->count();
    $memberships = $fresh->table('company_user')->where('company_id', $company->id)->pluck('user_id')->sort()->values()->all();
    $ownerAssignments = $fresh->table('user_roles')->where([
        'company_id' => $company->id,
        'role_id' => $ownerRole->id,
    ])->pluck('user_id')->sort()->values()->all();
    $events = $fresh->table('company_access_events')->where('company_id', $company->id)->where('id', '>', $initialEventId)->pluck('event_type')->all();

    expect($ownerCount)->toBe(1)
        ->and($ownerAssignments)->toHaveCount(1)
        ->and($events)->toHaveCount(1);

    expect($fresh->table('user_roles')->where('company_id', $company->id)->count())->toBe(1);
    expect($results->where('status', 'rejected')->first()['message'])->toBe(
        $results['demote']['status'] === 'succeeded'
            ? ($departureOperation === 'delete-account'
                ? 'Your account is the last owner of a company. Add another owner or transfer ownership before deleting your account.'
                : 'The last owner cannot leave the company.')
            : 'This action is unauthorized.',
    );
    fwrite(STDOUT, json_encode(['database' => $fresh->getDatabaseName(), 'overlapping_company_lock_waits' => count($waitingWorkers), 'results' => $results->all(), 'owner_count' => $ownerCount, 'memberships' => $memberships, 'owner_assignments' => $ownerAssignments, 'events' => $events], JSON_THROW_ON_ERROR)."\n");

    if ($departureOperation === 'delete-account') {
        expect($fresh->table('users')->where('id', $ownerB->id)->exists())->toBe($results['demote']['status'] === 'succeeded');
        if ($results['demote']['status'] !== 'succeeded') {
            $departure = $fresh->table('company_access_events')->where('company_id', $company->id)->where('id', '>', $initialEventId)->sole();
            expect($departure->actor_user_id)->toBeNull()->and($departure->subject_user_id)->toBeNull();
            expect(json_decode($departure->metadata, true))->toBe(['reason' => 'account_deleted']);
        }
    }

    if ($results['demote']['status'] === 'succeeded') {
        expect($memberships)->toBe([$ownerA->id, $ownerB->id])
            ->and($ownerAssignments)->toBe([$ownerB->id])
            ->and($events)->toBe([CompanyAccessEventType::OwnerDemoted->value]);
    } else {
        expect($memberships)->toBe([$ownerA->id])
            ->and($ownerAssignments)->toBe([$ownerA->id])
            ->and($events)->toBe([CompanyAccessEventType::MemberLeft->value]);
    }
})->with(['leave', 'delete-account']);

it('serializes account lifecycle and rejects stale owner authority on MySQL', function (string $scenario) {
    $connection = DB::connection();
    if ($connection->getDriverName() === 'sqlite') {
        $this->markTestSkipped('Requires isolated MySQL locking tests.');
    }
    expect(app()->environment())->toBe('testing');
    expect($connection->getDriverName())->toBe('mysql');
    expect($connection->selectOne('SELECT DATABASE() AS name')->name)->toBe('goerion_phase2b1_concurrency_test');

    $actor = UserFactory::new()->create();
    $company = null;
    $target = $actor;
    $remaining = $actor;
    $initialEventId = 0;
    if ($scenario === 'stale-promotion') {
        $remaining = UserFactory::new()->create();
        $target = UserFactory::new()->create();
        $company = app(CreateCompany::class)->handle(['company_name' => 'Concurrent Authority '.Str::uuid()], $remaining);
        $company->users()->attach([$actor->id, $target->id]);
        app(PromoteCompanyOwner::class)->handle($company, $actor, $remaining);
        $initialEventId = $company->accessEvents()->max('id');
    }
    $firstOperation = match ($scenario) {
        'creation-first' => 'create-company',
        'deletion-first' => 'delete-account',
        'stale-promotion' => 'demote',
    };
    $secondOperation = match ($scenario) {
        'creation-first' => 'delete-account',
        'deletion-first' => 'create-company',
        'stale-promotion' => 'promote',
    };
    $environment = [
        'APP_ENV' => 'testing', 'DB_URL' => '', 'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'goerion_phase2b1_concurrency_test',
        'DB_HOST' => (string) config('database.connections.mysql.host'),
        'DB_PORT' => (string) config('database.connections.mysql.port'),
        'DB_USERNAME' => (string) config('database.connections.mysql.username'),
        'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
    ];
    $worker = base_path('tests/Support/OwnershipConcurrencyWorker.php');
    $release = new InputStream;
    $first = new Process([PHP_BINARY, $worker, $firstOperation, (string) ($company?->id ?? 0), (string) $actor->id, (string) $remaining->id, 'hold'], base_path(), $environment);
    $first->setInput($release);
    $second = new Process([PHP_BINARY, $worker, $secondOperation, (string) ($company?->id ?? 0), (string) $target->id, (string) $actor->id], base_path(), $environment);
    $first->setTimeout(20);
    $second->setTimeout(20);
    $table = $company ? 'companies' : 'users';
    $lockId = $company?->id ?? $actor->id;
    try {
        $first->start();
        $deadline = microtime(true) + 8;
        while (! str_contains($first->getOutput(), 'APPLIED '.$firstOperation) && $first->isRunning() && microtime(true) < $deadline) {
            usleep(20_000);
        }
        expect($first->getOutput())->toContain('APPLIED '.$firstOperation);
        $second->start();
        $deadline = microtime(true) + 8;
        $waits = [];
        do {
            if (preg_match('/STARTED \S+ (\d+)/', $second->getOutput(), $secondConnection)) {
                $waits = $connection->select(
                    "SELECT threads.PROCESSLIST_ID AS connection_id FROM performance_schema.data_lock_waits AS waits
                     JOIN performance_schema.data_locks AS requested ON requested.ENGINE_LOCK_ID = waits.REQUESTING_ENGINE_LOCK_ID AND requested.ENGINE = waits.ENGINE
                     JOIN performance_schema.threads AS threads ON threads.THREAD_ID = waits.REQUESTING_THREAD_ID
                     WHERE requested.OBJECT_SCHEMA = 'goerion_phase2b1_concurrency_test'
                       AND requested.OBJECT_NAME = ? AND requested.INDEX_NAME = 'PRIMARY'
                       AND requested.LOCK_STATUS = 'WAITING' AND requested.LOCK_DATA = ? AND threads.PROCESSLIST_ID = ?",
                    [$table, (string) $lockId, (int) $secondConnection[1]],
                );
            }
            if ($waits !== []) {
                break;
            }
            usleep(20_000);
        } while (microtime(true) < $deadline);
        expect($waits)->not->toBeEmpty();
        expect($first->isRunning())->toBeTrue();
        expect($second->isRunning())->toBeTrue();
        preg_match('/STARTED \S+ (\d+)/', $first->getOutput(), $firstConnection);
        expect($firstConnection[1])->not->toBe($secondConnection[1]);
        if ($scenario === 'stale-promotion') {
            expect($second->getOutput())->toContain('AUTHORIZED promote');
        }
        $release->write("COMMIT\n");
        $release->close();
        $first->wait();
        $second->wait();
        foreach ([$first, $second] as $process) {
            expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
            expect(trim($process->getErrorOutput()))->toBe('');
        }
        $decode = static function (Process $process): array {
            $lines = explode("\n", trim($process->getOutput()));

            return json_decode(end($lines), true, flags: JSON_THROW_ON_ERROR);
        };
        expect($decode($first)['status'])->toBe('succeeded');
        expect($decode($second)['status'])->toBe('rejected');
        $expectedException = match ($scenario) {
            'creation-first' => DomainException::class,
            'deletion-first' => ModelNotFoundException::class,
            'stale-promotion' => AuthorizationException::class,
        };
        expect($decode($second)['exception'])->toBe($expectedException);

        $created = Company::query()->where('company_name', 'Concurrent Account '.$actor->id)->first();
        if ($scenario === 'creation-first') {
            expect($actor->fresh())->not->toBeNull();
            expect($created)->not->toBeNull();
            expect($created->users()->pluck('users.id')->all())->toBe([$actor->id]);
            expect($created->roles()->count())->toBe(1);
            expect($created->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$actor->id]);
            expect($created->accessEvents()->count())->toBe(0);
        } elseif ($scenario === 'deletion-first') {
            expect($actor->fresh())->toBeNull();
            expect($created)->toBeNull();
            expect(DB::table('company_user')->where('user_id', $actor->id)->count())->toBe(0);
            expect(DB::table('user_roles')->where('user_id', $actor->id)->count())->toBe(0);
        } else {
            expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$remaining->id]);
            expect($company->users()->whereKey($target)->exists())->toBeTrue();
            expect($company->accessEvents()->where('id', '>', $initialEventId)->pluck('event_type')->all())->toBe([CompanyAccessEventType::OwnerDemoted]);
        }
        expect(DB::selectOne('SELECT COUNT(*) AS total FROM companies c WHERE NOT EXISTS (SELECT 1 FROM roles r JOIN user_roles ur ON ur.role_id = r.id AND ur.company_id = c.id WHERE r.company_id = c.id AND r.name = ? AND r.is_system = 1)', ['owner'])->total)->toBe(0);
        expect(DB::selectOne('SELECT COUNT(*) AS total FROM user_roles ur LEFT JOIN company_user cu ON cu.user_id = ur.user_id AND cu.company_id = ur.company_id JOIN roles r ON r.id = ur.role_id WHERE cu.user_id IS NULL OR r.company_id <> ur.company_id')->total)->toBe(0);
        fwrite(STDOUT, json_encode(['scenario' => $scenario, 'waited_on' => $table, 'first' => $decode($first), 'second' => $decode($second)], JSON_THROW_ON_ERROR)."\n");
    } finally {
        $release->close();
        $first->stop();
        $second->stop();
    }
})->with(['creation-first', 'deletion-first', 'stale-promotion']);
