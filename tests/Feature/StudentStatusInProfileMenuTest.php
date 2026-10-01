<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The student's "Year · Sem · Enrollment status" line sits under their name
 * inside the profile button, not as a separate block beside it.
 */
class StudentStatusInProfileMenuTest extends TestCase
{
    use RefreshDatabase;

    /** The profile button's markup, from its opening tag to the avatar. */
    private function profileButton(string $html): string
    {
        $start = strpos($html, 'id="profileMenuWrap"');
        $this->assertNotFalse($start, 'profile menu not rendered');
        $end = strpos($html, '</button>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_status_line_renders_under_the_student_name_on_every_student_page(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'name' => 'Dela Cruz, Juan', 'year_level' => '1st Year', 'major' => 'BSOA',
        ]);
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);

        foreach (['/dashboard', '/clearance', '/documents', '/enrollment', '/ledger', '/cor'] as $url) {
            $html = $this->actingAs($student)->get($url)->assertOk()->getContent();
            $button = $this->profileButton($html);

            $namePos = strpos($button, 'Dela Cruz, Juan');
            $statusPos = strpos($button, 'Not Enrolled');
            $this->assertNotFalse($statusPos, "$url: status missing from the profile button");
            $this->assertGreaterThan($namePos, $statusPos, "$url: status should come after the name");
            $this->assertStringContainsString('BSOA', $button, "$url: program should still show");
            $this->assertStringContainsString('1st Year', $button, "$url: year level should show");
            $this->assertSame(1, substr_count($html, 'Not Enrolled'), "$url: status rendered more than once");
        }
    }

    public function test_staff_profile_menu_has_no_student_status(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $html = $this->actingAs($registrar)->get('/registrar/dashboard')->getContent();

        $this->assertStringNotContainsString('Not Enrolled', $this->profileButton($html));
    }
}
