<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait RefreshProductDatabase
{
    protected function setUpRefreshProductDatabase(): void
    {
        SupplierDatabaseGuard::assertRunnable(app()->environment(), config('database.connections.'.config('database.default'), []));
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException('Product tests require explicit in-memory SQLite.');
        }
        DB::purge();
        $this->artisan('migrate', ['--no-interaction' => true, '--force' => true])->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            SupplierDatabaseGuard::assertRunnable(app()->environment(), config('database.connections.'.config('database.default'), []));
            DB::purge();
        });
    }
}
