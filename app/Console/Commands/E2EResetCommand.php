<?php

namespace App\Console\Commands;

use App\Models\CreditTransaction;
use App\Models\PackagePurchaseRequest;
use App\Models\Reservation;
use App\Models\Trainer;
use App\Models\TrainerFeedbackNote;
use App\Models\TrainerSlot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Resets just the E2E fixtures to a known-clean state (re-seed + wipe any
 * reservations/slots/requests those specific fixture users touched) without
 * a full migrate:fresh — safe to run before every Playwright suite run.
 * Refuses to run outside local/testing, same guard as E2ETestSeeder.
 */
class E2EResetCommand extends Command
{
    protected $signature = 'e2e:reset';

    protected $description = 'Reset E2E test fixtures to a clean, known state (local/testing only)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('e2e:reset must never run outside local/testing.');

            return self::FAILURE;
        }

        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\E2ETestSeeder', '--force' => true]);

        $member = User::query()->where('name', 'E2E Member')->first();
        $trainerUser = User::query()->where('name', 'E2E Trainer')->first();
        $trainer = $trainerUser ? Trainer::query()->where('user_id', $trainerUser->id)->first() : null;

        if ($member) {
            $reservationIds = Reservation::query()->where('user_id', $member->id)->pluck('id');
            CreditTransaction::query()->whereIn('reference_id', $reservationIds)
                ->where('reference_type', Reservation::class)->delete();
            TrainerSlot::query()->whereIn('reservation_id', $reservationIds)
                ->update(['status' => 'available', 'reservation_id' => null]);
            Reservation::query()->where('user_id', $member->id)->delete();
            PackagePurchaseRequest::query()->where('user_id', $member->id)->delete();

            $member->update([
                'total_lessons' => 10, 'used_lessons' => 0,
                'remaining_lessons' => 10, 'pending_lessons' => 0,
                'active_package_id' => null,
            ]);
        }

        if ($trainer) {
            TrainerFeedbackNote::query()->where('trainer_id', $trainer->id)->delete();
            TrainerSlot::query()->where('trainer_id', $trainer->id)->delete();
        }

        // Belt-and-suspenders: any slot stuck 'busy' with no reservation attached
        // (e.g. from an interrupted prior run) blocks the "first available slot"
        // pattern our specs use — safe to clear in local/testing only.
        TrainerSlot::query()->whereNull('reservation_id')->where('status', 'busy')
            ->update(['status' => 'available']);

        $this->info('E2E fixtures reset.');

        return self::SUCCESS;
    }
}
