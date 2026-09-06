<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarReportsIslandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
    }

    public function test_reports_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Report Test Student']);
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Approved',
            'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
        ]);

        $response = $this->actingAs($registrar)->get('/registrar/reports');

        $response->assertOk();
        $response->assertSee('id="registrar-reports-root"', false);
        $response->assertSee('Report Test Student');
        $response->assertSee('&quot;total&quot;:1', false);
        $response->assertSee('&quot;registrarSigned&quot;:0', false);
    }

    public function test_reports_page_does_not_list_a_students_clearance_from_a_past_term(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);

        $past = Clearance::initializeFor($student->id, '2026-2027', 1);
        Setting::put('semester', '2');
        Setting::clearCache();
        $current = Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($registrar)->get('/registrar/reports');

        $response->assertOk();
        $occurrences = substr_count($response->getContent(), '&quot;studentName&quot;:&quot;' . $student->name . '&quot;');
        $this->assertSame(1, $occurrences);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/reports')->assertRedirect();
    }

    public function test_non_registrar_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/reports')->assertForbidden();
    }
}
