<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ClubSetting;
use App\Models\Reservation;
use App\Models\Trainer;
use App\Models\TrainerSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    /**
     * Default open hours when no admin-managed trainer_slots rows exist for a
     * given day yet (Phase 4 adds real per-trainer schedule management).
     */
    private const DEFAULT_TIMES = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
        ]);

        $date = Carbon::parse($validated['date']);

        $mondayClosed = filter_var(ClubSetting::get('monday_closed', true), FILTER_VALIDATE_BOOLEAN);
        if ($mondayClosed && $date->isMonday()) {
            return response()->json(['closed' => true, 'reason' => 'Pazartesi günleri kulüp kapalıdır.', 'trainers' => []]);
        }

        // whereHas('user') matters: a trainer whose linked user was soft-deleted
        // would otherwise crash this endpoint at $trainer->user->name below,
        // taking down the whole booking calendar for every member.
        $trainers = Trainer::query()
            ->where('is_active', true)
            ->when($validated['trainer_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->whereHas('user')
            ->with('user')
            ->get();

        $busyTimes = Reservation::query()
            ->where('date', $date->toDateString())
            ->where('status', 'confirmed')
            ->whereIn('trainer_id', $trainers->pluck('id'))
            ->get(['trainer_id', 'time'])
            ->groupBy('trainer_id')
            ->map(fn ($rows) => $rows->pluck('time')->map(fn ($t) => substr($t, 0, 5))->all());

        $offTimes = TrainerSlot::query()
            ->where('date', $date->toDateString())
            ->where('status', 'off')
            ->whereIn('trainer_id', $trainers->pluck('id'))
            ->get(['trainer_id', 'time'])
            ->groupBy('trainer_id')
            ->map(fn ($rows) => $rows->pluck('time')->map(fn ($t) => substr($t, 0, 5))->all());

        $isToday = $date->isToday();
        $currentTime = now()->format('H:i');

        $result = $trainers->map(function (Trainer $trainer) use ($busyTimes, $offTimes, $isToday, $currentTime) {
            $busy = $busyTimes->get($trainer->id, []);
            $off = $offTimes->get($trainer->id, []);

            $slots = collect(self::DEFAULT_TIMES)->map(function (string $time) use ($busy, $off, $isToday, $currentTime) {
                $isPast = $isToday && $time <= $currentTime;
                $status = $isPast ? 'off' : (in_array($time, $off, true) ? 'off' : (in_array($time, $busy, true) ? 'busy' : 'available'));

                return ['time' => $time, 'status' => $status];
            });

            return [
                'trainer_id' => $trainer->id,
                'name' => $trainer->user->name,
                'title' => $trainer->title,
                'avatar_letter' => $trainer->avatar_letter,
                'slots' => $slots,
            ];
        });

        return response()->json(['closed' => false, 'date' => $date->toDateString(), 'trainers' => $result]);
    }
}
