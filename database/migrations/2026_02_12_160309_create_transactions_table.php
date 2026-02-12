<?php

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('orders');
            $table->morphs('from');
            $table->morphs('to');
            $table->decimal('amount',10,2)->unsigned()->index();
            $table->string('payment_type');
            $table->string('reference')->unique();
            $table->string('status',20)->index();
            $table->timestamps();

            $table->index(['order_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
