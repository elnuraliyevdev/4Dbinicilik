<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteClaimRequest;
use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Members imported with password=null (the 13 fully-structured legacy
 * members, and the 150+ name-only roster entries once an admin adds their
 * contact info) reach a working login through this flow instead of ever
 * having a real or guessable password assigned on their behalf.
 */
class ClaimAccountController extends Controller
{
    /**
     * The signed link an admin sends (sendClaimLink()) is meant to be opened
     * directly in a phone browser — it has to render an actual form, not a
     * raw JSON blob. wantsJson() keeps the JSON contract for API/test callers
     * while a plain browser navigation gets the real page.
     */
    public function show(Request $request, User $user): JsonResponse|View
    {
        if ($user->password !== null) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Bu hesap zaten kurulmuş, doğrudan giriş yapabilirsiniz.'], 409);
            }

            return view('claim-account', ['alreadyClaimed' => true, 'user' => null, 'needsContactInfo' => false]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'needs_contact_info' => ! $user->phone && ! $user->email,
            ]);
        }

        return view('claim-account', [
            'alreadyClaimed' => false,
            'user' => $user,
            'needsContactInfo' => ! $user->phone && ! $user->email,
        ]);
    }

    public function store(CompleteClaimRequest $request, User $user): JsonResponse
    {
        if ($user->password !== null) {
            return response()->json(['message' => 'Bu hesap zaten kurulmuş.'], 409);
        }

        $updates = ['password' => Hash::make($request->validated('password'))];
        if ($request->validated('phone')) {
            $updates['phone'] = $request->validated('phone');
        }
        if ($request->validated('email')) {
            $updates['email'] = $request->validated('email');
        }

        $user->update($updates);

        AuthEvent::log('ACCOUNT_CLAIMED', 'success', "{$user->name} hesabını kurdu (ilk şifre belirleme)", [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json(['message' => 'Hesabınız aktifleşti, şimdi giriş yapabilirsiniz.']);
    }
}
