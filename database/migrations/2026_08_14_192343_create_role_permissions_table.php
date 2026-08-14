<?php
declare(strict_types=1);

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignIdFor(Role::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Permission::class)->constrained()->cascadeOnDelete();

            $table->primary(['role_id','permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
