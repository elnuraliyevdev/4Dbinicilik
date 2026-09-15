<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trainer_id', 'date', 'time', 'status', 'reservation_id'])]
class TrainerSlot extends Model
{
    // Deliberately no 'date' cast — see Reservation model for why (breaks
    // exact-string where()/firstOrCreate() matching against 'Y-m-d').

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
