<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\BookSafariRequest;
use App\Models\AuthEvent;
use App\Models\Reservation;
use App\Models\SafariTour;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SafariController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'tours' => SafariTour::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Safari tours are priced per person and paid separately from the lesson
     * credit pool (price_try recorded, payment itself is still handled at the
     * club / over WhatsApp — no payment gateway in this phase).
     */
    public function store(BookSafariRequest $request): JsonResponse
    {
        $user = $request->user();
        $tour = SafariTour::findOrFail($request->validated('safari_tour_id'));
        $participants = (int) $request->validated('participants');

        $reservation = DB::transaction(function () use ($request, $user, $tour, $participants) {
            return Reservation::create([
                'reservation_code' => 'SAF-'.Str::upper(Str::random(8)),
                'user_id' => $user->id,
                'safari_tour_id' => $tour->id,
                'type' => 'safari',
                'activity_label' => '🐴 '.$tour->name,
                'date' => $request->validated('date'),
                'time' => $request->validated('time'),
                'status' => 'confirmed',
                'participants' => $participants,
                'price_try' => $tour->price_per_person * $participants,
                'source' => 'member',
            ]);
        });

        AuthEvent::log('SAFARI_BOOKED', 'info', "{$user->name} — {$tour->name} ({$participants} kişi)", [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json(['reservation' => $reservation], 201);
    }
}
