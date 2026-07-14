<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company() . ' Office';

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
