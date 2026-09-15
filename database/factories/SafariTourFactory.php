<?php

namespace Database\Factories;

use App\Models\SafariTour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SafariTour>
 */
class SafariTourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Safari / Yürüyüş Turu',
            'duration_minutes' => 60,
            'price_per_person' => 1500,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
