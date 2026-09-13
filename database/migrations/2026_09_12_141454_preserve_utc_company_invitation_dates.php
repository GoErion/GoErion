<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application date strings are UTC. Preserve their existing session-visible values
     * while removing MySQL TIMESTAMP conversion, which depends on server timezone.
     * Run using the same connection timezone as the application that wrote the rows.
     */
    public function up(): void
    {
        Schema::table('company_invitations', function (Blueprint $table): void {
            $table->dateTime('expires_at')->change();
            foreach (['accepted_at', 'cancelled_at', 'last_sent_at', 'created_at', 'updated_at'] as $column) {
                $table->dateTime($column)->nullable()->change();
            }
        });
        Schema::table('company_invitation_roles', function (Blueprint $table): void {
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });
        Schema::table('company_access_events', function (Blueprint $table): void {
            $table->dateTime('created_at')->change();
        });
    }

    /** Reverting would reintroduce timezone conversion and could shift audit dates. */
    public function down(): void
    {
        throw new RuntimeException('UTC invitation and access-history dates require a forward fix.');
    }
};
