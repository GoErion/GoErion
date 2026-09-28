<?php

declare(strict_types=1);

use App\Actions\Product\CreateCategory;
use App\Actions\Product\CreateProduct;
use App\Actions\Product\CreateUnit;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

it('serializes first-use and existing SKU allocation on independent MySQL connections', function (bool $existing) {
    if (config('database.default') === 'sqlite') {
        $this->markTestSkipped('MySQL-only concurrent Product SKU allocation; isolation certification and workers remain disabled.');
    }
    [$owner,$company] = SupplierFixture::owner();
    $category = app(CreateCategory::class)->handle($company, $owner, ['name' => 'Hardware', 'code' => 'HW']);
    $unit = app(CreateUnit::class)->handle($company, $owner, ['name' => 'Each', 'symbol' => 'EA']);
    if ($existing) {
        app(CreateProduct::class)->handle($company, $owner, ['name' => 'Primer', 'category_id' => $category->id, 'unit_id' => $unit->id]);
    }
    $workers = [];
    try {
        DB::beginTransaction();
        $company->newQuery()->whereKey($company->id)->lockForUpdate()->firstOrFail();
        foreach (range(1, 2) as $i) {
            $p = new Process([PHP_BINARY, base_path('tests/Support/ProductConcurrencyWorker.php'), (string) $company->id, (string) $owner->id, (string) $category->id, (string) $unit->id], base_path());
            $p->setTimeout(15);
            $p->start();
            $workers[] = $p;
        }
        foreach ($workers as $worker) {
            expect($worker->waitUntil(fn ($type, $output) => str_contains($output, 'STARTED')))->toBeTrue()->and($worker->isRunning())->toBeTrue();
        }
        DB::commit();
        $skus = [];
        foreach ($workers as $worker) {
            $worker->wait();
            expect($worker->getExitCode())->toBe(0)->and($worker->getErrorOutput())->toBe('');
            $lines = explode("\n", trim($worker->getOutput()));
            $skus[] = json_decode(end($lines), true, flags: JSON_THROW_ON_ERROR)['sku'];
        }
        expect(array_unique($skus))->toHaveCount(2)->and($company->products()->count())->toBe($existing ? 3 : 2)->and($company->numberSequences()->where('type', 'product')->sole()->next_number)->toBe($existing ? 4 : 3);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop();
            }
        }DB::disconnect();
    }
})->with([false, true]);
