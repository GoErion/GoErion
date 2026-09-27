<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait RefreshSupplierDatabase
{
    public function guardSupplierDatabase(): void
    {
        SupplierDatabaseGuard::assertRunnable(
            app()->environment(),
            config('database.connections.'.config('database.default'), []),
        );
    }

    protected function setUpRefreshSupplierDatabase(): void
    {
        $this->guardSupplierDatabase();
        DB::purge();
        $this->artisan('migrate', ['--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            $this->guardSupplierDatabase();
            $connection = DB::connection();
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            DB::purge();
        });
    }
}
