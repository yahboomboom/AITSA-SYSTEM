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
            'last_name' => 'Student',
            'first_name' => 'Walk In',
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
        $this->assertSame('Student, Walk In', $student->name);
        $this->assertNotNull(Clearance::where('user_id', $student->id)->first());
    }

    public function test_admin_can_register_a_walk_in_student_with_a_middle_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students/create', [
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'email' => 'walkin-middle@example.com',
            'login_id' => '2026-99998',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'major' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $student = User::where('login_id', '2026-99998')->firstOrFail();
        $this->assertSame('Dela Cruz, Juan Santos', $student->name);
    }

    public function test_walk_in_registration_requires_last_and_first_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from('/admin/students/create')->post('/admin/students/create', [
            'email' => 'no-name@example.com',
            'login_id' => '2026-99997',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'major' => 'BSIT',
            'year_level' => '1st Year',
        ])->assertSessionHasErrors(['last_name', 'first_name']);

        $this->assertNull(User::where('login_id', '2026-99997')->first());
    }

    public function test_walk_in_registration_rejects_a_non_numeric_contact_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from('/admin/students/create')->post('/admin/students/create', [
            'last_name' => 'Student',
            'first_name' => 'Walk In',
            'email' => 'bad-contact@example.com',
            'login_id' => '2026-99996',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'major' => 'BSIT',
            'year_level' => '1st Year',
            'contact_number' => '09XX-ABC',
        ])->assertSessionHasErrors('contact_number');
    }

    public function test_the_new_students_clearance_is_tagged_with_the_current_term(): void
    {
        \App\Models\Setting::put('school_year', '2027-2028');
        \App\Models\Setting::put('semester', '1');
        \App\Models\Setting::clearCache();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students/create', [
            'last_name' => 'Student',
            'first_name' => 'Walk In',
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
