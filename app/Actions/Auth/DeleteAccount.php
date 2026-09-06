<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\CompanyAccessEventType;
use App\Models\Auth\User;
use App\Models\Company\Company;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class DeleteAccount
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $companyIds = $user->companies()->orderBy('companies.id')->pluck('companies.id');
            $companies = [];
            foreach ($companyIds as $companyId) {
                $companies[] = Company::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();
            }

            $memberships = [];
            foreach ($companies as $company) {
                if (! DB::table('company_user')->where(['company_id' => $company->id, 'user_id' => $user->id])->lockForUpdate()->first()) {
                    continue;
                }
                $ownerRole = $company->ownerRole()->lockForUpdate()->firstOrFail();
                $ownerIds = DB::table('user_roles')->where(['company_id' => $company->id, 'role_id' => $ownerRole->id])
                    ->lockForUpdate()->pluck('user_id');
                if ($ownerIds->contains($user->id) && $ownerIds->count() <= 1) {
                    throw new DomainException('Your account is the last owner of a company. Add another owner or transfer ownership before deleting your account.');
                }
                $memberships[] = $company;
            }

            foreach ($memberships as $company) {
                $event = $company->accessEvents()->create([
                    'actor_user_id' => $user->id,
                    'subject_user_id' => $user->id,
                    'event_type' => CompanyAccessEventType::MemberLeft,
                    'metadata' => ['reason' => 'account_deleted'],
                ]);
                if (! $event->exists) {
                    throw new RuntimeException('Account departure history could not be recorded.');
                }
            }

            if (! $user->delete()) {
                throw new RuntimeException('Account deletion failed.');
            }
        }, 3);
    }
}
