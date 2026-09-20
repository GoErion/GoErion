<?php

declare(strict_types=1);

use App\Actions\Auth\DeleteAccount;
use App\Actions\Company\AcceptInvitation;
use App\Actions\Company\CancelInvitation;
use App\Actions\Company\CreateInvitation;
use App\Actions\Company\DeleteRole;
use App\Actions\Company\ResendInvitation;
use App\Actions\Company\UpdateRole;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql' || DB::selectOne('SELECT DATABASE() AS name')->name !== 'goerion_phase2b1_concurrency_test') {
    throw new RuntimeException('Refusing to run invitation worker outside the isolated MySQL test database.');
}
$operation = $argv[1];
$input = json_decode((string) fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
$hold = $input['hold'] ?? false;
$connectionId = DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
fwrite(STDOUT, "STARTED {$operation} {$connectionId}\n");
fflush(STDOUT);
try {
    $company = Company::findOrFail($input['company']);
    $actor = User::findOrFail($input['actor']);
    $recipient = User::findOrFail($input['recipient']);
    $invitation = isset($input['invitation']) ? CompanyInvitation::findOrFail($input['invitation']) : null;
    if (in_array($operation, ['create', 'resend', 'cancel'], true)) {
        if (! $actor->can('inviteMembers', $company)) {
            throw new RuntimeException('Expected initial invitation authority.');
        }
        $actor->load('roles.permissions');
        fwrite(STDOUT, "AUTHORIZED {$operation}\n");
        fflush(STDOUT);
    }
    if ($operation === 'accept-boundary') {
        Date::setTestNow(Date::parse($input['expiry'])->subSecond());
        DB::listen(function ($query) use ($input): void {
            if (str_contains($query->sql, '`companies`') && str_contains($query->sql, 'for update')) {
                Date::setTestNow(Date::parse($input['expiry']));
            }
        });
    }
    if ($hold) {
        DB::beginTransaction();
    }
    $status = 'succeeded';
    $exceptionClass = null;
    try {
        match ($operation) {
            'create' => app(CreateInvitation::class)->handle($company, $actor, $recipient->email, $input['roles']),
            'resend' => app(ResendInvitation::class)->handle($company, $invitation, $actor),
            'cancel' => app(CancelInvitation::class)->handle($company, $invitation, $actor),
            'accept', 'accept-boundary' => app(AcceptInvitation::class)->handle($input['token'], $recipient),
            'accept-claim' => app(AcceptInvitation::class)->handleClaim($input['digest'], $recipient),
            'rename-role' => app(UpdateRole::class)->handle($company, Role::findOrFail($input['roles'][0]), $actor, 'renamed-invitation-role', null, []),
            'delete-role' => app(DeleteRole::class)->handle($company, Role::findOrFail($input['roles'][0]), $actor),
            'delete-account' => app(DeleteAccount::class)->handle($recipient),
            'revoke' => app(UpdateRole::class)->handle($company, Role::findOrFail($input['manager_role']), $actor, 'invitation-manager', null, []),
            'hold-company' => Company::whereKey($company)->lockForUpdate()->firstOrFail(),
            default => throw new RuntimeException('Unknown invitation operation.'),
        };
    } catch (DomainException|AuthorizationException|ModelNotFoundException $exception) {
        $status = 'rejected';
        $exceptionClass = $exception::class;
    }
    if ($hold) {
        fwrite(STDOUT, "APPLIED {$operation}\n");
        fflush(STDOUT);
        if (trim((string) fgets(STDIN)) !== 'COMMIT') {
            throw new RuntimeException('Missing transaction release signal.');
        }
        DB::commit();
    }
    fwrite(STDOUT, json_encode(['operation' => $operation, 'status' => $status, 'exception' => $exceptionClass], JSON_THROW_ON_ERROR)."\n");
} catch (Throwable $exception) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    fwrite(STDERR, json_encode(['operation' => $operation, 'exception' => $exception::class], JSON_THROW_ON_ERROR)."\n");
    exit(2);
}
