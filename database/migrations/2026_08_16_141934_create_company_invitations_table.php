<?php
declare(strict_types=1);

use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Company\Company;
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
        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Role::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'invited_by')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token')->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['company_id','email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_invitations');
    }
};
