<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarDocumentReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = User::factory()->create(['role' => 'registrar']);
    }

    public function test_documents_page_does_not_preload_document_submissions(): void
    {
        $sub = DocumentSubmission::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->get('/registrar/documents');

        $response->assertOk();
        $response->assertSee('id="registrar-documents-root"', false);
        $response->assertDontSee($sub->original_name);
        $response->assertSee('&quot;documentsPendingCount&quot;:1', false);
    }

    public function test_documents_search_finds_matching_submission_by_student_name(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Searchable Student']);
        $sub = DocumentSubmission::factory()->create(['user_id' => $student->id, 'status' => 'pending']);

        $response = $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->getJson('/registrar/documents/search?q=Searchable');

        $response->assertOk()->assertJsonFragment(['id' => $sub->id, 'originalName' => $sub->original_name]);
    }

    public function test_documents_search_requires_a_query(): void
    {
        DocumentSubmission::factory()->create(['status' => 'pending']);

        $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->getJson('/registrar/documents/search')
            ->assertOk()->assertJsonCount(0, 'documents');
    }

    public function test_documents_search_shows_only_the_latest_submission_for_a_document_type(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Resubmitter']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'document_type' => 'form137',
            'original_name' => 'rejected-form137.pdf',
            'status' => 'rejected',
            'created_at' => now()->subMinute(),
        ]);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'document_type' => 'form137',
            'original_name' => 'new-form137.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->getJson('/registrar/documents/search?q=Resubmitter');

        $response->assertOk()
            ->assertJsonFragment(['originalName' => 'new-form137.pdf'])
            ->assertJsonMissing(['originalName' => 'rejected-form137.pdf']);
    }

    public function test_documents_search_hides_accepted_documents_unless_viewing_all(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Cleared Student']);
        $accepted = DocumentSubmission::factory()->create(['user_id' => $student->id, 'status' => 'accepted']);

        $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->getJson('/registrar/documents/search?q=Cleared')
            ->assertOk()->assertJsonCount(0, 'documents');

        $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->getJson('/registrar/documents/search?q=Cleared&all=1')
            ->assertOk()->assertJsonFragment(['id' => $accepted->id]);
    }

    public function test_students_cannot_search_documents(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->getJson('/registrar/documents/search?q=a')->assertForbidden();
    }

    public function test_pending_status_filter_works_without_a_text_query(): void
    {
        $pending = DocumentSubmission::factory()->create(['status' => 'pending']);
        $rejected = DocumentSubmission::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($this->registrar)->withSession(['auth.password_confirmed_at' => time()])->getJson('/registrar/documents/search?status=pending');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $pending->id]);
        $response->assertJsonMissing(['id' => $rejected->id]);
    }

    public function test_registrar_can_accept(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/accept")
            ->assertRedirect(route('registrar.documents'));

        $sub->refresh();
        $this->assertSame('accepted', $sub->status);
        $this->assertSame($this->registrar->id, $sub->reviewed_by);
        $this->assertNotNull($sub->reviewed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Document Reviewed']);
    }

    public function test_accepting_document_also_approves_registrar_clearance(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Hold',
            'remarks' => 'Missing Form 137.',
        ]);
        $sub = DocumentSubmission::factory()->create(['user_id' => $student->id]);

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/accept")
            ->assertRedirect(route('registrar.documents'));

        $clearance->refresh();
        $this->assertSame('Approved', $clearance->registrar_status);
        $this->assertNull($clearance->remarks);
    }

    public function test_reject_requires_remarks(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $this->actingAs($this->registrar)
            ->from('/registrar/documents')
            ->post("/registrar/documents/{$sub->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/reject", ['remarks' => 'Scan is unreadable, please re-upload.'])
            ->assertRedirect(route('registrar.documents'));

        $sub->refresh();
        $this->assertSame('rejected', $sub->status);
        $this->assertSame('Scan is unreadable, please re-upload.', $sub->remarks);
    }

    public function test_reviewed_submission_cannot_be_re_reviewed(): void
    {
        $sub = DocumentSubmission::factory()->create(['status' => 'accepted']);

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/reject", ['remarks' => 'Changed my mind.'])
            ->assertRedirect(route('registrar.documents'));

        $this->assertSame('accepted', $sub->fresh()->status);
    }

    public function test_accepting_a_document_approves_only_the_current_terms_registrar_status(): void
    {
        $registrar = \App\Models\User::factory()->create(['role' => 'registrar']);
        $student = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\Setting::put('school_year', '2026-2027');
        \App\Models\Setting::put('semester', '1');
        \App\Models\Setting::clearCache();
        $past = \App\Models\Clearance::initializeFor($student->id, '2026-2027', 1, ['registrar_status' => 'Pending']);

        \App\Models\Setting::put('semester', '2');
        \App\Models\Setting::clearCache();
        $current = \App\Models\Clearance::initializeFor($student->id, '2026-2027', 2, ['registrar_status' => 'Pending']);

        $submission = \App\Models\DocumentSubmission::factory()->create(['user_id' => $student->id]);

        $this->actingAs($registrar)->post("/registrar/documents/{$submission->id}/accept");

        $this->assertSame('Pending', $past->fresh()->registrar_status);
        $this->assertSame('Approved', $current->fresh()->registrar_status);
    }

    public function test_students_cannot_review(): void
    {
        $sub = DocumentSubmission::factory()->create();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post("/registrar/documents/{$sub->id}/accept")
            ->assertForbidden();
    }
}
