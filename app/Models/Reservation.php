<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'reservation_code', 'user_id', 'trainer_id', 'horse_id', 'safari_tour_id', 'type', 'activity_label',
    'date', 'time', 'status', 'participants', 'price_try', 'source',
    'cancelled_at', 'cancellation_reason',
])]
class Reservation extends Model
{
    protected function casts(): array
    {
        return [
            // Deliberately NOT cast to 'date' — Eloquent's date cast round-trips
            // through a full datetime on write for some drivers, which breaks
            // exact-string where()/firstOrCreate() matching against 'Y-m-d'.
            // Kept as a plain 'Y-m-d' string throughout the app instead.
            'price_try' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    public function safariTour(): BelongsTo
    {
        return $this->belongsTo(SafariTour::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'reference_id')->where('reference_type', self::class);
    }

    public function cancellationWindowHours(): int
    {
        if ($this->type === 'safari' && $this->safari_tour_id) {
            $hours = $this->safariTour?->cancellation_hours;
            if ($hours !== null) {
                return $hours;
            }
        }

        return (int) ClubSetting::get('cancellation_window_hours', 2);
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->date.' '.$this->time);
    }

    /**
     * Row-locks this reservation and re-checks its status inside the
     * caller's transaction. Every status-changing action (cancel, late
     * cancel, attendance) does an optimistic pre-transaction status check
     * for a fast rejection, but that alone lets two concurrent requests for
     * the same reservation both pass before either commits — double
     * refunding or double consuming the credit. Call this first inside the
     * transaction as the actual guard.
     */
    public function lockAndRequireStatus(string $status, string $message): void
    {
        $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

        if ($locked->status !== $status) {
            throw new \RuntimeException($message);
        }
    }
}
