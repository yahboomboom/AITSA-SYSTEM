<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        // signature_path is required before a student may submit a document
        // (see routes/web.php's documents.submitRequirement guard).
        return User::factory()->create(['role' => 'student', 'signature_path' => 'signatures/fake.png']);
    }

    public function test_student_can_submit_a_document(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->student())->post('/documents/submit-requirement', [
            'document' => UploadedFile::fake()->create('form137.pdf', 500, 'application/pdf'),
            'document_type' => 'form137',
            'notes' => 'Certified true copy attached.',
        ]);

        $response->assertRedirect(route('documents'));
        $response->assertSessionHas('success');

        $sub = DocumentSubmission::first();
        $this->assertNotNull($sub);
        $this->assertSame('form137', $sub->document_type);
        $this->assertSame('form137.pdf', $sub->original_name);
        $this->assertSame('pending', $sub->status);
        Storage::disk('local')->assertExists($sub->file_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Document Submitted']);
    }

    public function test_student_can_submit_a_2x2_id_photo(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->student())->post('/documents/submit-requirement', [
            'document' => UploadedFile::fake()->create('id-photo.jpg', 200, 'image/jpeg'),
            'document_type' => 'id_photo_2x2',
        ]);

        $response->assertRedirect(route('documents'));
        $this->assertSame('id_photo_2x2', DocumentSubmission::first()->document_type);
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->student())->post('/documents/submit-requirement', [
            'document' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
            'document_type' => 'form137',
        ])->assertSessionHasErrors('document');

        $this->assertSame(0, DocumentSubmission::count());
    }

    public function test_wrong_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->student())->post('/documents/submit-requirement', [
            'document' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
            'document_type' => 'form137',
        ])->assertSessionHasErrors('document');
    }

    public function test_unknown_document_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->student())->post('/documents/submit-requirement', [
            'document' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            'document_type' => 'diploma',
        ])->assertSessionHasErrors('document_type');
    }

    public function test_guest_cannot_submit(): void
    {
        Storage::fake('local');

        $this->post('/documents/submit-requirement', [
            'document' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            'document_type' => 'form137',
        ])->assertRedirect();

        $this->assertSame(0, DocumentSubmission::count());
    }
}
