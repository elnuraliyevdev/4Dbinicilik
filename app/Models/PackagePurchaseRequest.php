<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'package_id', 'status', 'reviewed_by', 'reviewed_at', 'notes'])]
class PackagePurchaseRequest extends Model
{
    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Same row-lock-then-recheck pattern as Reservation::lockAndRequireStatus()
     * — the pre-transaction "is this still pending" check in approve()/reject()
     * is only a fast optimistic rejection; two concurrent approve() calls on
     * the same request could otherwise both pass it before either commits,
     * double-crediting the member's lesson balance.
     */
    public function lockAndRequireStatus(string $status, string $message): void
    {
        $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

        if ($locked->status !== $status) {
            throw new \RuntimeException($message);
        }
    }
}
