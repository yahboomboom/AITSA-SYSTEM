<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarDocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_page_renders_the_react_island_mount_point_with_real_counts(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        DocumentSubmission::factory()->create(['status' => 'pending']);
        DocumentSubmission::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($registrar)->get('/registrar/documents');

        $response->assertOk();
        $response->assertSee('id="registrar-documents-root"', false);
        $response->assertSee('&quot;documentsPendingCount&quot;:1', false);
        $response->assertSee('&quot;documentsRejectedCount&quot;:1', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/documents')->assertRedirect();
    }

    public function test_non_registrar_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/documents')->assertForbidden();
    }
}
