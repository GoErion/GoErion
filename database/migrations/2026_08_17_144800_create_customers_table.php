<?php
declare(strict_types=1);

use App\Models\Company\Company;
use App\Models\Customer\CustomerGroup;
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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CustomerGroup::class)->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('credit_limit',19,4)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id','code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
