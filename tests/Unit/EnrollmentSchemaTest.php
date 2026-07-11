<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_enrollment_per_student_per_term(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Enrollment::factory()->create(['user_id' => $user->id, 'school_year' => '2026-2027', 'semester' => 1]);

        $this->expectException(QueryException::class);
        Enrollment::factory()->create(['user_id' => $user->id, 'school_year' => '2026-2027', 'semester' => 1]);
    }

    public function test_rejected_enrollments_do_not_consume_seats(): void
    {
        $section = Section::factory()->create(['capacity' => 10]);
        $active = Enrollment::factory()->create(['status' => 'enrolled']);
        $rejected = Enrollment::factory()->create(['status' => 'rejected']);
        $active->sections()->attach($section->id);
        $rejected->sections()->attach($section->id);

        $this->assertSame(1, $section->enrolledCount());
        $this->assertSame(9, $section->seatsLeft());
    }

    public function test_setting_get_and_put(): void
    {
        $this->assertNull(Setting::get('school_year'));
        Setting::put('school_year', '2026-2027');
        Setting::put('school_year', '2027-2028');
        $this->assertSame('2027-2028', Setting::get('school_year'));
    }

    public function test_user_student_helpers(): void
    {
        $program = Program::factory()->create(['code' => 'BSOA']);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '2nd Year']);
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'OA101', 'status' => 'Passed']);
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'OA102', 'status' => 'Failed']);

        $this->assertTrue($user->isIrregularStudent());
        $this->assertSame(2, $user->yearNumber());
        $this->assertTrue($user->program()->is($program));
        $this->assertSame(['OA101'], $user->passedSubjectCodes());
    }
}
