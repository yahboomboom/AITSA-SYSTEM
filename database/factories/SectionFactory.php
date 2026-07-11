<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'block_label' => 'A',
            'days' => ['M', 'W'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'Rm 101',
            'professor' => fake()->name(),
            'capacity' => 40,
            'school_year' => '2026-2027',
        ];
    }
}
