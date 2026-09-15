<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['family_id', 'user_id', 'display_name', 'is_primary'])]
class FamilyMember extends Model
{
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->display_name ?? 'İsimsiz Üye';
    }
}
