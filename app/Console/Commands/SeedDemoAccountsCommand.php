<?php

namespace App\Console\Commands;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates (or resets) one dedicated demo account per role for live presentations,
 * without touching any real staff/trainer/member account. Credentials are randomly
 * generated and printed once — never hardcoded, matching AdminUserSeeder.
 */
class SeedDemoAccountsCommand extends Command
{
    protected $signature = 'demo:seed-accounts';

    protected $description = 'Create or reset one demo admin/trainer/member account with fresh random credentials';

    public function handle(): int
    {
        $adminPassword = Str::password(14);
        $adminPin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $trainerPin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $memberPassword = Str::password(14);

        $admin = User::updateOrCreate(
            ['email' => 'demo.yonetici@4dbinicilik.local'],
            [
                'name' => 'Demo Yönetici',
                'role' => 'admin',
                'password' => Hash::make($adminPassword),
                'pin_hash' => Hash::make($adminPin),
                'email_verified_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $trainerUser = User::updateOrCreate(
            ['email' => 'demo.antrenor@4dbinicilik.local'],
            [
                'name' => 'Demo Antrenör',
                'role' => 'trainer',
                'pin_hash' => Hash::make($trainerPin),
                'email_verified_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );
        if (! $trainerUser->hasRole('trainer')) {
            $trainerUser->assignRole('trainer');
        }
        $trainer = Trainer::updateOrCreate(
            ['user_id' => $trainerUser->id],
            ['name' => 'Demo Antrenör', 'title' => 'Demo Eğitmen']
        );

        $member = User::updateOrCreate(
            ['phone' => '05550000001'],
            [
                'name' => 'Demo Üye',
                'role' => 'member',
                'password' => Hash::make($memberPassword),
                'remaining_lessons' => 8,
                'used_lessons' => 0,
                'pending_lessons' => 0,
                'email_verified_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );
        if (! $member->hasRole('member')) {
            $member->assignRole('member');
        }

        $this->info('Demo accounts ready — save these, shown once:');
        $this->line('');
        $this->line("ADMIN   | email: demo.yonetici@4dbinicilik.local | password: {$adminPassword} | pin: {$adminPin}");
        $this->line("TRAINER | select 'Demo Antrenör' (trainer_id={$trainer->id}) | pin: {$trainerPin}");
        $this->line("MEMBER  | phone: 05550000001 | password: {$memberPassword} | remaining_lessons: 8");

        return self::SUCCESS;
    }
}
