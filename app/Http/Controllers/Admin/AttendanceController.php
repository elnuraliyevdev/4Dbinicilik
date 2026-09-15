<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\Reservation;
use App\Models\TrainerSlot;
use App\Services\CreditLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(private readonly CreditLedgerService $credits) {}

    public function today(): JsonResponse
    {
        $reservations = Reservation::query()
            ->with(['user', 'trainer.user', 'horse'])
            ->where('date', now()->toDateString())
            ->orderBy('time')
            ->get();

        return response()->json(['reservations' => $reservations]);
    }

    public function store(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:completed,no_show']]);

        if ($reservation->status !== 'confirmed') {
            return response()->json(['message' => 'Bu rezervasyonun yoklaması zaten alınmış.'], 422);
        }

        try {
            DB::transaction(function () use ($reservation, $data, $request) {
                $reservation->lockAndRequireStatus('confirmed', 'Bu rezervasyonun yoklaması zaten alınmış.');
                $this->credits->consumePending($reservation, 'attendance_consumed', $request->user()->id);
                $reservation->update(['status' => $data['status']]);
                TrainerSlot::where('reservation_id', $reservation->id)->update(['status' => 'available', 'reservation_id' => null]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        AuthEvent::log('ATTENDANCE_MARKED', 'info', "Admin {$request->user()->name} — yoklama: {$reservation->reservation_code} -> {$data['status']}", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['message' => 'Yoklama kaydedildi.', 'reservation' => $reservation->fresh()]);
    }
}
