<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('company_invitations')->whereNotIn('status', ['pending', 'accepted', 'cancelled', 'expired'])->exists()) {
            throw new RuntimeException('Cannot convert unrecognized legacy invitation lifecycle.');
        }
        if (DB::table('company_invitations')->where('status', '!=', 'accepted')->whereNotNull('accepted_at')->exists()) {
            throw new RuntimeException('Cannot convert contradictory legacy invitation acceptance history.');
        }
        Schema::table('company_invitations', function (Blueprint $table): void {
            $table->dropForeign(['invited_by']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['company_id']);
            $table->unsignedBigInteger('invited_by')->nullable()->change();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('active_key', 64)->nullable()->unique();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_sent_at')->nullable();
            $table->unique(['id', 'company_id'], 'invitations_id_company_unique');
            $table->index(['company_id', 'expires_at']);
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
        });
        Schema::create('company_invitation_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('invitation_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
            $table->primary(['invitation_id', 'role_id']);
            $table->foreign(['invitation_id', 'company_id'], 'invitation_roles_invitation_company_fk')
                ->references(['id', 'company_id'])->on('company_invitations')->cascadeOnDelete();
            $table->foreign(['role_id', 'company_id'], 'invitation_roles_role_company_fk')
                ->references(['id', 'company_id'])->on('roles')->cascadeOnDelete();
        });

        /**
         * Legacy links were never safely routable. No legacy token is read or hashed.
         * All non-accepted records are invalidated, including duplicate normalized emails.
         * Missing acceptance dates use updated_at, then created_at, then migration time.
         * Cancellation and missing expiry dates use migration time; expired dates are capped
         * at migration time. Actor identity and delivery dates cannot safely be inferred.
         */
        $convertedAt = now('UTC');
        DB::table('company_invitations')->select(['id', 'company_id', 'role_id', 'email', 'status', 'expires_at', 'accepted_at', 'created_at', 'updated_at'])
            ->orderBy('id')->chunkById(200, function ($invitations) use ($convertedAt): void {
                foreach ($invitations as $invitation) {
                    $accepted = $invitation->status === 'accepted';
                    $expired = $invitation->status === 'expired';
                    DB::table('company_invitations')->where('id', $invitation->id)->update([
                        'email' => mb_strtolower(trim($invitation->email)),
                        'accepted_at' => $accepted ? ($invitation->accepted_at ?? $invitation->updated_at ?? $invitation->created_at ?? $convertedAt) : null,
                        'cancelled_at' => ! $accepted && ! $expired ? $convertedAt : null,
                        'expires_at' => $expired ? min($invitation->expires_at ?? (string) $convertedAt, (string) $convertedAt) : ($invitation->expires_at ?? $convertedAt),
                    ]);
                    if (DB::table('roles')->where(['id' => $invitation->role_id, 'company_id' => $invitation->company_id])->exists()) {
                        DB::table('company_invitation_roles')->insert([
                            'invitation_id' => $invitation->id, 'role_id' => $invitation->role_id,
                            'company_id' => $invitation->company_id, 'created_at' => $convertedAt, 'updated_at' => $convertedAt,
                        ]);
                    }
                }
            });
        Schema::table('company_invitations', function (Blueprint $table): void {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'status', 'role_id']);
            $table->timestamp('expires_at')->nullable(false)->change();
        });
    }

    /**
     * Plaintext tokens cannot be recovered securely. Reversing would lose multi-role,
     * actor and lifecycle history and resurrect unsafe APIs. Use a forward fix instead.
     */
    public function down(): void
    {
        throw new RuntimeException('Security migration is irreversible: legacy tokens and single-role state cannot be safely restored.');
    }
};
