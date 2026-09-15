<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditException;
use App\Models\CreditTransaction;
use App\Models\Family;
use App\Models\Reservation;
use App\Models\User;

/**
 * Single place that touches lesson-credit balances, so the priority rule
 * (confirmed with the club, 2026-09-15) is enforced consistently everywhere:
 * deduct from the member's own balance first; only fall back to their
 * family's shared pool once the individual balance is exhausted. A
 * cancellation refund always returns credit to whichever source the
 * original booking actually drew from — never re-derived from the user's
 * current family membership, which could have changed since.
 */
class CreditLedgerService
{
    /**
     * Locks the user (and, on fallback, the family) row before checking the
     * balance — the caller's transaction only ever locked the trainer_slot
     * row, which serializes bookings of the *same* slot but does nothing to
     * stop the same member/family from booking two *different* slots
     * concurrently and oversetting a balance of 1 into two reservations.
     */
    public function deductForReservation(User $user, int $amount, string $reason, Reservation $reservation, ?int $createdBy = null): CreditTransaction
    {
        $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

        if ($user->remaining_lessons >= $amount) {
            $user->decrement('remaining_lessons', $amount);
            $user->increment('pending_lessons', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'family_id' => null,
                'delta' => -$amount,
                'reason' => $reason,
                'reference_type' => Reservation::class,
                'reference_id' => $reservation->id,
                'created_by' => $createdBy,
            ]);
        }

        $family = $this->activeFamilyFor($user);
        $family = $family ? Family::whereKey($family->id)->lockForUpdate()->first() : null;

        if ($family && $family->remaining_lessons >= $amount) {
            $family->decrement('remaining_lessons', $amount);
            $family->increment('reserved_lessons', $amount);
            $user->increment('pending_lessons', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'family_id' => $family->id,
                'delta' => -$amount,
                'reason' => $reason,
                'reference_type' => Reservation::class,
                'reference_id' => $reservation->id,
                'note' => "{$user->name} adına {$family->name} ortak havuzundan düşüldü",
                'created_by' => $createdBy,
            ]);
        }

        throw new InsufficientCreditException;
    }

    /**
     * Refunds the reservation's original deduction back to its exact source
     * (individual or family — read from the transaction, not re-derived).
     */
    public function refundForReservation(Reservation $reservation, string $reason, ?int $createdBy = null): ?CreditTransaction
    {
        $original = $reservation->creditTransactions()->where('delta', '<', 0)->latest('id')->first();

        if (! $original) {
            return null;
        }

        $amount = abs($original->delta);
        $user = User::query()->find($original->user_id);

        if ($original->family_id) {
            $family = Family::query()->find($original->family_id);
            $family?->increment('remaining_lessons', $amount);
            $family?->decrement('reserved_lessons', $amount);
        } else {
            $user?->increment('remaining_lessons', $amount);
        }

        $user?->decrement('pending_lessons', $amount);

        return CreditTransaction::create([
            'user_id' => $original->user_id,
            'family_id' => $original->family_id,
            'delta' => $amount,
            'reason' => $reason,
            'reference_type' => Reservation::class,
            'reference_id' => $reservation->id,
            'note' => $original->family_id ? "İade: {$original->note}" : null,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Marks a reservation's credit as permanently consumed (late cancellation,
     * no-show) — no balance is returned, but pending_lessons/used_lessons move.
     */
    public function consumePending(Reservation $reservation, string $reason = 'attendance_consumed', ?int $createdBy = null): CreditTransaction
    {
        $original = $reservation->creditTransactions()->where('delta', '<', 0)->latest('id')->first();
        $amount = $original ? abs($original->delta) : 1;

        $user = User::whereKey($reservation->user_id)->lockForUpdate()->firstOrFail();
        $user->decrement('pending_lessons', $amount);
        $user->increment('used_lessons', $amount);

        if ($original?->family_id) {
            $family = Family::whereKey($original->family_id)->lockForUpdate()->first();
            $family?->decrement('reserved_lessons', $amount);
            $family?->increment('used_lessons', $amount);
        }

        return CreditTransaction::create([
            'user_id' => $reservation->user_id,
            'family_id' => null,
            'delta' => 0,
            'reason' => $reason,
            'reference_type' => Reservation::class,
            'reference_id' => $reservation->id,
            'note' => $original?->family_id
                ? "Kalıcı kullanım (aile havuzundan): {$amount} ders"
                : "Kalıcı kullanım: {$amount} ders",
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Admin-initiated direct balance change — manual correction or a package
     * purchase grant. Always applied to the member's individual balance
     * (never the family pool; admins adjust a family's pool via a separate
     * family-scoped call if that's genuinely what's needed).
     */
    public function adjustIndividual(User $user, int $delta, string $reason, ?int $createdBy = null, ?string $note = null): CreditTransaction
    {
        $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

        if ($delta >= 0) {
            $user->increment('remaining_lessons', $delta);
            if ($reason === 'package_purchase') {
                $user->increment('total_lessons', $delta);
            }
        } else {
            $applied = min(abs($delta), $user->remaining_lessons);
            $user->decrement('remaining_lessons', $applied);
            $delta = -$applied;
        }

        return CreditTransaction::create([
            'user_id' => $user->id,
            'family_id' => null,
            'delta' => $delta,
            'reason' => $reason,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }

    private function activeFamilyFor(User $user): ?Family
    {
        $membership = $user->familyMemberships()->with('family')->first();

        return $membership?->family;
    }
}
