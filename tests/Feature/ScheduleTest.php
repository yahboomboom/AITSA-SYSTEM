<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_schedule_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/cor');

        $response->assertOk();
        $response->assertSee('id="schedule-root"', false);
        $response->assertDontSee('Certificate of Registration');
        $response->assertDontSee('Weekly Timetable');
        $response->assertDontSee('Schedule QR Code');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/cor')->assertRedirect();
    }

    public function test_current_pending_enrollment_schedule_is_visible(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA']);
        $program = Program::where('code', 'BSOA')->firstOrFail();
        $subject = Subject::factory()->for($program)->create([
            'code' => 'BSOA101',
            'title' => 'Foundations of Office Administration',
            'semester' => 1,
            'year_level' => 1,
        ]);
        $section = Section::factory()->for($subject)->create();
        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'status' => 'pending',
        ]);
        $enrollment->sections()->attach($section);

        $this->actingAs($student)->get('/cor')
            ->assertOk()
            ->assertSee('BSOA101');
    }
}
