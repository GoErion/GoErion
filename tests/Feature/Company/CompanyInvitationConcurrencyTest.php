<?php

declare(strict_types=1);

use App\Actions\Company\AcceptInvitation;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\CreateInvitation;
use App\Enums\CompanyAccessEventType;
use App\Enums\CompanyPermission;
use App\Models\Auth\Permission;
use Carbon\CarbonImmutable;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

it('serializes invitation lifecycle using independent MySQL connections', function (string $firstOperation, string $secondOperation, string $lockTable, string $firstStatus, string $secondStatus) {
    if (DB::connection()->getDriverName() === 'sqlite') {
        $this->markTestSkipped('Requires the retained isolated MySQL database via phpunit.mysql.xml.');
    }
    expect(app()->environment())->toBe('testing');
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::selectOne('SELECT DATABASE() AS name')->name)->toBe('goerion_phase2b1_concurrency_test');
    $actor = UserFactory::new()->create();
    $recipient = UserFactory::new()->create();
    $company = app(CreateCompany::class)->handle(['company_name' => 'Concurrent Invitations '.Str::uuid()], $actor);
    $roles = RoleFactory::new()->count(2)->create(['company_id' => $company->id]);
    $payload = ['company' => $company->id, 'actor' => $actor->id, 'recipient' => $recipient->id, 'roles' => $roles->modelKeys()];
    $invitation = null;
    if ($firstOperation !== 'create' && $secondOperation !== 'create') {
        $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $roles->modelKeys());
        $invitation = $delivery->invitation;
        $payload['invitation'] = $invitation->id;
        if ($firstOperation === 'accept-claim') {
            $payload['digest'] = hash('sha256', $delivery->takeToken());
        } else {
            $payload['token'] = $delivery->takeToken();
        }
        $payload['expiry'] = $invitation->expires_at->toDateTimeString();
    }
    $secondPayload = $payload;
    if ($firstOperation === 'revoke') {
        $manager = UserFactory::new()->create();
        $managerRole = RoleFactory::new()->create(['company_id' => $company->id, 'name' => 'invitation-manager']);
        $managerRole->permissions()->attach(Permission::where('name', CompanyPermission::InvitationsManage->value)->sole());
        $company->users()->attach($manager);
        $manager->roles()->attach($managerRole, ['company_id' => $company->id, 'assigned_by' => $actor->id]);
        $payload['manager_role'] = $managerRole->id;
        $secondPayload['actor'] = $manager->id;
    }
    $initialEvents = $company->accessEvents()->count();
    $environment = [
        'APP_ENV' => 'testing', 'DB_URL' => '', 'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'goerion_phase2b1_concurrency_test',
        'DB_HOST' => (string) config('database.connections.mysql.host'),
        'DB_PORT' => (string) config('database.connections.mysql.port'),
        'DB_USERNAME' => (string) config('database.connections.mysql.username'),
        'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
    ];
    $worker = base_path('tests/Support/InvitationConcurrencyWorker.php');
    $release = new InputStream;
    $release->write(json_encode($payload + ['hold' => true], JSON_THROW_ON_ERROR)."\n");
    $first = new Process([PHP_BINARY, $worker, $firstOperation], base_path(), $environment);
    $first->setInput($release);
    $second = new Process([PHP_BINARY, $worker, $secondOperation], base_path(), $environment);
    $second->setInput(json_encode($secondPayload, JSON_THROW_ON_ERROR)."\n");
    $first->setTimeout(25);
    $second->setTimeout(25);
    try {
        $first->start();
        $deadline = microtime(true) + 10;
        while (! str_contains($first->getOutput(), 'APPLIED '.$firstOperation) && $first->isRunning() && microtime(true) < $deadline) {
            usleep(20_000);
        }
        expect($first->getOutput())->toContain('APPLIED '.$firstOperation);
        $second->start();
        $waits = [];
        $deadline = microtime(true) + 10;
        do {
            if (preg_match('/STARTED \S+ (\d+)/', $second->getOutput(), $secondConnection)) {
                $waits = DB::select(
                    "SELECT threads.PROCESSLIST_ID AS connection_id FROM performance_schema.data_lock_waits AS waits
                     JOIN performance_schema.data_locks AS requested ON requested.ENGINE_LOCK_ID = waits.REQUESTING_ENGINE_LOCK_ID AND requested.ENGINE = waits.ENGINE
                     JOIN performance_schema.threads AS threads ON threads.THREAD_ID = waits.REQUESTING_THREAD_ID
                     WHERE requested.OBJECT_SCHEMA = 'goerion_phase2b1_concurrency_test'
                       AND requested.OBJECT_NAME = ? AND requested.INDEX_NAME = 'PRIMARY'
                       AND requested.LOCK_STATUS = 'WAITING' AND requested.LOCK_DATA = ? AND threads.PROCESSLIST_ID = ?",
                    [$lockTable, (string) ($lockTable === 'users' ? $recipient->id : $company->id), (int) $secondConnection[1]],
                );
            }
            if ($waits !== []) {
                break;
            }
            usleep(20_000);
        } while (microtime(true) < $deadline);
        expect($waits)->not->toBeEmpty('The second production Action must wait on the expected primary-key lock.');
        expect($first->isRunning())->toBeTrue();
        expect($second->isRunning())->toBeTrue();
        preg_match('/STARTED \S+ (\d+)/', $first->getOutput(), $firstConnection);
        expect($firstConnection[1])->not->toBe($secondConnection[1]);
        if ($firstOperation === 'revoke') {
            expect($second->getOutput())->toContain('AUTHORIZED '.$secondOperation);
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
        expect($decode($first)['status'])->toBe($firstStatus);
        expect($decode($second)['status'])->toBe($secondStatus);
        $events = $company->accessEvents()->orderBy('id')->get()->skip($initialEvents)->pluck('event_type')->all();
        $expectedEvents = match ($firstOperation) {
            'create' => [CompanyAccessEventType::InvitationCreated],
            'cancel' => [CompanyAccessEventType::InvitationCancelled],
            'resend' => [CompanyAccessEventType::InvitationResent],
            'revoke' => [CompanyAccessEventType::RoleUpdated],
            'hold-company', 'delete-account' => [],
            'delete-role' => [CompanyAccessEventType::InvitationAccepted, CompanyAccessEventType::MemberJoined],
            'rename-role' => [CompanyAccessEventType::RoleUpdated, CompanyAccessEventType::InvitationAccepted, CompanyAccessEventType::MemberJoined],
            'accept', 'accept-claim' => $secondOperation === 'delete-account'
                ? [CompanyAccessEventType::InvitationAccepted, CompanyAccessEventType::MemberJoined, CompanyAccessEventType::MemberLeft]
                : [CompanyAccessEventType::InvitationAccepted, CompanyAccessEventType::MemberJoined],
        };
        expect($events)->toBe($expectedEvents);
        if ($firstOperation === 'rename-role') {
            expect($company->accessEvents()->where('event_type', CompanyAccessEventType::InvitationAccepted)->sole()->metadata['roles'][0]['name'])->toBe('renamed-invitation-role');
        }
        $accepted = in_array($firstOperation, ['accept', 'accept-claim', 'delete-role', 'rename-role'], true);
        $deleted = $firstOperation === 'delete-account' || $secondOperation === 'delete-account';
        expect($company->users()->whereKey($recipient)->exists())->toBe($accepted && ! $deleted);
        expect(DB::table('user_roles')->where(['company_id' => $company->id, 'user_id' => $recipient->id])->count())->toBe($accepted && ! $deleted ? 2 : 0);
        if ($firstOperation === 'accept-claim') {
            expect(fn () => app(AcceptInvitation::class)->handleClaim($payload['digest'], $recipient))->toThrow(DomainException::class);
            expect(DB::table('company_user')->where(['company_id' => $company->id, 'user_id' => $recipient->id])->count())->toBe(1);
            expect(DB::table('user_roles')->where(['company_id' => $company->id, 'user_id' => $recipient->id])->orderBy('role_id')->pluck('role_id')->all())->toBe($roles->modelKeys());
            expect($company->accessEvents()->count())->toBe($initialEvents + 2);
        }
        expect($company->ownerRole()->sole()->users()->pluck('users.id')->all())->toBe([$actor->id]);
        expect($company->invitations()->whereNotNull('active_key')->count())->toBeLessThanOrEqual(1);
        if ($invitation) {
            $invitation = $invitation->fresh();
            expect($invitation->accepted_at !== null)->toBe($accepted);
            expect($invitation->cancelled_at !== null)->toBe($firstOperation === 'cancel');
            if ($accepted || $firstOperation === 'cancel') {
                expect($invitation->active_key)->toBeNull();
            }
        }
        expect(DB::selectOne('SELECT COUNT(*) AS total FROM companies c WHERE NOT EXISTS (SELECT 1 FROM roles r JOIN user_roles ur ON ur.role_id = r.id AND ur.company_id = c.id WHERE r.company_id = c.id AND r.name = ? AND r.is_system = 1)', ['owner'])->total)->toBe(0);
        expect(DB::selectOne('SELECT COUNT(*) AS total FROM user_roles ur LEFT JOIN company_user cu ON cu.user_id = ur.user_id AND cu.company_id = ur.company_id JOIN roles r ON r.id = ur.role_id WHERE cu.user_id IS NULL OR r.company_id <> ur.company_id')->total)->toBe(0);
        expect(DB::selectOne('SELECT COUNT(*) AS total FROM company_invitation_roles ir JOIN roles r ON r.id = ir.role_id JOIN company_invitations i ON i.id = ir.invitation_id WHERE ir.company_id <> r.company_id OR ir.company_id <> i.company_id')->total)->toBe(0);
        expect(DB::selectOne('SELECT COUNT(*) AS total FROM (SELECT active_key FROM company_invitations WHERE active_key IS NOT NULL GROUP BY active_key HAVING COUNT(*) > 1) duplicates')->total)->toBe(0);
        fwrite(STDOUT, json_encode(['scenario' => $firstOperation.' / '.$secondOperation, 'primary_key_wait' => $lockTable, 'first' => $decode($first)['status'], 'second' => $decode($second)['status'], 'events' => array_map(fn ($event) => $event->value, $events)], JSON_THROW_ON_ERROR)."\n");
    } finally {
        $release->close();
        $first->stop();
        $second->stop();
    }
})->with([
    'duplicate creates' => ['create', 'create', 'companies', 'succeeded', 'rejected'],
    'accept before cancel' => ['accept', 'cancel', 'companies', 'succeeded', 'rejected'],
    'cancel before accept' => ['cancel', 'accept', 'companies', 'succeeded', 'rejected'],
    'accept before resend' => ['accept', 'resend', 'companies', 'succeeded', 'rejected'],
    'resend before accept' => ['resend', 'accept', 'companies', 'succeeded', 'rejected'],
    'two accepts' => ['accept', 'accept', 'users', 'succeeded', 'succeeded'],
    'two digest claims reject replay' => ['accept-claim', 'accept-claim', 'users', 'succeeded', 'rejected'],
    'role rename before accept records current history' => ['rename-role', 'accept', 'companies', 'succeeded', 'succeeded'],
    'role deletion rejected before accept' => ['delete-role', 'accept', 'companies', 'rejected', 'succeeded'],
    'role deletion versus accept' => ['accept', 'delete-role', 'companies', 'succeeded', 'rejected'],
    'accept before account deletion' => ['accept', 'delete-account', 'users', 'succeeded', 'succeeded'],
    'account deletion before accept' => ['delete-account', 'accept', 'users', 'succeeded', 'rejected'],
    'stale create authority' => ['revoke', 'create', 'companies', 'succeeded', 'rejected'],
    'stale resend authority' => ['revoke', 'resend', 'companies', 'succeeded', 'rejected'],
    'stale cancel authority' => ['revoke', 'cancel', 'companies', 'succeeded', 'rejected'],
    'expiry after waiting' => ['hold-company', 'accept-boundary', 'companies', 'succeeded', 'rejected'],
]);

it('preserves UTC invitation and audit dates across MySQL session timezones', function () {
    if (DB::connection()->getDriverName() === 'sqlite') {
        $this->markTestSkipped('Requires MySQL timezone conversion semantics.');
    }
    expect(DB::selectOne('SELECT DATABASE() AS name')->name)->toBe('goerion_phase2b1_concurrency_test');
    $originalTimezone = DB::selectOne('SELECT @@session.time_zone AS zone')->zone;
    $this->travelTo(CarbonImmutable::parse('2026-09-12 12:00:00', 'UTC'));
    try {
        DB::statement("SET time_zone = '+03:00'");
        $actor = UserFactory::new()->create();
        $company = app(CreateCompany::class)->handle(['company_name' => 'UTC Invitations'], $actor);
        $role = RoleFactory::new()->create(['company_id' => $company->id]);
        $recipient = UserFactory::new()->create();
        $delivery = app(CreateInvitation::class)->handle($company, $actor, $recipient->email, [$role->id]);
        DB::statement("SET time_zone = '+00:00'");
        expect($delivery->invitation->fresh()->expires_at->toDateTimeString())->toBe('2026-09-19 12:00:00');
        expect($delivery->invitation->fresh()->created_at->toDateTimeString())->toBe('2026-09-12 12:00:00');
        expect(DB::table('company_invitation_roles')->where('invitation_id', $delivery->invitation->id)->sole()->created_at)->toBe('2026-09-12 12:00:00');
        expect($company->accessEvents()->sole()->created_at->toDateTimeString())->toBe('2026-09-12 12:00:00');
    } finally {
        DB::statement('SET time_zone = ?', [$originalTimezone]);
    }
});
