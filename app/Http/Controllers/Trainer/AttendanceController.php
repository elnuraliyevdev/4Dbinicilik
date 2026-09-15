<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Trainer\Concerns\ResolvesCurrentTrainer;
use App\Http\Requests\Trainer\MarkAttendanceRequest;
use App\Models\AuthEvent;
use App\Models\Reservation;
use App\Models\TrainerSlot;
use App\Services\CreditLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    use ResolvesCurrentTrainer;

    public function __construct(private readonly CreditLedgerService $credits) {}

    public function store(MarkAttendanceRequest $request, Reservation $reservation): JsonResponse
    {
        $trainer = $this->resolveTrainer($request);
        abort_unless($reservation->trainer_id === $trainer->id, 403);

        if ($reservation->status !== 'confirmed') {
            return response()->json(['message' => 'Bu rezervasyonun yoklaması zaten alınmış.'], 422);
        }

        $status = $request->validated('status');

        try {
            DB::transaction(function () use ($reservation, $status, $request) {
                $reservation->lockAndRequireStatus('confirmed', 'Bu rezervasyonun yoklaması zaten alınmış.');
                // Both outcomes permanently consume the credit — the lesson slot was
                // held either way, unlike a member-initiated free cancellation.
                $this->credits->consumePending($reservation, 'attendance_consumed', $request->user()->id);
                $reservation->update(['status' => $status]);
                TrainerSlot::where('reservation_id', $reservation->id)->update(['status' => 'available', 'reservation_id' => null]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $label = $status === 'completed' ? 'Ders tamamlandı' : 'Öğrenci derse gelmedi';
        AuthEvent::log('ATTENDANCE_MARKED', 'info', "{$label}: {$reservation->reservation_code}", [
            'user_id' => $request->user()->id,
            'role' => 'trainer',
        ]);

        return response()->json(['message' => 'Yoklama kaydedildi.', 'reservation' => $reservation->fresh()]);
    }
}
