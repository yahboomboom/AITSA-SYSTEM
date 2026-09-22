<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationClickThroughTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_notification_links_to_the_page_it_describes(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'chair_status' => 'Hold', 'remarks' => 'Missing requirement.',
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $notif = $this->findNotif($response->getContent(), 'Chair Clearance On Hold');
        $this->assertNotNull($notif, 'Expected a "Chair Clearance On Hold" notification to be present.');
        $this->assertSame(route('clearance'), $notif['url'] ?? null);
    }

    public function test_admin_notification_for_pending_applications_links_to_the_student_registry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'applicant']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $notif = $this->findNotif($response->getContent(), 'Applications Still Pending');
        $this->assertNotNull($notif, 'Expected an "Applications Still Pending" notification to be present.');
        $this->assertSame(route('admin.students.index'), $notif['url'] ?? null);
    }

    private function findNotif(string $html, string $title): ?array
    {
        preg_match('/const NOTIFS\s*=\s*(\[.*?\]);/s', $html, $matches);
        $this->assertNotEmpty($matches, 'Could not locate the NOTIFS array in the rendered page.');

        $notifs = json_decode($matches[1], true);

        foreach ($notifs as $notif) {
            if ($notif['title'] === $title) {
                return $notif;
            }
        }

        return null;
    }
}
