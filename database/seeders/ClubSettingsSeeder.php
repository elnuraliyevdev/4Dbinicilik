<?php

namespace Database\Seeders;

use App\Models\ClubSetting;
use Illuminate\Database\Seeder;

class ClubSettingsSeeder extends Seeder
{
    public function run(): void
    {
        ClubSetting::query()->updateOrCreate(['key' => 'monday_closed'], ['value' => '1']);
        ClubSetting::query()->updateOrCreate(['key' => 'cancellation_window_hours'], ['value' => '2']);
        // Placeholder — the real club WhatsApp number should be set by an admin in Settings,
        // not hardcoded here (the old prototype hardcoded CLUB_WHATSAPP_NUMBER in app.js).
        ClubSetting::query()->updateOrCreate(['key' => 'club_whatsapp_number'], ['value' => '905551234567']);
    }
}
