<?php

namespace App\Http\Controllers\Member;

use App\Exceptions\InsufficientCreditException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\BookLessonRequest;
use App\Models\AuthEvent;
use App\Models\ClubSetting;
use App\Models\Reservation;
use App\Models\Trainer;
use App\Models\TrainerSlot;
use App\Services\CreditLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function __construct(private readonly CreditLedgerService $credits) {}

    public function index(Request $request): JsonResponse
    {
        $reservations = $request->user()->reservations()
            ->with(['trainer.user', 'horse', 'safariTour'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();

        return response()->json(['reservations' => $reservations]);
    }

    public function store(BookLessonRequest $request): JsonResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->validated('date'));
        $time = $request->validated('time');

        $mondayClosed = filter_var(ClubSetting::get('monday_closed', true), FILTER_VALIDATE_BOOLEAN);
        if ($mondayClosed && $date->isMonday()) {
            return response()->json(['message' => 'Pazartesi günleri kulüp kapalıdır.'], 422);
        }

        if ($date->isToday() && Carbon::parse($date->toDateString().' '.$time)->isPast()) {
            return response()->json(['message' => 'Geçmiş saatteki bir seans için rezervasyon yapılamaz.'], 422);
        }

        $trainer = Trainer::findOrFail($request->validated('trainer_id'));

        try {
            $reservation = DB::transaction(function () use ($request, $user, $date, $trainer) {
                // Row-level lock on the slot itself serializes concurrent booking
                // attempts for the same trainer/date/time — the earlier exists()-only
                // check left a narrow window two simultaneous requests could both pass.
                $slot = TrainerSlot::firstOrCreate(
                    ['trainer_id' => $trainer->id, 'date' => $date->toDateString(), 'time' => $request->validated('time')],
                    ['status' => 'available']
                );
                $slot = TrainerSlot::whereKey($slot->id)->lockForUpdate()->firstOrFail();

                if ($slot->status === 'off') {
                    throw new \RuntimeException('Eğitmen bu saatte müsait değil.');
                }
                if ($slot->status === 'busy') {
                    throw new \RuntimeException('Bu saat az önce başka biri tarafından alındı, lütfen başka bir saat seçin.');
                }

                $reservation = Reservation::create([
                    'reservation_code' => 'RES-'.Str::upper(Str::random(8)),
                    'user_id' => $user->id,
                    'trainer_id' => $trainer->id,
                    'horse_id' => $request->validated('horse_id'),
                    'type' => 'lesson',
                    'activity_label' => $request->validated('activity_label') ?: '🏇 Manej Binicilik Dersi',
                    'date' => $date->toDateString(),
                    'time' => $request->validated('time'),
                    'status' => 'confirmed',
                    'source' => 'member',
                ]);

                $this->credits->deductForReservation($user, 1, 'booking', $reservation, $user->id);

                $slot->update(['status' => 'busy', 'reservation_id' => $reservation->id]);

                return $reservation;
            });
        } catch (InsufficientCreditException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        AuthEvent::log('RESERVATION_CREATED', 'info', "{$user->name} — {$reservation->activity_label} ({$reservation->date} {$reservation->time})", [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json(['reservation' => $reservation->fresh(['trainer.user', 'horse'])], 201);
    }

    /**
     * Free cancellation — only allowed while still outside the cancellation window.
     */
    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeOwnership($request, $reservation);

        if ($reservation->status !== 'confirmed') {
            return response()->json(['message' => 'Bu rezervasyon zaten aktif değil.'], 422);
        }

        $hoursUntil = now()->diffInMinutes($reservation->startsAt(), false) / 60;

        if ($hoursUntil < $reservation->cancellationWindowHours()) {
            return response()->json([
                'message' => 'İptal penceresi geçti — bu rezervasyon yalnızca "geç iptal" olarak (ders hakkı iade edilmeden) iptal edilebilir.',
                'late_cancel_required' => true,
            ], 422);
        }

        DB::transaction(function () use ($reservation, $request) {
            $this->credits->refundForReservation($reservation, 'free_cancellation', $request->user()->id);
            $reservation->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            TrainerSlot::where('reservation_id', $reservation->id)->update(['status' => 'available', 'reservation_id' => null]);
        });

        AuthEvent::log('RESERVATION_CANCELLED', 'info', "{$request->user()->name} — ücretsiz iptal: {$reservation->reservation_code}", [
            'user_id' => $request->user()->id,
            'role' => $request->user()->role,
        ]);

        return response()->json(['message' => 'Rezervasyon iptal edildi, ders hakkınız iade edildi.']);
    }

    /**
     * Late cancellation — inside the cancellation window: consumes the credit
     * permanently (no refund), only exists so the member isn't just a no-show.
     */
    public function lateCancel(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeOwnership($request, $reservation);

        if ($reservation->status !== 'confirmed') {
            return response()->json(['message' => 'Bu rezervasyon zaten aktif değil.'], 422);
        }

        $hoursUntil = now()->diffInMinutes($reservation->startsAt(), false) / 60;

        if ($hoursUntil >= $reservation->cancellationWindowHours()) {
            return response()->json([
                'message' => 'Hâlâ ücretsiz iptal penceresi içindesiniz, normal iptali kullanın.',
            ], 422);
        }

        if ($hoursUntil < 0) {
            return response()->json(['message' => 'Ders saati geçmiş, iptal edilemez.'], 422);
        }

        DB::transaction(function () use ($reservation) {
            $this->credits->consumePending($reservation);
            $reservation->update([
                'status' => 'late_cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Kulüp kuralı gereği 2 saatten az kala iptal edildi, ders hakkı kullanıldı sayıldı.',
            ]);
            TrainerSlot::where('reservation_id', $reservation->id)->update(['status' => 'available', 'reservation_id' => null]);
        });

        AuthEvent::log('LATE_CANCELLATION', 'warning', "{$request->user()->name} — geç iptal (ders hakkı düşüldü): {$reservation->reservation_code}", [
            'user_id' => $request->user()->id,
            'role' => $request->user()->role,
        ]);

        return response()->json(['message' => 'Geç iptal onaylandı, kural gereği ders hakkınız kullanıldı sayıldı.']);
    }

    private function authorizeOwnership(Request $request, Reservation $reservation): void
    {
        abort_unless($reservation->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
    }
}
