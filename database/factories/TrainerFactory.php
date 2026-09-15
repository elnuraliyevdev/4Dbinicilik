<?php

namespace Database\Factories;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'trainer']),
            'title' => fake()->jobTitle(),
            'avatar_letter' => fake()->randomLetter(),
            'is_active' => true,
        ];
    }
}
