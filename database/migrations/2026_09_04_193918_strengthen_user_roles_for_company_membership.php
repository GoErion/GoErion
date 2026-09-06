<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $invalidAssignment = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('company_user', function ($join): void {
                $join->on('company_user.user_id', '=', 'user_roles.user_id')
                    ->on('company_user.company_id', '=', 'roles.company_id');
            })->whereNull('company_user.user_id')->exists();

        if ($invalidAssignment) {
            throw new RuntimeException('Cannot strengthen user_roles: an assigned role belongs to a company the user has not joined.');
        }

        Schema::table('roles', fn (Blueprint $table) => $table->unique(['id', 'company_id'], 'roles_id_company_id_unique'));
        Schema::table('user_roles', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('role_id');
            $table->foreignId('assigned_by')->nullable()->after('company_id');
            $table->timestamps();
        });
        DB::table('user_roles')->update([
            'company_id' => DB::raw('(select company_id from roles where roles.id = user_roles.role_id)'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::table('user_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);
            $table->index(['company_id', 'user_id']);
            $table->foreign(['user_id', 'company_id'])->references(['user_id', 'company_id'])->on('company_user')->cascadeOnDelete();
            $table->foreign(['role_id', 'company_id'])->references(['id', 'company_id'])->on('roles')->restrictOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Rollback permanently discards company-aware assignment metadata: company_id, assigned_by, and timestamps.
     */
    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign(['user_id', 'company_id']);
            $table->dropForeign(['role_id', 'company_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropIndex(['company_id', 'user_id']);
            $table->dropColumn(['company_id', 'assigned_by', 'created_at', 'updated_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });
        Schema::table('roles', fn (Blueprint $table) => $table->dropUnique('roles_id_company_id_unique'));
    }
};
