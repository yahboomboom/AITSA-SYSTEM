<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'school_year' => '2026-2027',
            'semester' => 1,
            'type' => 'regular',
            'status' => 'enrolled',
        ];
    }
}
