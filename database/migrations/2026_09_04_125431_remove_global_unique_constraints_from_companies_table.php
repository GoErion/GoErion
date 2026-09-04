<?php

declare(strict_types=1);

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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique('companies_company_name_unique');
            $table->dropUnique('companies_legal_name_unique');
            $table->dropUnique('companies_registration_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unique('company_name');
            $table->unique('legal_name');
            $table->unique('registration_number');
        });
    }
};
