<?php

declare(strict_types=1);

use App\Models\Company\CompanyInvitation;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('converts nonempty legacy history without retaining tokens or activating duplicate links', function () {
    $original = DB::getDefaultConnection();
    config()->set('database.connections.invitation_legacy', ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]);
    DB::setDefaultConnection('invitation_legacy');
    try {
        $migrations = glob(database_path('migrations/*.php'));
        sort($migrations);
        $forwardPath = database_path('migrations/2026_09_11_185020_strengthen_company_invitations_for_phase_2b2.php');
        foreach ($migrations as $path) {
            if ($path === $forwardPath) {
                break;
            }
            (require $path)->up();
        }
        $company = CompanyFactory::new()->create();
        $actor = UserFactory::new()->create();
        $role = RoleFactory::new()->create(['company_id' => $company->id]);
        $foreign = RoleFactory::new()->create();
        $ids = [];
        foreach (['pending', 'pending', 'accepted', 'cancelled', 'expired', 'pending'] as $index => $status) {
            $ids[] = DB::table('company_invitations')->insertGetId([
                'uuid' => (string) Str::uuid(), 'company_id' => $company->id,
                'role_id' => $index === 5 ? $foreign->id : $role->id, 'invited_by' => $actor->id,
                'email' => $index === 0 ? ' LEGACY@example.test ' : 'legacy@example.test',
                'token' => 'obsolete-fixture-'.$index, 'status' => $status,
                'expires_at' => null, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-02 00:00:00',
            ]);
        }
        $migration = require $forwardPath;
        DB::table('company_invitations')->where('id', $ids[0])->update(['status' => 'unknown']);
        expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'unrecognized legacy invitation lifecycle');
        expect(Schema::hasColumn('company_invitations', 'token_hash'))->toBeFalse();
        DB::table('company_invitations')->where('id', $ids[0])->update(['status' => 'pending', 'accepted_at' => '2026-01-02 00:00:00']);
        expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'contradictory legacy invitation acceptance history');
        expect(Schema::hasColumn('company_invitations', 'token_hash'))->toBeFalse();
        DB::table('company_invitations')->where('id', $ids[0])->update(['accepted_at' => null]);
        $migration->up();
        (require database_path('migrations/2026_09_12_141454_preserve_utc_company_invitation_dates.php'))->up();

        expect(DB::table('company_invitations')->count())->toBe(6);
        expect(Schema::hasColumn('company_invitations', 'token'))->toBeFalse();
        expect(Schema::hasColumn('company_invitations', 'status'))->toBeFalse();
        expect(Schema::hasColumn('company_invitations', 'role_id'))->toBeFalse();
        expect(DB::table('company_invitations')->whereNotNull('token_hash')->count())->toBe(0);
        expect(DB::table('company_invitations')->whereNotNull('active_key')->count())->toBe(0);
        expect(DB::table('company_invitations')->where('email', 'legacy@example.test')->count())->toBe(6);
        expect(DB::table('company_invitations')->whereNotNull('cancelled_at')->count())->toBe(4);
        expect(DB::table('company_invitations')->whereNotNull('accepted_at')->sole()->accepted_at)->toBe('2026-01-02 00:00:00');
        expect(DB::table('company_invitations')->whereNull('expires_at')->count())->toBe(0);
        expect(DB::table('company_invitation_roles')->count())->toBe(5);
        expect(CompanyInvitation::findOrFail($ids[4])->isPending())->toBeFalse();
        DB::table('users')->where('id', $actor->id)->delete();
        expect(DB::table('company_invitations')->whereNull('invited_by')->count())->toBe(6);
        DB::table('roles')->where('id', $role->id)->delete();
        expect(DB::table('company_invitations')->count())->toBe(6);
        expect(DB::table('company_invitation_roles')->count())->toBe(0);
        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Security migration is irreversible');
    } finally {
        DB::setDefaultConnection($original);
        DB::purge('invitation_legacy');
    }
});
