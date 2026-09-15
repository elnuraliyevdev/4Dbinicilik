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

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('E2ETestSeeder must never run outside local/testing.');
        }

        $admin = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'E2E Admin',
                'role' => 'admin',
                'password' => Hash::make(self::PASSWORD),
                'pin_hash' => Hash::make(self::ADMIN_PIN),
            ]
        );
        $admin->assignRole('admin');

        $trainerUser = User::query()->updateOrCreate(
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

        $member = User::query()->updateOrCreate(
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

        Package::query()->updateOrCreate(
            ['lesson_count' => 4],
            ['price_try' => 7000, 'is_active' => true, 'sort_order' => 1]
        );

        $this->command?->info('E2E fixtures ready: trainer_id='.$trainer->id);
    }
}
