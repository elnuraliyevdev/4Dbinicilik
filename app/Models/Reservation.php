<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reservation_code', 'user_id', 'trainer_id', 'horse_id', 'type', 'activity_label',
    'date', 'time', 'status', 'participants', 'price_try', 'source',
    'cancelled_at', 'cancellation_reason',
])]
class Reservation extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
}
