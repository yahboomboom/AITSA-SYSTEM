<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Notification text names the current school year, never a hardcoded one. */
class NotificationSchoolYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_admin_notifications_use_the_current_school_year(): void
    {
        Setting::put('school_year', '2031-2032');

        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA']);
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2031-2032', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Approved',
            'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([[$student, '/dashboard'], [$admin, '/admin/dashboard']] as [$user, $url]) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('2025', $html, "$url still shows a hardcoded school year");
            $this->assertStringContainsString('A.Y. 2031-2032', $html, "$url should name the current school year");
        }
    }
}
