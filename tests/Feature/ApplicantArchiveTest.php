<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_can_archive_a_stale_applicant(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant', 'name' => 'Stale Applicant']);

        $response = $this->actingAs($registrar)->post("/registrar/archive-applicant/{$applicant->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $applicant->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Applicant Archived']);
    }

    public function test_archived_applicants_no_longer_appear_in_the_pending_queue(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($registrar)->post("/registrar/archive-applicant/{$applicant->id}");

        $this->assertSame(0, User::where('role', 'applicant')->where('id', $applicant->id)->count());
    }

    public function test_cannot_archive_a_non_applicant_account(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($registrar)->post("/registrar/archive-applicant/{$student->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'deleted_at' => null]);
    }

    public function test_non_registrar_cannot_archive_applicants(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $applicant = User::factory()->create(['role' => 'applicant']);

        $response = $this->actingAs($student)->post("/registrar/archive-applicant/{$applicant->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $applicant->id, 'deleted_at' => null]);
    }
}
