<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignatureEditTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSignatureDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    public function test_authenticated_user_can_view_the_signature_page(): void
    {
        $user = User::factory()->create(['signature_path' => null]);

        $response = $this->actingAs($user)->get('/my-signature');

        $response->assertOk();
        $response->assertSee('id="signature-root"', false);
        $response->assertSee('&quot;hasSignature&quot;:false', false);
    }

    public function test_page_reflects_an_existing_signature(): void
    {
        $user = User::factory()->create(['signature_path' => 'signatures/existing.png']);

        $response = $this->actingAs($user)->get('/my-signature');

        $response->assertOk();
        $response->assertSee('&quot;hasSignature&quot;:true', false);
    }

    public function test_user_can_save_a_drawn_signature(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['signature_path' => null]);

        $response = $this->actingAs($user)->post('/my-signature', ['signature' => $this->fakeSignatureDataUrl()]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->signature_path);
        Storage::disk('public')->assertExists($user->fresh()->signature_path);
    }

    public function test_saving_a_new_signature_replaces_and_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('signatures/old.png', 'fake-old-bytes');
        $user = User::factory()->create(['signature_path' => 'signatures/old.png']);

        $this->actingAs($user)->post('/my-signature', ['signature' => $this->fakeSignatureDataUrl()]);

        Storage::disk('public')->assertMissing('signatures/old.png');
        $this->assertNotSame('signatures/old.png', $user->fresh()->signature_path);
    }

    public function test_empty_signature_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['signature_path' => null]);

        $response = $this->actingAs($user)->post('/my-signature', ['signature' => '']);

        $response->assertSessionHasErrors('signature');
        $this->assertNull($user->fresh()->signature_path);
    }

    public function test_malformed_signature_data_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['signature_path' => null]);

        $response = $this->actingAs($user)->post('/my-signature', ['signature' => 'data:image/png;base64,not-real-png-bytes']);

        $response->assertSessionHasErrors('signature');
        $this->assertNull($user->fresh()->signature_path);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/my-signature')->assertRedirect();
    }
}
