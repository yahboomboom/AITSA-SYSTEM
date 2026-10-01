<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Links that can't be embedded (Facebook posts, news articles) get their
 * preview picture (og:image) fetched once when posted and saved locally, so
 * the dashboard can show the picture in the zoom viewer.
 */
class AnnouncementLinkImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private const FB = 'https://www.facebook.com/share/p/19T3J9Hokb/';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        // Pretend every hostname resolves to a public IP (no real DNS in tests).
        $this->app->instance(\App\Services\LinkPreviewImageFetcher::class, new \App\Services\LinkPreviewImageFetcher(fn () => ['93.184.216.34']));
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function fakeFacebook(string $imageType = 'image/jpeg'): void
    {
        Http::fake([
            'www.facebook.com/*' => Http::response(
                '<html><head><meta property="og:image" content="https://scontent.xx.fbcdn.net/v/poster.jpg?oe=1" /></head></html>', 200),
            'scontent.xx.fbcdn.net/*' => Http::response('JPEGBYTES', 200, ['Content-Type' => $imageType]),
        ]);
    }

    private function postLink(string $url)
    {
        return $this->actingAs($this->admin)->post('/admin/announcements', ['title' => 'Alumni request', 'link_url' => $url]);
    }

    public function test_preview_picture_is_fetched_and_saved_for_a_facebook_link(): void
    {
        $this->fakeFacebook();

        $this->postLink(self::FB)->assertSessionHasNoErrors();

        $a = Announcement::where('title', 'Alumni request')->first();
        $this->assertNotNull($a->link_image_path);
        Storage::disk('local')->assertExists($a->link_image_path);
        $this->assertSame('JPEGBYTES', Storage::disk('local')->get($a->link_image_path));
        Http::assertSent(fn ($req) => str_contains($req->url(), 'facebook.com') && str_contains($req->header('User-Agent')[0] ?? '', 'facebookexternalhit'));
    }

    public function test_posting_still_works_when_the_site_cannot_be_reached(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->postLink(self::FB)->assertSessionHasNoErrors();

        $this->assertNull(Announcement::where('title', 'Alumni request')->value('link_image_path'));
    }

    public function test_non_image_responses_are_not_saved(): void
    {
        $this->fakeFacebook('text/html');

        $this->postLink(self::FB);

        $this->assertNull(Announcement::where('title', 'Alumni request')->value('link_image_path'));
    }

    public function test_private_and_local_addresses_are_never_fetched(): void
    {
        Http::fake();

        foreach (['http://127.0.0.1/admin', 'http://localhost/secret', 'http://10.0.0.5/x', 'http://192.168.1.1/'] as $url) {
            $this->postLink($url);
        }

        Http::assertNothingSent();
    }

    public function test_embeddable_links_are_not_fetched(): void
    {
        Http::fake();

        $this->postLink('https://youtu.be/dQw4w9WgXcQ');

        Http::assertNothingSent();
    }

    public function test_dashboard_gets_the_saved_picture_and_any_user_can_view_it(): void
    {
        $this->fakeFacebook();
        $this->postLink(self::FB);
        $a = Announcement::where('title', 'Alumni request')->first();

        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);

        $this->actingAs($student)->get('/dashboard')
            ->assertSee('&quot;linkImageUrl&quot;:&quot;' . str_replace('/', '\/', route('announcements.link-image', $a)), false);
        $this->actingAs($student)->get(route('announcements.link-image', $a))->assertOk();
    }

    public function test_guests_cannot_view_the_saved_picture(): void
    {
        $this->fakeFacebook();
        $this->postLink(self::FB);
        $a = Announcement::where('title', 'Alumni request')->first();
        auth()->logout();

        $this->get(route('announcements.link-image', $a))->assertRedirect();
    }

    public function test_deleting_the_announcement_removes_the_saved_picture(): void
    {
        $this->fakeFacebook();
        $this->postLink(self::FB);
        $a = Announcement::where('title', 'Alumni request')->first();
        $path = $a->link_image_path;

        $this->actingAs($this->admin)->post("/admin/announcements/{$a->id}/delete");

        Storage::disk('local')->assertMissing($path);
    }
}
