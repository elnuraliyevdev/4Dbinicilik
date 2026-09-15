<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Trainer\Concerns\ResolvesCurrentTrainer;
use App\Http\Requests\Trainer\ToggleSlotRequest;
use App\Models\TrainerSlot;
use Illuminate\Http\JsonResponse;

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

        $slot = TrainerSlot::firstOrNew([
            'trainer_id' => $trainer->id,
            'date' => $request->validated('date'),
            'time' => $request->validated('time'),
        ]);

        if ($slot->exists && $slot->status === 'busy') {
            return response()->json(['message' => 'Bu saatte zaten bir rezervasyon var, önce onu iptal etmelisiniz.'], 422);
        }

        $slot->status = $request->validated('status');
        $slot->save();

        return response()->json(['slot' => $slot]);
    }
}
