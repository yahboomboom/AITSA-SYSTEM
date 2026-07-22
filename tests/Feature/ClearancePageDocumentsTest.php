<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearancePageDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_own_submissions_with_status_and_remarks(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'original_name' => 'my-form137.pdf',
            'status' => 'rejected', 'remarks' => 'Scan is blurry.',
        ]);
        DocumentSubmission::factory()->create(['original_name' => 'someone-elses.pdf']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk()
            ->assertSee('id="clearance-root"', false)
            ->assertSee('"originalName":"my-form137.pdf"')
            ->assertSee('"remarks":"Scan is blurry."')
            ->assertDontSee('someone-elses.pdf');
    }

    public function test_page_hides_history_section_when_no_submissions(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"submissions":[]');
    }
}
