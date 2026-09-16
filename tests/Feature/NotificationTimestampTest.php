<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\DocumentSubmission;
use App\Models\TransactionLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTimestampTest extends TestCase
{
    use RefreshDatabase;

    private function currentTermClearance(User $student): Clearance
    {
        return Clearance::create([
            'user_id' => $student->id,
            'school_year' => '2026-2027',
            'semester' => 1,
        ]);
    }

    public function test_document_review_notification_shows_the_real_review_time_not_a_generic_label(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->currentTermClearance($student);

        $reviewedAt = now()->subHours(3);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id,
            'status' => 'accepted',
            'reviewed_at' => $reviewedAt,
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $notif = $this->findNotif($response->getContent(), 'Document Accepted');
        $this->assertNotNull($notif, 'Expected a "Document Accepted" notification to be present.');
        $this->assertSame($reviewedAt->diffForHumans(), $notif['time']);
    }

    public function test_settled_payment_notification_shows_the_real_paid_time_not_a_generic_label(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->currentTermClearance($student);

        $paidAt = now()->subMinutes(20);
        TransactionLedger::factory()->create([
            'user_id' => $student->id,
            'gateway' => 'paymongo',
            'status' => 'Settled',
            'paid_at' => $paidAt,
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $notif = $this->findNotif($response->getContent(), 'Payment Received');
        $this->assertNotNull($notif, 'Expected a "Payment Received" notification to be present.');
        $this->assertSame($paidAt->diffForHumans(), $notif['time']);
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
