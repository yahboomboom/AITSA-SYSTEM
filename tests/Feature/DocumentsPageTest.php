<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/documents')->assertRedirect();
    }

    private const TRANSFEREE_ONLY_TYPES = ['transcript_of_records', 'honorable_dismissal'];

    public function test_page_lists_every_requirement_type_as_missing_by_default(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/documents');

        $response->assertOk()
            ->assertSee('id="documents-root"', false);

        foreach (array_diff(array_keys(DocumentSubmission::TYPES), self::TRANSFEREE_ONLY_TYPES) as $type) {
            $response->assertSee('&quot;type&quot;:&quot;' . $type . '&quot;', false);
        }
        $response->assertSee('&quot;status&quot;:&quot;missing&quot;', false);
    }

    public function test_transcript_and_honorable_dismissal_are_hidden_for_a_new_student(): void
    {
        $student = User::factory()->create(['role' => 'student', 'applicant_type' => 'NEW']);

        $response = $this->actingAs($student)->get('/documents');

        $response->assertOk();
        foreach (self::TRANSFEREE_ONLY_TYPES as $type) {
            $response->assertDontSee('&quot;type&quot;:&quot;' . $type . '&quot;', false);
        }
    }

    public function test_transcript_and_honorable_dismissal_show_for_a_transferee(): void
    {
        $student = User::factory()->create(['role' => 'student', 'applicant_type' => 'TRANSFEREE']);

        $response = $this->actingAs($student)->get('/documents');

        $response->assertOk();
        foreach (self::TRANSFEREE_ONLY_TYPES as $type) {
            $response->assertSee('&quot;type&quot;:&quot;' . $type . '&quot;', false);
        }
    }

    public function test_transcript_and_honorable_dismissal_show_for_a_returnee(): void
    {
        $student = User::factory()->create(['role' => 'student', 'applicant_type' => 'RETURNEE']);

        $response = $this->actingAs($student)->get('/documents');

        $response->assertOk();
        foreach (self::TRANSFEREE_ONLY_TYPES as $type) {
            $response->assertSee('&quot;type&quot;:&quot;' . $type . '&quot;', false);
        }
    }

    public function test_2x2_id_photo_is_a_selectable_requirement_type(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/documents')
            ->assertOk()
            ->assertSee('&quot;label&quot;:&quot;2x2 ID Photo&quot;', false);
    }

    public function test_page_reflects_latest_submission_status_per_type(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'document_type' => 'form137', 'status' => 'accepted',
        ]);

        $this->actingAs($student)->get('/documents')
            ->assertOk()
            ->assertSee('&quot;type&quot;:&quot;form137&quot;', false)
            ->assertSee('&quot;status&quot;:&quot;accepted&quot;', false);
    }

    public function test_page_sees_own_submissions_with_status_and_remarks(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'original_name' => 'my-form137.pdf',
            'status' => 'rejected', 'remarks' => 'Scan is blurry.',
        ]);
        DocumentSubmission::factory()->create(['original_name' => 'someone-elses.pdf']);

        $response = $this->actingAs($student)->get('/documents');

        $response->assertOk()
            ->assertSee('"originalName":"my-form137.pdf"')
            ->assertSee('"remarks":"Scan is blurry."')
            ->assertDontSee('someone-elses.pdf');
    }

    public function test_resubmission_replaces_the_previous_rejected_document_in_the_student_view(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'document_type' => 'form137',
            'original_name' => 'old-form137.pdf',
            'status' => 'rejected',
            'created_at' => now()->subMinute(),
        ]);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'document_type' => 'form137',
            'original_name' => 'new-form137.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($student)->get('/documents')
            ->assertOk()
            ->assertSee('new-form137.pdf')
            ->assertDontSee('old-form137.pdf');
    }
}
