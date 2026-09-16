<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartNewSemesterTest extends TestCase
{
    use RefreshDatabase;

    private function collegeStudent(string $email): User
    {
        Program::factory()->create(['code' => 'BSOA', 'level' => 'bachelor', 'is_enrollable' => true]);

        return User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'email' => $email]);
    }

    private function tesdaStudent(string $email): User
    {
        Program::factory()->create(['code' => 'BK3', 'level' => 'tesda', 'is_enrollable' => false]);

        return User::factory()->create(['role' => 'student', 'major' => 'BK3', 'email' => $email]);
    }

    public function test_registrar_can_start_a_new_semester_for_college_students(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->collegeStudent('college-term-test@example.com');
        Clearance::initializeFor($student->id, '2026-2027', 1, ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']);

        $response = $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ]);

        $response->assertRedirect();
        $this->assertSame('2', Setting::get('semester'));

        $newClearance = Clearance::where('user_id', $student->id)->where('semester', 2)->first();
        $this->assertNotNull($newClearance);
        $this->assertSame('Pending', $newClearance->chair_status);
        $this->assertSame('Pending', $newClearance->cashier_status);
        $this->assertSame('Pending', $newClearance->registrar_status);
        $this->assertFalse($newClearance->is_provisional);
        $this->assertDatabaseHas('audit_logs', ['action' => 'New Term Started']);
    }

    public function test_tesda_students_are_not_touched(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $tesdaStudent = $this->tesdaStudent('tesda-term-test@example.com');
        Clearance::initializeFor($tesdaStudent->id, '2026-2027', 1);

        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ]);

        $this->assertSame(1, Clearance::where('user_id', $tesdaStudent->id)->count());
    }

    public function test_rejects_a_no_op_call_with_the_same_term(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 1,
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame('1', Setting::get('semester'));
    }

    public function test_admission_role_cannot_start_a_new_semester(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);

        $this->actingAs($admission)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ])->assertForbidden();
    }

    public function test_student_cannot_start_a_new_semester(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ])->assertForbidden();
    }
}
