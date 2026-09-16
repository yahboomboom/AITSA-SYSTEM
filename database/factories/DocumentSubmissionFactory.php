<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'document_type' => 'form137',
            'notes' => null,
            'file_path' => 'documents/'.$this->faker->uuid.'.pdf',
            'original_name' => 'form137-scan.pdf',
            'mime_type' => 'application/pdf',
            'size' => 204800,
            'status' => 'pending',
        ];
    }
}
