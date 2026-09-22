<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->post('/admin/announcements', ['title' => 'Enrollment period open', 'body' => 'Enroll now for A.Y. 2026-2027.'])
            ->assertRedirect(route('admin.announcements'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Enrollment period open',
            'body' => 'Enroll now for A.Y. 2026-2027.',
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
}
