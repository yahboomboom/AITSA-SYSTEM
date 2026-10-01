<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Announcements are title-only now (no details text) with an optional file
 * and/or link. Links are embedded in the dashboard viewer when the site
 * allows it (YouTube, Google Drive/Docs, direct PDFs and images), otherwise
 * shown as a link card.
 */
class AnnouncementLinksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_post_with_just_a_title(): void
    {
        $this->actingAs($this->admin)->post('/admin/announcements', ['title' => 'Classes suspended tomorrow'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', ['title' => 'Classes suspended tomorrow', 'body' => null, 'link_url' => null]);
    }

    public function test_admin_can_post_with_a_link(): void
    {
        $this->actingAs($this->admin)->post('/admin/announcements', [
            'title' => 'Orientation video', 'link_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', ['title' => 'Orientation video', 'link_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
    }

    public function test_link_must_be_a_web_address(): void
    {
        foreach (['javascript:alert(1)', 'ftp://files.example.com/a.pdf', 'not a link'] as $bad) {
            $this->actingAs($this->admin)->post('/admin/announcements', ['title' => 'Bad', 'link_url' => $bad])
                ->assertSessionHasErrors('link_url');
        }
        $this->assertDatabaseMissing('announcements', ['title' => 'Bad']);
    }

    /** @dataProvider links */
    public function test_link_preview_picks_embed_image_or_card(string $url, string $type, ?string $src): void
    {
        $preview = (new Announcement(['link_url' => $url]))->linkPreview();

        $this->assertSame($type, $preview['type']);
        $this->assertSame($src, $preview['src']);
    }

    public static function links(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s', 'embed', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', 'embed', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtube shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'embed', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'drive file' => ['https://drive.google.com/file/d/1AbC_dEf-123/view?usp=sharing', 'embed', 'https://drive.google.com/file/d/1AbC_dEf-123/preview'],
            'google doc' => ['https://docs.google.com/document/d/1XyZ-789/edit', 'embed', 'https://docs.google.com/document/d/1XyZ-789/preview'],
            'google slides' => ['https://docs.google.com/presentation/d/1Sl-ide/edit#slide=id.p', 'embed', 'https://docs.google.com/presentation/d/1Sl-ide/preview'],
            'direct pdf' => ['https://aitsa.edu.ph/files/memo.pdf', 'embed', 'https://aitsa.edu.ph/files/memo.pdf'],
            'direct image' => ['https://aitsa.edu.ph/img/poster.JPG?v=2', 'image', 'https://aitsa.edu.ph/img/poster.JPG?v=2'],
            'facebook post' => ['https://www.facebook.com/aitsa/posts/123', 'card', null],
        ];
    }

    public function test_dashboard_sends_title_and_link_preview_but_no_details(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);
        Announcement::create([
            'title' => 'Orientation video', 'body' => 'Old details text', 'posted_by' => $this->admin->id, 'is_active' => true,
            'link_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);

        $html = $this->actingAs($student)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Orientation video', $html);
        $this->assertStringContainsString('&quot;linkType&quot;:&quot;embed&quot;', $html);
        $this->assertStringContainsString('youtube.com\/embed\/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('&quot;linkHost&quot;:&quot;youtu.be&quot;', $html);
        $this->assertStringNotContainsString('Old details text', $html);
    }
}
