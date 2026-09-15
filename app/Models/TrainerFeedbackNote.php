<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trainer_id', 'discipline_level', 'student_user_id', 'reservation_id', 'note'])]
class TrainerFeedbackNote extends Model
{
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
