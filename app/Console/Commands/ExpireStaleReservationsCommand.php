<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\TrainerSlot;
use App\Models\AuthEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * A 'confirmed' reservation whose start time has passed with no admin/trainer
 * attendance action is left in limbo forever otherwise — this marks it
 * 'no_show' and frees its slot, mirroring exactly what AttendanceController
 * does manually. Intended to run on a schedule (see routes/console.php).
 */
class ExpireStaleReservationsCommand extends Command
{
    protected $signature = 'reservations:expire-stale';

    protected $description = 'Mark past-due confirmed reservations as no_show and free their slots';

    public function handle(): int
    {
        $stale = Reservation::query()
            ->where('status', 'confirmed')
            ->where('date', '<=', now()->toDateString())
            ->get()
            ->filter(fn (Reservation $r) => $r->startsAt()->isPast());

        $expired = 0;

        foreach ($stale as $reservation) {
            try {
                DB::transaction(function () use ($reservation) {
                    $reservation->lockAndRequireStatus('confirmed', 'no longer confirmed');
                    $reservation->update(['status' => 'no_show']);
                    TrainerSlot::where('reservation_id', $reservation->id)
                        ->update(['status' => 'available', 'reservation_id' => null]);
                });
            } catch (\RuntimeException $e) {
                // Someone (admin/trainer) already marked it manually between
                // the query above and this transaction — nothing to do.
                continue;
            }

            AuthEvent::log('ATTENDANCE_AUTO_EXPIRED', 'info', "Otomatik: {$reservation->reservation_code} süresi geçtiği için no_show işaretlendi", [
                'reservation_id' => $reservation->id,
            ]);
            $expired++;
        }

        $this->info("{$expired} stale reservation(s) marked no_show.");

        return self::SUCCESS;
    }
}
