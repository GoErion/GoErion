<?php

declare(strict_types=1);

use App\Actions\Supplier\CreateSupplier;
use App\Actions\Supplier\SetDefaultSupplierAddress;
use App\Actions\Supplier\SetPrimarySupplierContact;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** Reject before bootstrapping Laravel or resolving any database connection. */
fwrite(STDERR, "Supplier MySQL workers are disabled pending separately approved isolation certification.\n");
exit(2);

$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::selectOne('SELECT DATABASE() AS name')->name !== 'goerion_supplier_phase4a_test') {
    throw new RuntimeException('Refusing Supplier worker outside its disposable MySQL test database.');
}

[$script, $operation, $companyId, $supplierId, $childId, $actorId] = $argv;
fwrite(STDOUT, 'STARTED '.DB::selectOne('SELECT CONNECTION_ID() AS id')->id."\n");
fflush(STDOUT);

$exitCode = 0;
try {
    $company = Company::query()->findOrFail((int) $companyId);
    $supplier = $supplierId === '0' ? null : $company->suppliers()->findOrFail((int) $supplierId);
    $result = match ($operation) {
        'create' => app(CreateSupplier::class)->handle($company, User::query()->findOrFail((int) $actorId), ['name' => 'Concurrent '.$childId]),
        'primary' => app(SetPrimarySupplierContact::class)->handle($company, $supplier, $supplier->contacts()->findOrFail((int) $childId), User::query()->findOrFail((int) $actorId)),
        'default' => app(SetDefaultSupplierAddress::class)->handle($company, $supplier, $supplier->addresses()->findOrFail((int) $childId), User::query()->findOrFail((int) $actorId)),
        default => throw new InvalidArgumentException('Unknown Supplier concurrency operation.'),
    };
    fwrite(STDOUT, json_encode(['status' => 'succeeded', 'code' => $operation === 'create' ? $result->code : null], JSON_THROW_ON_ERROR)."\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
    $exitCode = 2;
} finally {
    DB::disconnect();
}

exit($exitCode);
