<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'attempted_identifier', 'ip_address', 'role', 'event_type', 'severity', 'description'])]
class AuthEvent extends Model
{
    const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $eventType, string $severity, string $description, array $extra = []): self
    {
        return static::create(array_merge([
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
        ], $extra));
    }
}
