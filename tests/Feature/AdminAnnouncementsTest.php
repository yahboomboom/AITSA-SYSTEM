<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_announcements_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        Announcement::create(['title' => 'Library extended hours', 'body' => 'Open until 8PM.', 'posted_by' => $this->admin->id, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get('/admin/announcements');

        $response->assertOk();
        $response->assertSee('id="admin-announcements-root"', false);
        $response->assertSee('Library extended hours');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/announcements')->assertRedirect();
    }

    public function test_admin_can_create_an_announcement(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/announcements', ['title' => 'Enrollment period open'])
            ->assertRedirect(route('admin.announcements'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Enrollment period open',
            'posted_by' => $this->admin->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Announcement Posted']);
    }

    public function test_title_is_required(): void
    {
        $this->actingAs($this->admin)->from('/admin/announcements')
            ->post('/admin/announcements', ['title' => '', 'body' => 'Body only.'])
            ->assertSessionHasErrors('title');
    }

    public function test_admin_can_delete_an_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Old notice', 'body' => 'Stale.', 'posted_by' => $this->admin->id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/announcements/{$announcement->id}/delete")
            ->assertRedirect(route('admin.announcements'));

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/announcements')->assertForbidden();
        $this->actingAs($student)->post('/admin/announcements', ['title' => 'X', 'body' => 'Y'])->assertForbidden();
    }

    public function test_admin_can_post_an_announcement_with_an_image_attachment(): void
    {
        Storage::fake('local');
        $image = UploadedFile::fake()->create('poster.jpg', 50, 'image/jpeg');

        $this->actingAs($this->admin)
            ->post('/admin/announcements', ['title' => 'Photo day', 'body' => 'Smile!', 'attachment' => $image])
            ->assertRedirect(route('admin.announcements'));

        $announcement = Announcement::where('title', 'Photo day')->firstOrFail();
        $this->assertSame('poster.jpg', $announcement->attachment_name);
        Storage::disk('local')->assertExists($announcement->attachment_path);
    }

    public function test_admin_can_post_an_announcement_with_a_pdf_attachment(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('handbook.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->post('/admin/announcements', ['title' => 'Handbook', 'body' => 'Read this.', 'attachment' => $pdf])
            ->assertRedirect(route('admin.announcements'));

        $announcement = Announcement::where('title', 'Handbook')->firstOrFail();
        $this->assertSame('handbook.pdf', $announcement->attachment_name);
        Storage::disk('local')->assertExists($announcement->attachment_path);
    }

    public function test_attachment_must_be_an_allowed_file_type(): void
    {
        Storage::fake('local');
        $script = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

        $this->actingAs($this->admin)->from('/admin/announcements')
            ->post('/admin/announcements', ['title' => 'Bad file', 'body' => 'Nope.', 'attachment' => $script])
            ->assertSessionHasErrors('attachment');
    }

    public function test_deleting_an_announcement_removes_its_stored_attachment(): void
    {
        Storage::fake('local');
        $image = UploadedFile::fake()->create('old.jpg', 50, 'image/jpeg');
        $this->actingAs($this->admin)->post('/admin/announcements', ['title' => 'Old', 'body' => 'B', 'attachment' => $image]);
        $announcement = Announcement::where('title', 'Old')->firstOrFail();
        $path = $announcement->attachment_path;

        $this->actingAs($this->admin)->post("/admin/announcements/{$announcement->id}/delete");

        Storage::disk('local')->assertMissing($path);
    }

    public function test_any_authenticated_user_can_view_an_announcement_attachment(): void
    {
        Storage::fake('local');
        $image = UploadedFile::fake()->create('flyer.jpg', 50, 'image/jpeg');
        $this->actingAs($this->admin)->post('/admin/announcements', ['title' => 'Flyer', 'body' => 'B', 'attachment' => $image]);
        $announcement = Announcement::where('title', 'Flyer')->firstOrFail();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get("/announcements/{$announcement->id}/attachment")->assertOk();
    }

    public function test_attachment_route_404s_when_announcement_has_none(): void
    {
        $announcement = Announcement::create(['title' => 'No file', 'body' => 'B', 'posted_by' => $this->admin->id, 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get("/announcements/{$announcement->id}/attachment")->assertNotFound();
    }

    public function test_guest_cannot_view_an_announcement_attachment(): void
    {
        $announcement = Announcement::create(['title' => 'No file', 'body' => 'B', 'posted_by' => $this->admin->id, 'is_active' => true]);

        $this->get("/announcements/{$announcement->id}/attachment")->assertRedirect();
    }
}
