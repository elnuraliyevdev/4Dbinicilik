<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lesson_count', 'price_try', 'is_active', 'is_featured', 'badge_label', 'sort_order', 'cancellation_hours'])]
class Package extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_try' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PackagePurchaseRequest::class);
    }
}
