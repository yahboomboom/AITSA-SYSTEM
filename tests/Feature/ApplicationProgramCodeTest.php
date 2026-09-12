<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * major must hold the Program's `code` (e.g. "BSOA"), not the display name
 * submitted by the apply form — User::program() resolves a student's program
 * by that code, and the enrollment block/section lookup (so the whole
 * Enrollment page, once cleared) silently comes up empty otherwise.
 */
class ApplicationProgramCodeTest extends TestCase
{
    use RefreshDatabase;

    private function baseApplicationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'contact_number' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'sex' => 'Male',
            'address' => 'Brgy. Sala, Cabuyao, Laguna',
            'last_school' => 'Cabuyao National High School',
            'year_graduated' => '2024',
            'applicant_type' => 'NEW',
            'program_key' => 'em3',
            'program_name' => 'Events Management NC III',
            'program_level' => 'TESDA',
        ], $overrides);
    }

    public function test_applicant_major_is_stored_as_the_program_code_not_the_display_name(): void
    {
        $response = $this->post('/apply', $this->baseApplicationData());

        $response->assertRedirect();
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();
        $this->assertSame('EMNC3', $applicant->major);
    }

    public function test_applicant_major_resolves_to_a_real_program(): void
    {
        Program::factory()->create(['code' => 'EMNC3', 'name' => 'Events Management NC III']);

        $this->post('/apply', $this->baseApplicationData());

        $applicant = User::where('email', 'juan@example.com')->firstOrFail();
        $this->assertNotNull($applicant->program());
        $this->assertSame('EMNC3', $applicant->program()->code);
    }

    public function test_bachelor_program_major_is_stored_as_its_code(): void
    {
        $response = $this->post('/apply', $this->baseApplicationData([
            'program_key' => 'bsoa',
            'program_name' => 'Bachelor in Science Office Administration',
            'program_level' => 'BACHELOR',
        ]));

        $response->assertRedirect();
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();
        $this->assertSame('BSOA', $applicant->major);
    }
}
