<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->lastName().' Ailesi',
            'package_total' => 30,
            'remaining_lessons' => 10,
            'used_lessons' => 0,
            'reserved_lessons' => 0,
        ];
    }
}
