<?php

declare(strict_types=1);

use App\Enums\SupplierPermission;
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
            foreach (SupplierPermission::cases() as $permission) {
                $id = DB::table('permissions')->where('name', $permission->value)->value('id');
                if ($id === null) {
                    $id = DB::table('permissions')->insertGetId([
                        'uuid' => (string) Str::uuid7(),
                        'name' => $permission->value,
                        'display_name' => $permission->displayName(),
                        'assignable_to_custom_roles' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('permissions')->where('id', $id)->update([
                    'display_name' => $permission->displayName(),
                    'assignable_to_custom_roles' => true,
                    'updated_at' => now(),
                ]);
                DB::table('roles')->whereExists(fn ($query) => $query->selectRaw('1')->from('companies')->whereColumn('companies.id', 'roles.company_id'))->where('name', 'owner')->where('is_system', true)->orderBy('id')->eachById(
                    fn (object $role) => DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $role->id, 'permission_id' => $id,
                    ]),
                );
            }
        }, 2);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** Data grants remain in place because later role edits may depend on them. */
    }
};
