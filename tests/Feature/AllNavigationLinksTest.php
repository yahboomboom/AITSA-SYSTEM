<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllNavigationLinksTest extends TestCase
{
    use RefreshDatabase;

    private function assertAllOk(User $user, array $urls): void
    {
        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);
            $this->assertTrue(
                $response->status() < 400,
                "GET {$url} as role \"{$user->role}\" returned {$response->status()}, expected < 400."
            );
        }
    }

    public function test_every_student_nav_link_loads(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->assertAllOk($student, [
            '/dashboard',
            '/documents',
            '/clearance',
            '/enrollment',
            '/grades',
            '/ledger',
            '/cor',
            '/profile',
            '/my-signature',
        ]);
    }

    public function test_every_admin_nav_link_loads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertAllOk($admin, [
            '/admin/dashboard',
            '/admin/departments',
            '/admin/students',
            '/admin/students/create',
            '/admin/audit',
        ]);
    }

    public function test_admission_role_can_access_the_shared_registrar_dashboard(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);

        $this->assertAllOk($admission, [
            '/registrar/dashboard',
        ]);
    }

    public function test_every_registrar_nav_link_loads(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->assertAllOk($registrar, [
            '/registrar/dashboard',
            '/registrar/documents',
            '/registrar/students',
            '/registrar/students/search',
            '/registrar/reports',
            '/registrar/slots',
            '/registrar/documents/search',
        ]);
    }

    public function test_every_cashier_nav_link_loads(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->assertAllOk($cashier, [
            '/cashier/dashboard',
            '/cashier/accounts',
            '/cashier/transactions',
            '/cashier/billing',
        ]);
    }

    public function test_every_chair_nav_link_loads(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->assertAllOk($chair, [
            '/approver/dashboard',
            '/approver/scheduling',
            '/approver/curriculum',
        ]);
    }

    public function test_every_faculty_nav_link_loads(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->assertAllOk($faculty, [
            '/faculty/schedule',
            '/faculty/sections',
        ]);
    }

    public function test_faculty_section_grades_link_loads(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create();
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);

        $this->assertAllOk($faculty, [
            "/faculty/sections/{$section->id}/grades",
        ]);
    }

    public function test_every_department_officer_nav_link_loads(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $this->assertAllOk($officer, [
            '/department/dashboard',
        ]);
    }

    public function test_clearance_print_link_loads_for_a_fully_cleared_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'school_year' => '2026-2027',
            'semester' => 1,
            'admission_status' => 'Approved',
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($student)->get("/clearance/{$clearance->id}/print");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
