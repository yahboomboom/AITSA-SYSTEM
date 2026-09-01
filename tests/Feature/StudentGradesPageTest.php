<?php

namespace Tests\Feature;

use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentGradesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_their_own_grades(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        StudentGrade::create(['user_id' => $student->id, 'subject_code' => 'CC101', 'status' => 'Passed', 'final_grade' => '90']);
        StudentGrade::create(['user_id' => $student->id, 'subject_code' => 'CC102', 'status' => 'Failed', 'final_grade' => '60']);

        $response = $this->actingAs($student)->get('/grades');

        $response->assertOk();
        $response->assertSee('CC101');
        $response->assertSee('CC102');
        $response->assertSee('90');
        $response->assertSee('Passed');
        $response->assertSee('Failed');
    }

    public function test_student_does_not_see_another_students_grades(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        StudentGrade::create(['user_id' => $other->id, 'subject_code' => 'CC999', 'status' => 'Passed', 'final_grade' => '99']);

        $response = $this->actingAs($student)->get('/grades');

        $response->assertOk();
        $response->assertDontSee('CC999');
    }

    public function test_grades_page_handles_no_grades_yet(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/grades');

        $response->assertOk();
    }
}
