<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalkInStudentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_a_walk_in_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/students/create', [
            'name' => 'Walk In Student',
            'email' => 'walkin@example.com',
            'login_id' => '2026-99999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'major' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $response->assertRedirect(route('admin.students.create'));
        $student = User::where('login_id', '2026-99999')->first();
        $this->assertNotNull($student);
        $this->assertSame('student', $student->role);
        $this->assertNotNull(Clearance::where('user_id', $student->id)->first());
    }

    public function test_the_new_students_clearance_is_tagged_with_the_current_term(): void
    {
        \App\Models\Setting::put('school_year', '2027-2028');
        \App\Models\Setting::put('semester', '1');
        \App\Models\Setting::clearCache();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students/create', [
            'name' => 'Walk In Student',
            'email' => 'walkin-term-test@example.com',
            'login_id' => '2027-11111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'major' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $student = User::where('login_id', '2027-11111')->firstOrFail();
        $clearance = Clearance::where('user_id', $student->id)->first();
        $this->assertSame('2027-2028', $clearance->school_year);
        $this->assertSame(1, $clearance->semester);
    }

    public function test_admin_dashboard_no_longer_lists_a_verified_applicants_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Registrar-Verified Applicants');
    }
}
