<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('customer_addresses')->select('customer_id', 'type')->distinct()
                ->orderBy('customer_id')->orderBy('type')->chunk(500, function ($groups): void {
                    foreach ($groups as $group) {
                        $addresses = DB::table('customer_addresses')
                            ->where('customer_id', $group->customer_id)->where('type', $group->type)
                            ->orderBy('id')->get(['id', 'is_default']);
                        $defaults = $addresses->where('is_default', true);
                        if ($defaults->count() === 1) {
                            continue;
                        }

                        $primaryId = ($defaults->first() ?? $addresses->first())->id;
                        DB::table('customer_addresses')->where('customer_id', $group->customer_id)
                            ->where('type', $group->type)->where('id', '<>', $primaryId)
                            ->where('is_default', true)->update(['is_default' => false]);
                        DB::table('customer_addresses')->where('id', $primaryId)->update(['is_default' => true]);
                    }
                });
        }, 2);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** Original ambiguous default selections cannot be reconstructed. */
    }
};
