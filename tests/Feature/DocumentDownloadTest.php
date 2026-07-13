<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function submissionWithFile(): DocumentSubmission
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.pdf', '%PDF-1.4 fake');

        return DocumentSubmission::factory()->create([
            'file_path' => 'documents/test.pdf',
            'original_name' => 'form137-scan.pdf',
        ]);
    }

    public function test_owner_can_view_their_file(): void
    {
        $sub = $this->submissionWithFile();

        $response = $this->actingAs($sub->user)->get("/documents/{$sub->id}");

        $response->assertOk();
        $this->assertStringContainsString('form137-scan.pdf', $response->headers->get('content-disposition'));
    }

    public function test_registrar_can_view_any_file(): void
    {
        $sub = $this->submissionWithFile();
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->get("/documents/{$sub->id}")->assertOk();
    }

    public function test_other_students_are_forbidden(): void
    {
        $sub = $this->submissionWithFile();
        $other = User::factory()->create(['role' => 'student']);

        $this->actingAs($other)->get("/documents/{$sub->id}")->assertForbidden();
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::fake('local');
        $sub = DocumentSubmission::factory()->create(['file_path' => 'documents/gone.pdf']);

        $this->actingAs($sub->user)->get("/documents/{$sub->id}")->assertNotFound();
    }

    public function test_guest_is_redirected(): void
    {
        $sub = $this->submissionWithFile();

        $this->get("/documents/{$sub->id}")->assertRedirect();
    }
}
