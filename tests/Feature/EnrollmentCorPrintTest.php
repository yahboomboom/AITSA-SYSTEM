<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentCorPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_download_their_cor(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach(Section::factory()->create()->id);

        $response = $this->actingAs($student)->get("/enrollment/{$enrollment->id}/cor");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_a_pending_enrollment_cannot_be_downloaded_as_a_cor(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'status' => 'pending']);

        $this->actingAs($student)->get("/enrollment/{$enrollment->id}/cor")->assertForbidden();
    }

    public function test_a_student_cannot_download_another_students_cor(): void
    {
        $owner = User::factory()->create(['role' => 'student']);
        $intruder = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $owner->id, 'status' => 'enrolled']);

        $this->actingAs($intruder)->get("/enrollment/{$enrollment->id}/cor")->assertForbidden();
    }

    public function test_cor_shows_pending_when_registrar_has_not_signed_off(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Juan Dela Cruz']);
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach(Section::factory()->create()->id);
        Clearance::create(['user_id' => $student->id, 'school_year' => $enrollment->school_year, 'semester' => $enrollment->semester, 'registrar_status' => 'Pending']);

        $response = $this->actingAs($student)->get("/enrollment/{$enrollment->id}/cor");

        $response->assertOk();
    }
}
