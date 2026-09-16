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
use Illuminate\Support\Facades\Cache;

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

        // Each has its own dedicated identifier (see E2ETestSeeder) precisely
        // so no single test's mutations of one leak into another's exact-
        // balance assertions — but each still needs its own reset here between
        // runs, or effects would accumulate across repeated suite executions.
        $this->resetMemberCredits('E2E Member', 10);
        $this->resetMemberCredits('E2E Package Test Member', 0);
        $this->resetMemberCredits('E2E Concurrency Test Member', 0);

        $trainerUser = User::query()->where('name', 'E2E Trainer')->first();
        $trainer = $trainerUser ? Trainer::query()->where('user_id', $trainerUser->id)->first() : null;

        if ($trainer) {
            TrainerFeedbackNote::query()->where('trainer_id', $trainer->id)->delete();
            TrainerSlot::query()->where('trainer_id', $trainer->id)->delete();
        }

        // Belt-and-suspenders: any slot stuck 'busy' with no reservation attached
        // (e.g. from an interrupted prior run) blocks the "first available slot"
        // pattern our specs use — safe to clear in local/testing only.
        TrainerSlot::query()->whereNull('reservation_id')->where('status', 'busy')
            ->update(['status' => 'available']);

        // LoginController's IP+identifier RateLimiter (60s lock after 5 fails)
        // is a *separate* mechanism from the per-account failed_login_attempts/
        // locked_until columns reset above — it only clears on a *successful*
        // login (establishSession()). A test that deliberately drives an
        // account into that lock (and never successfully logs in afterward,
        // by design) leaves it live for the full 60s, silently blocking every
        // other test's login against the same identifier from the same IP
        // within that window. Full flush is simplest and safe here — this
        // command already refuses to run outside local/testing.
        Cache::flush();

        $this->info('E2E fixtures reset.');

        return self::SUCCESS;
    }

    private function resetMemberCredits(string $name, int $baselineLessons): void
    {
        $member = User::query()->where('name', $name)->first();
        if (! $member) {
            return;
        }

        $reservationIds = Reservation::query()->where('user_id', $member->id)->pluck('id');
        CreditTransaction::query()->whereIn('reference_id', $reservationIds)
            ->where('reference_type', Reservation::class)->delete();
        TrainerSlot::query()->whereIn('reservation_id', $reservationIds)
            ->update(['status' => 'available', 'reservation_id' => null]);
        Reservation::query()->where('user_id', $member->id)->delete();
        PackagePurchaseRequest::query()->where('user_id', $member->id)->delete();

        $member->update([
            'total_lessons' => $baselineLessons, 'used_lessons' => 0,
            'remaining_lessons' => $baselineLessons, 'pending_lessons' => 0,
            'active_package_id' => null,
        ]);
    }
}
