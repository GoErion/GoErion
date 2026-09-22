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
        Schema::table('customers', fn (Blueprint $table) => $table->string('tax_pin', 50)->nullable()->after('phone'));
        Schema::table('customer_contacts', fn (Blueprint $table) => $table->boolean('is_primary')->default(false)->after('position'));

        DB::table('customer_contacts')->select('customer_id')->distinct()->orderBy('customer_id')->chunk(500, function ($rows): void {
            foreach ($rows as $row) {
                $oldest = DB::table('customer_contacts')->where('customer_id', $row->customer_id)->orderBy('id')->value('id');
                DB::table('customer_contacts')->where('id', $oldest)->update(['is_primary' => true]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_contacts', fn (Blueprint $table) => $table->dropColumn('is_primary'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('tax_pin'));
    }
};
