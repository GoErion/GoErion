<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', fn (Blueprint $table) => $table->boolean('is_system')->default(false)->after('display_name'));
        }
        if (! Schema::hasColumn('permissions', 'assignable_to_custom_roles')) {
            Schema::table('permissions', fn (Blueprint $table) => $table->boolean('assignable_to_custom_roles')->default(false)->after('display_name'));
        }
        DB::transaction(function (): void {
            DB::table('roles')->where('name', 'owner')->update(['is_system' => true]);
            $permissions = [
                'company.update' => ['Update Company', true],
                'company.members.view' => ['View Company Members', true],
                'company.members.manage' => ['Manage Company Members', true],
                'company.roles.view' => ['View Company Roles', true],
                'company.roles.manage' => ['Manage Company Roles', true],
                'company.invitations.manage' => ['Manage Company Invitations', true],
                'company.ownership.manage' => ['Manage Company Ownership', false],
            ];
            foreach ($permissions as $name => [$displayName, $assignable]) {
                $existing = DB::table('permissions')->where('name', $name)->first();
                $permissionId = $existing?->id ?? DB::table('permissions')->insertGetId([
                    'uuid' => (string) Str::uuid7(), 'name' => $name, 'display_name' => $displayName,
                    'assignable_to_custom_roles' => $assignable, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('permissions')->where('id', $permissionId)->update([
                    'display_name' => $displayName, 'assignable_to_custom_roles' => $assignable, 'updated_at' => now(),
                ]);
                DB::table('roles')->where('name', 'owner')->orderBy('id')->eachById(
                    fn (object $role) => DB::table('role_permissions')->insertOrIgnore(['role_id' => $role->id, 'permission_id' => $permissionId]),
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', fn (Blueprint $table) => $table->dropColumn('assignable_to_custom_roles'));
        Schema::table('roles', fn (Blueprint $table) => $table->dropColumn('is_system'));
    }
};
