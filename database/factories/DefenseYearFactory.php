<?php

namespace Database\Factories;

use App\Models\DefenseYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DefenseYear>
 */
class DefenseYearFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => (string) fake()->unique()->numberBetween(2000, 2100),
            'is_active' => true,
        ];
    }
}
