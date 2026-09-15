<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seeds one initial super-admin with a randomly generated password and PIN.
     * Never hardcode real/demo credentials here — this is the exact class of bug
     * (a shared, predictable admin secret shipped in code) this rebuild fixes.
     */
    public function run(): void
    {
        if (User::query()->where('role', 'admin')->exists()) {
            $this->command?->info('Admin user already exists, skipping AdminUserSeeder.');

            return;
        }

        $email = 'admin@4dbinicilik.local';
        $password = Str::password(16);
        $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $admin = User::query()->create([
            'name' => 'Kulüp Yöneticisi',
            'email' => $email,
            'password' => Hash::make($password),
            'pin_hash' => Hash::make($pin),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $this->command?->info('Seeded initial admin user — save these, they are shown once:');
        $this->command?->info("  email:    {$email}");
        $this->command?->info("  password: {$password}");
        $this->command?->info("  pin:      {$pin}");
    }
}
