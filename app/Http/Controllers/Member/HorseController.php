<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Horse;
use Illuminate\Http\JsonResponse;

/**
 * Backs the booking modal's horse-preference dropdown with the real roster —
 * the old prototype (and briefly, a patch that copied it) hardcoded horse
 * names against guessed IDs that didn't match any actual `horses` row in a
 * given environment.
 */
class HorseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'horses' => Horse::query()->where('status', 'available')->orderBy('name')->get(['id', 'name', 'breed']),
        ]);
    }
}
