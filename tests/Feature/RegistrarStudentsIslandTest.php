<?php

namespace Tests\Feature;

use App\Models\StudentGrade;
use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarStudentsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_page_renders_the_react_island_mount_point_without_preloading_students(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        User::factory()->create(['role' => 'student', 'name' => 'Regular Registry Student']);
        $response = $this->actingAs($registrar)->get('/registrar/students');

        $response->assertOk();
        $response->assertSee('id="registrar-students-root"', false);
        $response->assertSee('&quot;rows&quot;:[]', false);
        $response->assertDontSee('Regular Registry Student');
        $response->assertDontSee('Manage Grades');
    }

    public function test_students_page_handles_no_accounts_yet(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($registrar)->get('/registrar/students');

        $response->assertOk();
        $response->assertSee('&quot;rows&quot;:[]', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/students')->assertRedirect();
    }

    public function test_non_registrar_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/students')->assertForbidden();
    }

    public function test_students_search_finds_matching_students_with_irregular_status(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $regular = User::factory()->create(['role' => 'student', 'name' => 'Regular Registry Student']);
        $irregular = User::factory()->create(['role' => 'student', 'name' => 'Irregular Registry Student']);
        StudentGrade::create(['user_id' => $irregular->id, 'subject_code' => 'CC 101', 'status' => 'Failed', 'final_grade' => '60']);
        User::factory()->create(['role' => 'student', 'name' => 'Unrelated Person']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Registry');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $regular->id, 'isIrregular' => false]);
        $response->assertJsonFragment(['id' => $irregular->id, 'isIrregular' => true]);
        $response->assertDontSee('Unrelated Person');
    }

    public function test_students_search_requires_a_query(): void
    {
        User::factory()->create(['role' => 'student']);
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->getJson('/registrar/students/search')
            ->assertOk()->assertJsonCount(0, 'rows');
    }

    public function test_non_registrar_roles_cannot_search_students(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->getJson('/registrar/students/search?q=a')->assertForbidden();
    }

    public function test_latest_resubmission_determines_student_status(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Resubmitting Student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'document_type' => 'form137',
            'status' => 'rejected',
            'created_at' => now()->subMinute(),
        ]);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'document_type' => 'form137',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Resubmitting');

        $response->assertOk()->assertJsonFragment(['adminStatus' => 'Pending']);
        $response->assertJsonMissing(['adminStatus' => 'Hold']);
    }

    public function test_rejected_latest_submission_holds_student_status(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Held Registry Student']);
        DocumentSubmission::factory()->create(['user_id' => $student->id, 'status' => 'rejected']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Held');

        $response->assertOk()->assertJsonFragment(['adminStatus' => 'Hold']);
    }
}
