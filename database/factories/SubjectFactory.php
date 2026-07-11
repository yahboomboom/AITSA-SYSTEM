<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'title' => fake()->words(4, true),
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'mode' => 'F2F',
        ];
    }
}
