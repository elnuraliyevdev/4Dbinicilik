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
    public function deductForReservation(User $user, int $amount, string $reason, Reservation $reservation, ?int $createdBy = null): CreditTransaction
    {
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
    public function consumePending(Reservation $reservation): void
    {
        $original = $reservation->creditTransactions()->where('delta', '<', 0)->latest('id')->first();
        $amount = $original ? abs($original->delta) : 1;

        $user = $reservation->user;
        $user->decrement('pending_lessons', $amount);
        $user->increment('used_lessons', $amount);

        if ($original?->family_id) {
            $family = Family::query()->find($original->family_id);
            $family?->decrement('reserved_lessons', $amount);
            $family?->increment('used_lessons', $amount);
        }
    }

    /**
     * Admin-initiated direct balance change — manual correction or a package
     * purchase grant. Always applied to the member's individual balance
     * (never the family pool; admins adjust a family's pool via a separate
     * family-scoped call if that's genuinely what's needed).
     */
    public function adjustIndividual(User $user, int $delta, string $reason, ?int $createdBy = null, ?string $note = null): CreditTransaction
    {
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
