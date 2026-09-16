<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearancePageDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearance_page_no_longer_carries_the_full_submission_history(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        // Two submissions: only the latest's filename should surface (via the
        // single `submission` field the registrar-hold banner still reads),
        // never a full history list — that now lives on the /documents page.
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'original_name' => 'older-upload.pdf', 'created_at' => now()->subDay(),
        ]);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'original_name' => 'latest-upload.pdf',
        ]);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk()
            ->assertSee('id="clearance-root"', false)
            ->assertDontSee('older-upload.pdf')
            ->assertDontSee('&quot;submissions&quot;', false)
            ->assertSee('&quot;documentsUrl&quot;', false);
    }
}
