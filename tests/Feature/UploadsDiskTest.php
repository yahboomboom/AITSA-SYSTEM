<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Clearance;
use App\Models\DocumentSubmission;
use App\Models\EnrollmentAgreement;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * UPLOADS_DISK=s3 (production) must move every upload off the container disk,
 * which Railway wipes on each redeploy.
 */
class UploadsDiskTest extends TestCase
{
    use RefreshDatabase;

    // 1x1 PNG, so the tests don't need the GD extension.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.uploads_disk' => 's3', 'filesystems.signatures_disk' => 's3']);
        Storage::fake('s3');
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_documents_are_read_from_the_uploads_disk(): void
    {
        Storage::disk('s3')->put('documents/test.pdf', '%PDF-1.4 fake');
        $sub = DocumentSubmission::factory()->create(['file_path' => 'documents/test.pdf', 'original_name' => 'form137.pdf']);

        $this->actingAs($sub->user)->get("/documents/{$sub->id}")->assertOk();
    }

    public function test_announcement_attachment_is_stored_and_served_from_the_uploads_disk(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/announcements', [
            'title' => 'Exam schedule',
            'attachment' => UploadedFile::fake()->create('schedule.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $announcement = Announcement::firstOrFail();
        Storage::disk('s3')->assertExists($announcement->attachment_path);
        Storage::disk('local')->assertMissing($announcement->attachment_path);

        $this->actingAs($admin)->get("/announcements/{$announcement->id}/attachment")->assertOk();
    }

    public function test_saved_signature_goes_to_the_uploads_disk_and_shows_on_the_page(): void
    {
        $user = User::factory()->create(['role' => 'registrar']);
        $png = self::PNG;

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('signature.update'), ['signature' => 'data:image/png;base64,' . $png]);

        $path = $user->fresh()->signature_path;
        $this->assertNotNull($path);
        Storage::disk('s3')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringStartsWith('data:image/png;base64,', Uploads::signatureDataUri($path));
    }

    public function test_agreement_and_clearance_pdfs_render_with_signatures_on_the_uploads_disk(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('dompdf needs the GD extension to draw PNG signatures.');
        }

        $png = base64_decode(self::PNG);
        Storage::disk('s3')->put('agreement-signatures/a.png', $png);
        Storage::disk('s3')->put('signatures/r.png', $png);

        $student = User::factory()->create(['role' => 'student']);
        EnrollmentAgreement::create([
            'user_id' => $student->id, 'signature_path' => 'agreement-signatures/a.png',
            'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'agreement_hash' => str_repeat('a', 64), 'signed_at' => now(),
        ]);
        $this->actingAs($student)->get('/my-agreement')->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $registrar = User::factory()->create(['role' => 'registrar', 'signature_path' => 'signatures/r.png']);
        $clearance = Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'registrar_signed_by' => $registrar->id,
        ]);
        $this->actingAs($student)->get("/clearance/{$clearance->id}/print")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_missing_signature_file_does_not_break_rendering(): void
    {
        $this->assertNull(Uploads::signatureDataUri('signatures/gone.png'));
        $this->assertNull(Uploads::signatureDataUri(null));
    }
}
