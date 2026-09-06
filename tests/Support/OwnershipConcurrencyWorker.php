<?php

declare(strict_types=1);

use App\Actions\Auth\DeleteAccount;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\DemoteCompanyOwner;
use App\Actions\Company\LeaveCompany;
use App\Actions\Company\PromoteCompanyOwner;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

[$script, $operation, $companyId, $memberId, $actorId] = $argv;

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql' || DB::selectOne('SELECT DATABASE() AS name')->name !== 'goerion_phase2b1_concurrency_test') {
    throw new RuntimeException('Refusing to run ownership worker outside the isolated MySQL test database.');
}
$connectionId = DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
fwrite(STDOUT, "STARTED {$operation} {$connectionId}\n");
fflush(STDOUT);

try {
    $company = (int) $companyId === 0 ? null : Company::query()->findOrFail((int) $companyId);
    $member = User::query()->findOrFail((int) $memberId);
    $actor = User::query()->findOrFail((int) $actorId);

    if ($operation === 'promote') {
        if (! $actor->can('manageOwnership', $company)) {
            throw new RuntimeException('The promotion actor was not initially authorized.');
        }
        fwrite(STDOUT, "AUTHORIZED promote\n");
        fflush(STDOUT);
    }
    $hold = ($argv[5] ?? '') === 'hold';
    if ($hold) {
        DB::beginTransaction();
    }
    match ($operation) {
        'create-company' => app(CreateCompany::class)->handle(['company_name' => 'Concurrent Account '.$memberId], $member),
        'promote' => app(PromoteCompanyOwner::class)->handle($company, $member, $actor),
        'demote' => app(DemoteCompanyOwner::class)->handle($company, $member, $actor),
        'delete-account' => app(DeleteAccount::class)->handle($member),
        'leave' => app(LeaveCompany::class)->handle($company, $member),
        default => throw new InvalidArgumentException('Unknown ownership operation.'),
    };

    if ($hold) {
        fwrite(STDOUT, "APPLIED {$operation}\n");
        fflush(STDOUT);
        if (trim((string) fgets(STDIN)) !== 'COMMIT') {
            throw new RuntimeException('Missing transaction release signal.');
        }
        DB::commit();
    }
    fwrite(STDOUT, json_encode(['operation' => $operation, 'status' => 'succeeded'], JSON_THROW_ON_ERROR)."\n");
} catch (DomainException|AuthorizationException|ModelNotFoundException $exception) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    fwrite(STDOUT, json_encode([
        'operation' => $operation,
        'status' => 'rejected',
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR)."\n");
} catch (Throwable $exception) {
    fwrite(STDERR, json_encode([
        'operation' => $operation,
        'status' => 'unexpected_failure',
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR)."\n");
    exit(2);
}
