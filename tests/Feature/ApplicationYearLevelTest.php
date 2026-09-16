<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Transferees/returnees don't start at 1st Year — but the apply form never
 * asked, so AdmissionService::activateStudentAccount() defaulted everyone to
 * 1st Year on activation. This covers collecting the real year level at
 * apply-time for those applicant types.
 */
class ApplicationYearLevelTest extends TestCase
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
            'program_key' => 'bsoa',
            'program_name' => 'Bachelor in Science Office Administration',
            'program_level' => 'BACHELOR',
        ], $overrides);
    }

    public function test_a_transferee_applications_chosen_year_level_is_saved_on_the_applicant(): void
    {
        $response = $this->post('/apply', $this->baseApplicationData([
            'applicant_type' => 'TRANSFEREE',
            'year_level' => '3rd Year',
        ]));

        $response->assertRedirect();
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();
        $this->assertSame('3rd Year', $applicant->year_level);
    }

    public function test_a_new_applicant_does_not_need_a_year_level(): void
    {
        $response = $this->post('/apply', $this->baseApplicationData(['applicant_type' => 'NEW']));

        $response->assertRedirect();
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();
        $this->assertNull($applicant->year_level);
    }

    public function test_a_returnee_must_pick_a_year_level(): void
    {
        $response = $this->post('/apply', $this->baseApplicationData(['applicant_type' => 'RETURNEE']));

        $response->assertSessionHasErrors('year_level');
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }
}
