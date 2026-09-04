<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $permissionId = DB::table('permissions')
                ->where('name', 'company.update')
                ->value('id');

            if ($permissionId === null) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'uuid' => (string) Str::uuid7(),
                    'name' => 'company.update',
                    'display_name' => 'Update Company',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('roles')
                ->where('name', 'owner')
                ->whereNotNull('company_id')
                ->orderBy('id')
                ->eachById(function (object $role) use ($permissionId): void {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                    ]);
                });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * This data upgrade is intentionally irreversible. Removing the permission
         * could revoke access granted after deployment or delete shared data.
         */
    }
};
