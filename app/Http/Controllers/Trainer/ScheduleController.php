<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Trainer\Concerns\ResolvesCurrentTrainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    use ResolvesCurrentTrainer;

    public function index(Request $request): JsonResponse
    {
        $trainer = $this->resolveTrainer($request);

        $date = Carbon::parse($request->query('date', now()->toDateString()));

        $reservations = $trainer->reservations()
            ->with(['user', 'horse'])
            ->where('date', $date->toDateString())
            ->whereIn('status', ['confirmed', 'completed', 'no_show'])
            ->orderBy('time')
            ->get();

        return response()->json([
            'trainer' => ['id' => $trainer->id, 'name' => $trainer->user->name, 'title' => $trainer->title],
            'date' => $date->toDateString(),
            'reservations' => $reservations,
        ]);
    }
}
