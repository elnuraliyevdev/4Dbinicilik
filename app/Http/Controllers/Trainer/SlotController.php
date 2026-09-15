<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Trainer\Concerns\ResolvesCurrentTrainer;
use App\Http\Requests\Trainer\ToggleSlotRequest;
use App\Models\TrainerSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SlotController extends Controller
{
    use ResolvesCurrentTrainer;

    /**
     * Lets a trainer mark themselves unavailable (or re-open) a specific slot
     * that has no reservation — cannot be used to bump an existing booking.
     */
    public function toggle(ToggleSlotRequest $request): JsonResponse
    {
        $trainer = $this->resolveTrainer($request);

        try {
            $slot = DB::transaction(function () use ($trainer, $request) {
                // Same row-lock pattern as the member booking path — without
                // it, a toggle racing a concurrent booking can read "not busy",
                // then blindly overwrite the slot's status after the booking
                // commits, leaving a confirmed reservation behind a slot that
                // looks open again (and can be double-booked).
                $slot = TrainerSlot::firstOrCreate(
                    ['trainer_id' => $trainer->id, 'date' => $request->validated('date'), 'time' => $request->validated('time')],
                    ['status' => 'available']
                );
                $slot = TrainerSlot::whereKey($slot->id)->lockForUpdate()->firstOrFail();

                if ($slot->status === 'busy') {
                    throw new \RuntimeException('Bu saatte zaten bir rezervasyon var, önce onu iptal etmelisiniz.');
                }

                $slot->update(['status' => $request->validated('status')]);

                return $slot;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['slot' => $slot]);
    }
}
