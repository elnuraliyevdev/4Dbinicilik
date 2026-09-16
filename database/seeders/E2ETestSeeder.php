<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic fixtures for Playwright E2E runs — NEVER run against a real
 * environment. Credentials here are intentionally fixed/known (unlike
 * AdminUserSeeder's randomly-generated production admin) because tests need
 * to log in reliably; that's only safe because this seeder refuses to run
 * outside local/testing.
 */
class E2ETestSeeder extends Seeder
{
    public const MEMBER_PHONE = '+90 500 000 09 09';

    public const TRAINER_PIN = '4242';

    public const ADMIN_EMAIL = 'e2e-admin@test.local';

    public const ADMIN_PIN = '4242';

    public const PASSWORD = 'E2eTestPass123!';

    public const INACTIVE_TRAINER_PIN = '4242';

    public const UNCLAIMED_MEMBER_PHONE = '+90 500 000 09 10';

    public const LOCKOUT_TEST_MEMBER_PHONE = '+90 500 000 09 11';

    public const PACKAGE_TEST_MEMBER_PHONE = '+90 500 000 09 12';

    public const CONCURRENCY_TEST_MEMBER_PHONE = '+90 500 000 09 13';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('E2ETestSeeder must never run outside local/testing.');
        }

        $admin = $this->upsertUser(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'E2E Admin',
                'role' => 'admin',
                'password' => Hash::make(self::PASSWORD),
                'pin_hash' => Hash::make(self::ADMIN_PIN),
            ]
        );
        $admin->assignRole('admin');

        $trainerUser = $this->upsertUser(
            ['email' => 'e2e-trainer@test.local'],
            [
                'name' => 'E2E Trainer',
                'role' => 'trainer',
                'pin_hash' => Hash::make(self::TRAINER_PIN),
            ]
        );
        $trainerUser->assignRole('trainer');
        $trainer = Trainer::query()->updateOrCreate(
            ['user_id' => $trainerUser->id],
            ['title' => 'E2E Test Trainer', 'avatar_letter' => 'E', 'is_active' => true]
        );

        // Deactivated trainer — for the login-should-be-rejected regression test.
        $inactiveTrainerUser = $this->upsertUser(
            ['email' => 'e2e-inactive-trainer@test.local'],
            [
                'name' => 'E2E Inactive Trainer',
                'role' => 'trainer',
                'pin_hash' => Hash::make(self::INACTIVE_TRAINER_PIN),
            ]
        );
        $inactiveTrainerUser->assignRole('trainer');
        $inactiveTrainer = Trainer::query()->updateOrCreate(
            ['user_id' => $inactiveTrainerUser->id],
            ['title' => 'E2E Inactive Trainer', 'avatar_letter' => 'I', 'is_active' => false]
        );

        $member = $this->upsertUser(
            ['phone' => self::MEMBER_PHONE],
            [
                'name' => 'E2E Member',
                'role' => 'member',
                'password' => Hash::make(self::PASSWORD),
                'total_lessons' => 10,
                'used_lessons' => 0,
                'remaining_lessons' => 10,
                'pending_lessons' => 0,
            ]
        );
        $member->assignRole('member');

        // password=null — for the claim-account (first-time password setup) flow.
        $unclaimed = $this->upsertUser(
            ['phone' => self::UNCLAIMED_MEMBER_PHONE],
            [
                'name' => 'E2E Unclaimed Member',
                'role' => 'member',
                'password' => null,
                'remaining_lessons' => 0,
            ]
        );
        $unclaimed->assignRole('member');

        // Its own dedicated identifier — admin.spec.ts's package-approval test
        // mutates this account's remaining_lessons (a real credit grant), which
        // must never be observable from member.spec.ts's tests asserting an
        // exact starting balance against the shared MEMBER_PHONE account.
        $packageTestMember = $this->upsertUser(
            ['phone' => self::PACKAGE_TEST_MEMBER_PHONE],
            [
                'name' => 'E2E Package Test Member',
                'role' => 'member',
                'password' => Hash::make(self::PASSWORD),
                'remaining_lessons' => 0,
            ]
        );
        $packageTestMember->assignRole('member');

        // Its own dedicated identifier too — shared credit-mutating fixtures
        // are exactly the class of bug this session kept re-discovering
        // (auth.spec.ts's lockout test, admin.spec.ts's package approval,
        // admin-members.spec.ts's credit grant all needed their own). The
        // concurrency test grants and spends its own credit mid-run, so it
        // gets a clean identity nothing else touches.
        $concurrencyTestMember = $this->upsertUser(
            ['phone' => self::CONCURRENCY_TEST_MEMBER_PHONE],
            [
                'name' => 'E2E Concurrency Test Member',
                'role' => 'member',
                'password' => Hash::make(self::PASSWORD),
                'remaining_lessons' => 0,
            ]
        );
        $concurrencyTestMember->assignRole('member');

        // Its own dedicated identifier, never shared with MEMBER_PHONE — the
        // lockout regression test deliberately drives this account's rate
        // limiter into a 60s-locked state and (by design) never successfully
        // logs in afterward to clear it, so no *other* test's login against
        // the same identifier from the same IP can be collaterally blocked
        // by it within that window.
        $lockoutTestMember = $this->upsertUser(
            ['phone' => self::LOCKOUT_TEST_MEMBER_PHONE],
            [
                'name' => 'E2E Lockout Test Member',
                'role' => 'member',
                'password' => Hash::make(self::PASSWORD),
                'remaining_lessons' => 0,
            ]
        );
        $lockoutTestMember->assignRole('member');

        Package::query()->updateOrCreate(
            ['lesson_count' => 4],
            ['price_try' => 7000, 'is_active' => true, 'sort_order' => 1]
        );

        $this->command?->info('E2E fixtures ready: trainer_id='.$trainer->id.' inactive_trainer_id='.$inactiveTrainer->id.' unclaimed_member_id='.$unclaimed->id);
    }

    /**
     * Plain updateOrCreate() can't see a soft-deleted row (a prior
     * E2EResetCommand run, say), so it would crash re-seeding on the unique
     * email/phone constraint instead of restoring the fixture. Also resets
     * failed_login_attempts/locked_until — now that login lockout is
     * actually enforced (not just written), a fixture left locked by an
     * earlier failed-login test would otherwise stay locked across resets.
     */
    private function upsertUser(array $attributes, array $values): User
    {
        $user = User::withTrashed()->firstOrNew($attributes);
        $user->fill($values);
        $user->deleted_at = null;
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        return $user;
    }
}
