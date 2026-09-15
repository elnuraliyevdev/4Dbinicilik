<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_count' => 8,
            'price_try' => 13200,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
