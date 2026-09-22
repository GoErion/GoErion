<?php

declare(strict_types=1);

use App\Actions\Customer\CreateCustomer;
use App\Actions\Customer\SetDefaultCustomerAddress;
use App\Actions\Customer\SetPrimaryCustomerContact;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::selectOne('SELECT DATABASE() AS name')->name !== 'goerion_customer_phase3a_test') {
    throw new RuntimeException('Refusing Customer worker outside its disposable MySQL test database.');
}

[$script, $operation, $companyId, $customerId, $childId, $actorId] = $argv;
fwrite(STDOUT, 'STARTED '.DB::selectOne('SELECT CONNECTION_ID() AS id')->id."\n");
fflush(STDOUT);

$exitCode = 0;
try {
    $company = Company::query()->findOrFail((int) $companyId);
    $customer = $customerId === '0' ? null : $company->customers()->findOrFail((int) $customerId);
    $result = match ($operation) {
        'create' => app(CreateCustomer::class)->handle($company, User::query()->findOrFail((int) $actorId), ['name' => 'Concurrent '.$childId]),
        'primary' => app(SetPrimaryCustomerContact::class)->handle($company, $customer, $customer->contacts()->findOrFail((int) $childId)),
        'default' => app(SetDefaultCustomerAddress::class)->handle($company, $customer, $customer->addresses()->findOrFail((int) $childId)),
        default => throw new InvalidArgumentException('Unknown Customer concurrency operation.'),
    };
    fwrite(STDOUT, json_encode(['status' => 'succeeded', 'code' => $operation === 'create' ? $result->code : null], JSON_THROW_ON_ERROR)."\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
    $exitCode = 2;
} finally {
    DB::disconnect();
}

exit($exitCode);
