<?php

declare(strict_types=1);

namespace App\Models\Company;

use App\Enums\InvitationStatus;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

#[Fillable(['company_id', 'email', 'token_hash', 'expires_at', 'accepted_at', 'accepted_by', 'cancelled_at', 'cancelled_by', 'invited_by', 'last_sent_at'])]
#[Hidden(['token_hash', 'active_key'])]
#[RouteKey('uuid')]
class CompanyInvitation extends Model
{
    use HasUuid;

    protected $table = 'company_invitations';

    public function newEloquentBuilder(mixed $query): CompanyInvitationBuilder
    {
        return new CompanyInvitationBuilder($query);
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function activeKey(int $companyId, string $email): string
    {
        return hash('sha256', $companyId.':'.self::normalizeEmail($email));
    }

    public function lifecycle(): InvitationStatus
    {
        return match (true) {
            $this->accepted_at !== null => InvitationStatus::Accepted,
            $this->cancelled_at !== null => InvitationStatus::Cancelled,
            $this->expires_at === null || $this->expires_at->lte(now('UTC')) => InvitationStatus::Expired,
            default => InvitationStatus::Pending,
        };
    }

    public function isPending(): bool
    {
        return $this->lifecycle() === InvitationStatus::Pending;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_invitation_roles', 'invitation_id', 'role_id')
            ->withPivot('company_id')->withTimestamps();
    }

    public function delete(): never
    {
        throw new LogicException('Invitations must be retained for history.');
    }

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'accepted_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime', 'last_sent_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $invitation): void {
            $invitation->email = self::normalizeEmail($invitation->email);
            $invitation->active_key = $invitation->accepted_at || $invitation->cancelled_at || ! $invitation->token_hash
                ? null : self::activeKey((int) $invitation->company_id, $invitation->email);
        });
    }
}
