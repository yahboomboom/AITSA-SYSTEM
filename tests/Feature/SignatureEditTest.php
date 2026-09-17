<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignatureEditTest extends TestCase
{
    use RefreshDatabase;

    // A real 1x1 transparent PNG — the "image" validation rule reads actual
    // image headers via getimagesize(), so a plain fake-bytes file won't
    // pass it. UploadedFile::fake()->image() needs the GD extension (not
    // installed in this dev environment), so real bytes avoid that too.
    private function fakePng(string $name = 'signature.png'): UploadedFile
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $bytes);
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

    public function test_user_can_upload_a_signature(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['signature_path' => null]);
        $file = $this->fakePng();

        $response = $this->actingAs($user)->post('/my-signature', ['signature' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->signature_path);
        Storage::disk('public')->assertExists($user->fresh()->signature_path);
    }

    public function test_uploading_a_new_signature_replaces_and_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $old = $this->fakePng('old.png')->store('signatures', 'public');
        $user = User::factory()->create(['signature_path' => $old]);
        $file = $this->fakePng('new.png');

        $this->actingAs($user)->post('/my-signature', ['signature' => $file]);

        Storage::disk('public')->assertMissing($old);
        $this->assertNotSame($old, $user->fresh()->signature_path);
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['signature_path' => null]);
        $file = UploadedFile::fake()->create('not-an-image.pdf', 100);

        $response = $this->actingAs($user)->post('/my-signature', ['signature' => $file]);

        $response->assertSessionHasErrors('signature');
        $this->assertNull($user->fresh()->signature_path);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/my-signature')->assertRedirect();
    }
}
