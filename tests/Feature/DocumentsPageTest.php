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

    public function test_page_lists_every_requirement_type_as_missing_by_default(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/documents');

        $response->assertOk()
            ->assertSee('id="documents-root"', false);

        foreach (array_keys(DocumentSubmission::TYPES) as $type) {
            $response->assertSee('&quot;type&quot;:&quot;' . $type . '&quot;', false);
        }
        $response->assertSee('&quot;status&quot;:&quot;missing&quot;', false);
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
}
