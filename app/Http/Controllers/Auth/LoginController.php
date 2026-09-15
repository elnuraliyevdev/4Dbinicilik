<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Requests\Auth\MemberLoginRequest;
use App\Http\Requests\Auth\TrainerLoginRequest;
use App\Models\AuthEvent;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * All three flows are real, server-checked, non-bypassable — this replaces the
 * old client-side prototype's fake checks (any length>3 string, any non-empty
 * password, a hardcoded PIN echoed in the UI). No response here ever reveals a
 * password/PIN value or which specific field failed.
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 60;

    public function member(MemberLoginRequest $request): JsonResponse
    {
        $identifier = trim($request->string('identifier'));
        $key = $this->throttleKey('member', $request, $identifier);

        if ($blocked = $this->blockIfTooManyAttempts($key, $request, 'member', $identifier)) {
            return $blocked;
        }

        $user = User::query()
            ->where('role', 'member')
            ->where(function ($q) use ($identifier) {
                $q->where('phone', $identifier)
                    ->orWhere('ref_code', $identifier)
                    ->orWhereRaw('LOWER(email) = ?', [mb_strtolower($identifier)]);
            })
            ->first();

        if ($user && $user->password === null) {
            // Legitimate state for freshly-imported legacy members: intentionally not a
            // generic failure, but it never confirms whether the *password* would be right.
            RateLimiter::hit($key, self::LOCK_SECONDS);
            AuthEvent::log('AUTH_CLAIM_REQUIRED', 'info', "Hesap kurulumu tamamlanmamış giriş denemesi: [{$identifier}]", [
                'attempted_identifier' => $identifier,
                'ip_address' => $request->ip(),
                'role' => 'member',
            ]);

            return response()->json([
                'message' => 'Bu hesap için henüz şifre oluşturulmamış. Lütfen kulüpten hesap kurulum bağlantısı isteyin.',
            ], 403);
        }

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $this->recordFailure($user, $identifier, 'member', $request);

            return $this->invalidCredentialsResponse();
        }

        return $this->establishSession($user, $key, $request);
    }

    public function trainer(TrainerLoginRequest $request): JsonResponse
    {
        $trainer = Trainer::with('user')->findOrFail($request->integer('trainer_id'));
        $identifier = 'trainer:'.$trainer->id;
        $key = $this->throttleKey('trainer', $request, $identifier);

        if ($blocked = $this->blockIfTooManyAttempts($key, $request, 'trainer', $identifier)) {
            return $blocked;
        }

        $user = $trainer->user;

        if (! $user || ! $user->pin_hash || ! Hash::check($request->string('pin'), $user->pin_hash)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $this->recordFailure($user, $identifier, 'trainer', $request);

            return $this->invalidCredentialsResponse();
        }

        return $this->establishSession($user, $key, $request);
    }

    public function admin(AdminLoginRequest $request): JsonResponse
    {
        $email = trim($request->string('email'));
        $key = $this->throttleKey('admin', $request, $email);

        if ($blocked = $this->blockIfTooManyAttempts($key, $request, 'admin', $email)) {
            return $blocked;
        }

        $user = User::query()
            ->where('role', 'admin')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();

        $passwordOk = $user && Hash::check($request->string('password'), $user->password);
        $pinOk = $user && $user->pin_hash && Hash::check($request->string('pin'), $user->pin_hash);

        if (! $passwordOk || ! $pinOk) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $this->recordFailure($user, $email, 'admin', $request);

            return $this->invalidCredentialsResponse();
        }

        return $this->establishSession($user, $key, $request);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            AuthEvent::log('SESSION_LOGOUT', 'info', "Oturum kapatıldı: [{$user->name}]", [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'role' => $user->role,
            ]);
        }

        return response()->json(['message' => 'Oturum kapatıldı.']);
    }

    private function throttleKey(string $flow, Request $request, string $identifier): string
    {
        return "login:{$flow}:{$request->ip()}:".mb_strtolower($identifier);
    }

    private function blockIfTooManyAttempts(string $key, Request $request, string $role, string $identifier): ?JsonResponse
    {
        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return null;
        }

        $waitSeconds = RateLimiter::availableIn($key);
        AuthEvent::log('BRUTE_FORCE_BLOCKED', 'danger', "Çok fazla hatalı deneme, hesap {$waitSeconds}sn kilitlendi: [{$identifier}]", [
            'attempted_identifier' => $identifier,
            'ip_address' => $request->ip(),
            'role' => $role,
        ]);

        return response()->json([
            'message' => "Çok fazla hatalı deneme. Lütfen {$waitSeconds} saniye sonra tekrar deneyin.",
        ], 429);
    }

    private function recordFailure(?User $user, string $identifier, string $role, Request $request): void
    {
        if ($user) {
            $attempts = $user->failed_login_attempts + 1;
            $user->forceFill([
                'failed_login_attempts' => $attempts,
                'locked_until' => $attempts >= self::MAX_ATTEMPTS ? now()->addSeconds(self::LOCK_SECONDS) : $user->locked_until,
            ])->save();
        }

        AuthEvent::log('AUTH_FAIL', 'warning', "Başarısız giriş denemesi ({$role}): [{$identifier}]", [
            'user_id' => $user?->id,
            'attempted_identifier' => $identifier,
            'ip_address' => $request->ip(),
            'role' => $role,
        ]);
    }

    private function establishSession(User $user, string $key, Request $request): JsonResponse
    {
        RateLimiter::clear($key);
        $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();

        Auth::guard('web')->login($user, remember: false);
        $request->session()->regenerate();

        AuthEvent::log('AUTH_SUCCESS', 'success', "Giriş başarılı ({$user->role}): [{$user->name}]", [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'role' => $user->role,
        ]);

        return response()->json([
            'message' => 'Giriş başarılı.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    private function invalidCredentialsResponse(): JsonResponse
    {
        // Deliberately generic — never reveals which field was wrong, and never
        // echoes back any password/PIN value (the exact bug this replaces).
        return response()->json(['message' => 'Giriş bilgileri hatalı.'], 422);
    }
}
