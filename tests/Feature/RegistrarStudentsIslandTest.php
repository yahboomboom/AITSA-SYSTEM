<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\DocumentSubmission;
use App\Models\StudentGrade;
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

    public function test_latest_accepted_resubmission_clears_hold_status(): void
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
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Resubmitting');

        $response->assertOk()->assertJsonFragment(['adminStatus' => 'Pending', 'needsAttention' => false]);
    }

    public function test_rejected_latest_submission_needs_attention(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Held Registry Student']);
        DocumentSubmission::factory()->create(['user_id' => $student->id, 'status' => 'rejected']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Held');

        $response->assertOk()->assertJsonFragment(['adminStatus' => 'Pending', 'needsAttention' => true]);
    }

    public function test_pending_document_also_needs_attention(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Awaiting Review Student']);
        DocumentSubmission::factory()->create(['user_id' => $student->id, 'status' => 'pending']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Awaiting');

        $response->assertOk()->assertJsonFragment(['adminStatus' => 'Pending', 'needsAttention' => true]);
    }

    public function test_status_all_filter_shows_every_student_regardless_of_status(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $held = User::factory()->create(['role' => 'student', 'name' => 'All Filter Held Student']);
        $cleared = User::factory()->create(['role' => 'student', 'name' => 'All Filter Cleared Student']);
        $untouched = User::factory()->create(['role' => 'student', 'name' => 'All Filter Untouched Student']);
        DocumentSubmission::factory()->create(['user_id' => $held->id, 'status' => 'rejected']);
        Clearance::create([
            'user_id' => $cleared->id,
            'admission_status' => 'Approved', 'chair_status' => 'Approved',
            'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?status=all');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $held->id, 'adminStatus' => 'Pending', 'needsAttention' => true]);
        $response->assertJsonFragment(['id' => $cleared->id, 'adminStatus' => 'Cleared', 'needsAttention' => false]);
        $response->assertJsonFragment(['id' => $untouched->id, 'adminStatus' => 'Pending', 'needsAttention' => false]);
    }

    public function test_status_filter_finds_on_hold_students_without_a_text_query(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $held = User::factory()->create(['role' => 'student', 'name' => 'On Hold Student']);
        $cleared = User::factory()->create(['role' => 'student', 'name' => 'Cleared Student']);
        DocumentSubmission::factory()->create(['user_id' => $held->id, 'status' => 'rejected']);
        Clearance::create([
            'user_id' => $cleared->id,
            'admission_status' => 'Approved', 'chair_status' => 'Approved',
            'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?status=hold');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $held->id, 'adminStatus' => 'Pending', 'needsAttention' => true]);
        $response->assertDontSee('Cleared Student');
    }

    public function test_status_filter_hold_includes_students_with_pending_documents(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $pendingReview = User::factory()->create(['role' => 'student', 'name' => 'Pending Review Student']);
        DocumentSubmission::factory()->create(['user_id' => $pendingReview->id, 'status' => 'pending']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?status=hold');

        $response->assertOk()->assertJsonFragment(['id' => $pendingReview->id, 'adminStatus' => 'Pending', 'needsAttention' => true]);
    }

    public function test_status_filter_hold_includes_students_with_pending_account_clearance(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $pendingAccount = User::factory()->create(['role' => 'student', 'name' => 'Pending Account Student']);
        Clearance::create([
            'user_id' => $pendingAccount->id,
            'admission_status' => 'Approved',
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Pending',
        ]);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?status=hold');

        $response->assertOk()->assertJsonFragment(['id' => $pendingAccount->id, 'adminStatus' => 'Pending', 'needsAttention' => true]);
    }

    public function test_status_filter_finds_cleared_students(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $held = User::factory()->create(['role' => 'student', 'name' => 'On Hold Student']);
        $cleared = User::factory()->create(['role' => 'student', 'name' => 'Cleared Student']);
        DocumentSubmission::factory()->create(['user_id' => $held->id, 'status' => 'rejected']);
        Clearance::create([
            'user_id' => $cleared->id,
            'admission_status' => 'Approved', 'chair_status' => 'Approved',
            'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?status=cleared');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $cleared->id, 'adminStatus' => 'Cleared']);
        $response->assertDontSee('On Hold Student');
    }

    public function test_status_filter_combines_with_a_text_query(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $match = User::factory()->create(['role' => 'student', 'name' => 'Filterable Held Student']);
        $nonMatch = User::factory()->create(['role' => 'student', 'name' => 'Someone Else']);
        DocumentSubmission::factory()->create(['user_id' => $match->id, 'status' => 'rejected']);
        DocumentSubmission::factory()->create(['user_id' => $nonMatch->id, 'status' => 'rejected']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?q=Filterable&status=hold');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $match->id]);
        $response->assertDontSee('Someone Else');
    }

    public function test_status_filter_hold_excludes_students_with_no_document_issues(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        User::factory()->create(['role' => 'student', 'name' => 'No Issues Student']);

        $response = $this->actingAs($registrar)->getJson('/registrar/students/search?status=hold');

        $response->assertOk()->assertJsonCount(0, 'rows');
    }
}
