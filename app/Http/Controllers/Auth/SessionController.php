<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClubSetting;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The frontend's single source of truth for "who am I / is my session still
 * valid" — replaces the old prototype's client-only `localStorage.angora_auth_active`
 * flag, which gated the UI but never actually protected anything.
 */
class SessionController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['authenticated' => false]);
        }

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'ref_code' => $user->ref_code,
                'role' => $user->role,
                'total_lessons' => $user->total_lessons,
                'used_lessons' => $user->used_lessons,
                'remaining_lessons' => $user->remaining_lessons,
                'pending_lessons' => $user->pending_lessons,
                'active_package_id' => $user->active_package_id,
                'package_expires_at' => $user->package_expires_at,
            ],
            'club' => [
                'whatsapp_number' => ClubSetting::get('club_whatsapp_number', '905551234567'),
                'monday_closed' => (bool) ClubSetting::get('monday_closed', true),
                'cancellation_window_hours' => (int) ClubSetting::get('cancellation_window_hours', 2),
            ],
        ]);
    }

    /**
     * Public, minimal (id/name/title only — no contact info) roster for the
     * trainer-login dropdown. Real trainer IDs are DB integers now, not the
     * old prototype's hardcoded "TR-1" style strings.
     */
    public function trainerOptions(): JsonResponse
    {
        return response()->json([
            // whereHas + the null-check both matter: a trainer row whose linked
            // user was soft-deleted must never 500 this public, unauthenticated,
            // unconditionally-loaded endpoint — that would take down the entire
            // login page for every role, not just that one trainer.
            'trainers' => Trainer::query()->where('is_active', true)->whereHas('user')->with('user:id,name')
                ->get(['id', 'user_id', 'title'])
                ->filter(fn (Trainer $t) => $t->user !== null)
                ->map(fn (Trainer $t) => ['id' => $t->id, 'name' => $t->user->name, 'title' => $t->title])
                ->values(),
        ]);
    }
}
