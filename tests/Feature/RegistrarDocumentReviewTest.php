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

    public function test_dashboard_lists_submissions(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $response = $this->actingAs($this->registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $this->assertTrue($response->viewData('documentSubmissions')->contains('id', $sub->id));
        $response->assertSee('id="registrar-dashboard-root"', false);
        $response->assertSee('&quot;originalName&quot;:&quot;' . $sub->original_name . '&quot;', false);
    }

    public function test_registrar_can_accept(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/accept")
            ->assertRedirect(route('registrar.dashboard'));

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
            ->assertRedirect(route('registrar.dashboard'));

        $clearance->refresh();
        $this->assertSame('Approved', $clearance->registrar_status);
        $this->assertNull($clearance->remarks);
    }

    public function test_reject_requires_remarks(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $this->actingAs($this->registrar)
            ->from('/registrar/dashboard')
            ->post("/registrar/documents/{$sub->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/reject", ['remarks' => 'Scan is unreadable, please re-upload.'])
            ->assertRedirect(route('registrar.dashboard'));

        $sub->refresh();
        $this->assertSame('rejected', $sub->status);
        $this->assertSame('Scan is unreadable, please re-upload.', $sub->remarks);
    }

    public function test_reviewed_submission_cannot_be_re_reviewed(): void
    {
        $sub = DocumentSubmission::factory()->create(['status' => 'accepted']);

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/reject", ['remarks' => 'Changed my mind.'])
            ->assertRedirect(route('registrar.dashboard'));

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
