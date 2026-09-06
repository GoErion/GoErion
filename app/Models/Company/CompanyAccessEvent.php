<?php

declare(strict_types=1);

namespace App\Models\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Traits\HasUuid;
use Database\Factories\Company\CompanyAccessEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['actor_user_id', 'subject_user_id', 'role_id', 'invitation_id', 'event_type', 'metadata'])]
class CompanyAccessEvent extends Model
{
    /** @use HasFactory<CompanyAccessEventFactory> */
    use HasFactory;

    use HasUuid;

    public const UPDATED_AT = null;

    protected $table = 'company_access_events';

    public function newEloquentBuilder(mixed $query): CompanyAccessEventBuilder
    {
        return new CompanyAccessEventBuilder($query);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(CompanyInvitation::class);
    }

    protected function casts(): array
    {
        return ['event_type' => CompanyAccessEventType::class, 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Company access events are append-only.'));
        static::deleting(fn () => throw new LogicException('Company access events are append-only.'));
    }
}
