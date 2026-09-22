<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Curriculum editing moved from Admin to Registrar to the Dept Chair —
        // the property is kept as $admin only to minimize churn across this file.
        $this->admin = User::factory()->create(['role' => 'chair']);
    }

    public function test_students_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->getJson('/api/admin/programs')->assertForbidden();
    }

    public function test_admin_role_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->getJson('/api/admin/programs')->assertForbidden();
    }

    public function test_admin_lists_programs_and_subjects(): void
    {
        $program = Program::factory()->create(['code' => 'BSOA']);
        $subject = Subject::factory()->for($program)->create();
        Section::factory()->for($subject)->create();

        $this->actingAs($this->admin)->getJson('/api/admin/programs')
            ->assertOk()->assertJsonPath('programs.0.code', 'BSOA');

        $this->actingAs($this->admin)->getJson("/api/admin/programs/{$program->id}/subjects")
            ->assertOk()
            ->assertJsonPath('subjects.0.id', $subject->id)
            ->assertJsonCount(1, 'subjects.0.sections');
    }

    public function test_admin_creates_subject_with_prerequisites(): void
    {
        $program = Program::factory()->create();
        $prereq = Subject::factory()->for($program)->create();

        $this->actingAs($this->admin)->postJson('/api/admin/subjects', [
            'program_id' => $program->id, 'code' => 'OA201', 'title' => 'Advanced Office Procedures',
            'units' => 3, 'year_level' => 2, 'semester' => 1, 'mode' => 'F2F',
            'prerequisite_ids' => [$prereq->id],
        ])->assertCreated();

        $subject = Subject::where('code', 'OA201')->first();
        $this->assertTrue($subject->prerequisites->first()->is($prereq));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Curriculum Updated']);
    }

    public function test_subject_with_enrollments_cannot_be_deleted(): void
    {
        $section = Section::factory()->create();
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/subjects/{$section->subject_id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('subjects', ['id' => $section->subject_id]);
    }

    public function test_registrar_is_forbidden_from_the_curriculum_api(): void
    {
        // Curriculum content (Programs/Subjects) moved from Registrar to the
        // Dept Chair — see tests/Feature/Api/SectionConflictTest.php for the
        // chair-side section CRUD coverage that used to live here.
        $program = Program::factory()->create();
        $subject = Subject::factory()->for($program)->create();
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->getJson('/api/admin/programs')->assertForbidden();
        $this->actingAs($registrar)->postJson('/api/admin/subjects', [
            'program_id' => $program->id, 'code' => 'OA201', 'title' => 'X',
            'units' => 3, 'year_level' => 1, 'semester' => 1, 'mode' => 'F2F',
        ])->assertForbidden();
        $this->actingAs($registrar)->postJson('/api/admin/sections', [
            'subject_id' => $subject->id, 'block_label' => 'A', 'days' => ['M', 'W'],
            'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Rm 101',
            'professor' => 'J. Dela Cruz', 'capacity' => 40, 'school_year' => '2026-2027',
            'delivery_mode' => 'Face-to-Face',
        ])->assertForbidden();
    }
}
