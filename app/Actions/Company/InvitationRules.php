<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Company\CompanyInvitation;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InvitationRules
{
    public static function unavailable(): never
    {
        throw new DomainException('This invitation is not available.');
    }

    /** @param array<int, int> $roleIds
     * @return Collection<int, Role>
     */
    public function roles(Company $company, array $roleIds): Collection
    {
        foreach ($roleIds as $roleId) {
            if (! is_int($roleId) || $roleId < 1) {
                self::unavailable();
            }
        }
        $roleIds = array_values(array_unique($roleIds));
        $roles = $company->roles()->whereKey($roleIds)->orderBy('id')->lockForUpdate()->get();
        if ($roleIds === [] || $roles->count() !== count($roleIds) || $roles->contains(fn (Role $role): bool => $role->isSystem() || $role->name === 'owner')) {
            self::unavailable();
        }

        return $roles;
    }

    public function rejectMember(Company $company, string $email): void
    {
        if (DB::table('company_user')->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $company->id)->whereRaw('LOWER(TRIM(users.email)) = ?', [$email])->exists()) {
            self::unavailable();
        }
    }

    public function expiresAt(): CarbonImmutable
    {
        $days = config('invitations.lifetime_days');
        if (! is_int($days) || $days < 1) {
            throw new RuntimeException('Invitation lifetime must be a positive number of days.');
        }

        return CarbonImmutable::now('UTC')->addDays($days);
    }

    public function record(CompanyInvitation $invitation, User $actor, CompanyAccessEventType $type): void
    {
        $event = $invitation->company()->firstOrFail()->accessEvents()->create([
            'actor_user_id' => $actor->id,
            'subject_user_id' => $type === CompanyAccessEventType::InvitationAccepted || $type === CompanyAccessEventType::MemberJoined ? $actor->id : null,
            'invitation_id' => $invitation->id,
            'event_type' => $type,
            'metadata' => [
                'invitation_uuid' => $invitation->uuid,
                'reason' => $type->value,
                'roles' => $invitation->roles()->orderBy('roles.id')->lockForUpdate()->get()->map(fn (Role $role): array => ['uuid' => $role->uuid, 'name' => $role->name, 'display_name' => $role->display_name])->all(),
            ],
        ]);
        if (! $event->exists) {
            throw new RuntimeException('Invitation history could not be recorded.');
        }
    }
}
