<?php

namespace Tests\Unit;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_relations_defaults_and_type_label(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $sub = DocumentSubmission::factory()->create(['user_id' => $student->id, 'document_type' => 'form137']);

        $this->assertTrue($sub->user->is($student));
        $this->assertSame('pending', $sub->status);
        $this->assertSame('Form 137 — Permanent Record / Senior HS Report Card', $sub->typeLabel());
        $this->assertTrue($student->documentSubmissions()->whereKey($sub->id)->exists());

        $sub->update(['status' => 'accepted', 'reviewed_by' => $registrar->id, 'reviewed_at' => now()]);
        $this->assertTrue($sub->fresh()->reviewer->is($registrar));
        $this->assertNotNull($sub->fresh()->reviewed_at);
    }

    public function test_type_label_covers_all_modal_options(): void
    {
        $expected = [
            'form137' => 'Form 137 — Permanent Record / Senior HS Report Card',
            'form138' => 'Form 138 — Report Card',
            'birth_cert' => 'PSA Birth Certificate',
            'good_moral' => 'Certificate of Good Moral Character',
            'id_photo_2x2' => '2x2 ID Photo',
            'other' => 'Other Supporting Document',
        ];

        foreach ($expected as $key => $label) {
            $sub = DocumentSubmission::factory()->make(['document_type' => $key]);
            $this->assertSame($label, $sub->typeLabel());
        }
    }
}
