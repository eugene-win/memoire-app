<?php

namespace Database\Factories;

use App\Models\DefenseYear;
use App\Models\Student;
use App\Models\StudyLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        $matricule = fake()->unique()->numerify('########');

        return [
            'user_id' => User::factory()->state([
                'role' => 'student',
                'matricule' => $matricule,
            ]),
            'matricule' => $matricule,
            'enrollment_status' => 'enrolled',
        ];
    }

    public function eligibleToDeposit(): static
    {
        return $this->state(function (): array {
            $level = StudyLevel::firstOrCreate(
                ['code' => 'L3'],
                ['name' => 'Licence 3', 'is_active' => true],
            );
            $year = DefenseYear::firstOrCreate(
                ['label' => (string) now()->year],
                ['is_active' => true],
            );

            return [
                'study_level_id' => $level->id,
                'defense_year_id' => $year->id,
                'institutionally_verified_at' => now(),
                'defended_at' => now(),
            ];
        });
    }
}
