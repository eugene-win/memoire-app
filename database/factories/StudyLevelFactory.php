<?php

namespace Database\Factories;

use App\Models\StudyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyLevel>
 */
class StudyLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'LVL-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
