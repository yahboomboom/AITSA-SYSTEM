<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultySchedulePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_sees_own_sections_only(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $other = User::factory()->create(['role' => 'faculty']);
        $mine = Subject::factory()->create(['code' => 'MINE101']);
        $theirs = Subject::factory()->create(['code' => 'THEIRS101']);
        Section::factory()->create(['subject_id' => $mine->id, 'faculty_id' => $prof->id]);
        Section::factory()->create(['subject_id' => $theirs->id, 'faculty_id' => $other->id, 'days' => ['T']]);

        $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()
            ->assertSee('&quot;subjectCode&quot;:&quot;MINE101&quot;', false)
            ->assertDontSee('THEIRS101');
    }

    public function test_context_is_empty_without_teaching_load(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()
            ->assertSee('&quot;days&quot;:[]', false);
    }

    public function test_students_cannot_view_faculty_schedule(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/faculty/schedule')->assertForbidden();
    }

    public function test_faculty_cannot_access_admin_api(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($prof)->getJson('/api/admin/programs')->assertForbidden();
    }
}
