<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteClaimRequest;
use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Members imported with password=null (the 13 fully-structured legacy
 * members, and the 150+ name-only roster entries once an admin adds their
 * contact info) reach a working login through this flow instead of ever
 * having a real or guessable password assigned on their behalf.
 */
class ClaimAccountController extends Controller
{
    public function show(User $user): JsonResponse
    {
        if ($user->password !== null) {
            return response()->json(['message' => 'Bu hesap zaten kurulmuş, doğrudan giriş yapabilirsiniz.'], 409);
        }

        return response()->json([
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'needs_contact_info' => ! $user->phone && ! $user->email,
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
