<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'duration_minutes', 'price_per_person', 'description', 'image_url', 'features', 'is_active', 'sort_order', 'cancellation_hours'])]
class SafariTour extends Model
{
    protected function casts(): array
    {
        return [
            'price_per_person' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
