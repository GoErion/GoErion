<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('supplier_addresses')->whereNotIn('type', ['billing', 'shipping', 'other'])->exists()) {
            throw new RuntimeException('Unknown Supplier address type; normalization refused.');
        }
        DB::transaction(function (): void {
            foreach (DB::table('suppliers')->orderBy('id')->pluck('id') as $supplierId) {
                $this->normalize('supplier_contacts', $supplierId, 'is_primary');
                foreach (['billing', 'shipping', 'other'] as $type) {
                    $this->normalize('supplier_addresses', $supplierId, 'is_default', $type);
                }
            }
        });
    }

    private function normalize(string $table, int $supplierId, string $flag, ?string $type = null): void
    {
        $query = DB::table($table)->where('supplier_id', $supplierId)->when($type !== null, fn ($query) => $query->where('type', $type));
        $rows = (clone $query)->orderBy('id')->get(['id', $flag]);
        if ($rows->isEmpty()) {
            return;
        }
        $chosen = ($rows->firstWhere($flag, true) ?? $rows->first())->id;
        (clone $query)->where('id', '<>', $chosen)->where($flag, true)->update([$flag => false]);
        (clone $query)->where('id', $chosen)->update([$flag => true]);
    }

    public function down(): void
    {
        /** Original ambiguous selections cannot be reconstructed; retain normalized state. */
    }
};
