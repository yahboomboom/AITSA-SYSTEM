<?php

namespace Tests\Unit;

use App\Mail\ApplicantAccountCreated;
use App\Models\Clearance;
use App\Models\User;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activates_an_applicant_into_a_student_account(): void
    {
        Mail::fake();

        $applicant = User::factory()->create([
            'role' => 'applicant',
            'login_id' => 'APPL-ABCDEFGH',
            'year_level' => null,
        ]);

        $result = (new AdmissionService())->activateStudentAccount($applicant);

        $applicant->refresh();
        $this->assertSame('student', $applicant->role);
        $this->assertSame('1st Year', $applicant->year_level);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{5}$/', $applicant->login_id);
        $this->assertSame($applicant->login_id, $result['login_id']);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($result['password'], $applicant->password));

        $this->assertNotNull(Clearance::where('user_id', $applicant->id)->first());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Student Account Auto-Created']);

        Mail::assertSent(ApplicantAccountCreated::class, function ($mail) use ($applicant) {
            return $mail->hasTo($applicant->email) && $mail->loginId === $applicant->login_id;
        });
    }

    public function test_preserves_a_pre_existing_year_level_instead_of_overwriting_it(): void
    {
        Mail::fake();

        $applicant = User::factory()->create(['role' => 'applicant', 'year_level' => '2nd Year']);

        (new AdmissionService())->activateStudentAccount($applicant);

        $this->assertSame('2nd Year', $applicant->fresh()->year_level);
    }

    public function test_is_a_no_op_for_a_non_applicant(): void
    {
        Mail::fake();

        $student = User::factory()->create(['role' => 'student', 'login_id' => 'EXISTING-1']);

        $result = (new AdmissionService())->activateStudentAccount($student);

        $this->assertNull($result);
        $this->assertSame('student', $student->fresh()->role);
        $this->assertSame('EXISTING-1', $student->fresh()->login_id);
        Mail::assertNothingSent();
    }

    public function test_generated_login_ids_do_not_collide(): void
    {
        Mail::fake();

        $applicants = User::factory()->count(5)->create(['role' => 'applicant']);
        $service = new AdmissionService();

        $loginIds = $applicants->map(fn ($a) => $service->activateStudentAccount($a)['login_id'])->all();

        $this->assertSame($loginIds, array_unique($loginIds));
    }

    public function test_the_new_clearance_is_tagged_with_the_current_term(): void
    {
        Mail::fake();
        \App\Models\Setting::put('school_year', '2027-2028');
        \App\Models\Setting::put('semester', '2');
        \App\Models\Setting::clearCache();

        $applicant = User::factory()->create(['role' => 'applicant']);

        (new AdmissionService())->activateStudentAccount($applicant);

        $clearance = Clearance::where('user_id', $applicant->id)->first();
        $this->assertSame('2027-2028', $clearance->school_year);
        $this->assertSame(2, $clearance->semester);
    }

    public function test_first_ever_clearance_is_auto_provisional(): void
    {
        Mail::fake();
        $applicant = User::factory()->create(['role' => 'applicant']);

        (new AdmissionService())->activateStudentAccount($applicant);

        $clearance = Clearance::where('user_id', $applicant->id)->first();
        $this->assertTrue($clearance->is_provisional);
    }

    public function test_a_second_clearance_row_is_not_auto_provisional(): void
    {
        Mail::fake();
        $applicant = User::factory()->create(['role' => 'applicant']);
        // Simulates a pre-existing clearance row, e.g. a returning/re-admitted student.
        Clearance::create([
            'user_id' => $applicant->id,
            'school_year' => '2025-2026',
            'semester' => 1,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        (new AdmissionService())->activateStudentAccount($applicant);

        $newClearance = Clearance::where('user_id', $applicant->id)
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->first();
        $this->assertFalse($newClearance->is_provisional);
    }
}
