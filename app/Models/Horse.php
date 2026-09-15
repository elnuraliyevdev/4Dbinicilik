<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'breed', 'status', 'notes'])]
class Horse extends Model
{
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
