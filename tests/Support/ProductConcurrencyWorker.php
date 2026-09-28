<?php

declare(strict_types=1);
use App\Actions\Product\CreateProduct;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

/** Fail before autoload, application bootstrap, or connection resolution. */
fwrite(STDERR, "Product MySQL workers are disabled pending separately approved isolation certification.\n");
exit(2);

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[$script, $companyId, $actorId, $categoryId, $unitId] = $argv;
fwrite(STDOUT, "STARTED\n");
fflush(STDOUT);
try {
    $product = app(CreateProduct::class)->handle(Company::query()->findOrFail($companyId), User::query()->findOrFail($actorId), ['name' => 'Concurrent', 'category_id' => $categoryId, 'unit_id' => $unitId]);
    fwrite(STDOUT, json_encode(['sku' => $product->sku], JSON_THROW_ON_ERROR)."\n");
} finally {
    DB::disconnect();
}
