<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'primary_user_id', 'package_total', 'remaining_lessons', 'used_lessons', 'reserved_lessons'])]
class Family extends Model
{
    public function primaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}
