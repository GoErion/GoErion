<?php
declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\InvitationStatus;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateInvitation
{
    public function handle(array $input, Company $company,User $invitedBy)
    {
        return DB::transaction(function() use($input,$company,$invitedBy)
        {
            // Ensure the role belongs to this company.
            $role = $company->roles()->whereKey($input['role_id'])->firstOrFail();

            // Check whether the email already belongs
            // to a member of this company.
            $user = User::where('email',$input['email'])->first();
            if ($user && $company->users()->whereKey($user->id)->exists())
            {
                throw new \DomainException(
                    'This user is already a member of the company.'
                );
            }

            // Check for an existing pending invitation.
            $invitation = $company->invitations()->where('email',$input['email'])->where('status',InvitationStatus::Pending)->first();

            if ($invitation)
            {
                $invitation->update(
                    [
                        'role_id' => $role->id,
                        'invited_by' => $invitedBy->id,
                        'email' => $input['email'],
                        'token' => (string) Str::uuid(),
                        'status' => InvitationStatus::Pending,
                        'expires_at' => now()->addDays(1),
                    ]);
                    return $invitation->fresh();
            }
            return $company->invitations()->create(
                [
                    'role_id' => $role->id,
                        'invited_by' => $invitedBy->id,
                        'email' => $input['email'],
                        'token' => (string) Str::uuid(),
                        'status' => InvitationStatus::Pending,
                        'expires_at' => now()->addDays(1),
                ]);
        });
    }
}
