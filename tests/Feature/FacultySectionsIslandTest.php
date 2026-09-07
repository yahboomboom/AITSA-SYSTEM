<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultySectionsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::clearCache();

        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        Section::factory()->create([
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
            'school_year' => '2026-2027',
            'block_label' => 'A',
        ]);

        $response = $this->actingAs($faculty)->get('/faculty/sections');

        $response->assertOk();
        $response->assertSee('id="faculty-sections-root"', false);
        $response->assertSee('CC101');
    }

    public function test_sections_page_handles_no_assigned_sections(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $response = $this->actingAs($faculty)->get('/faculty/sections');

        $response->assertOk();
        $response->assertSee('id="faculty-sections-root" data-context="[]"', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/faculty/sections')->assertRedirect();
    }

    public function test_non_faculty_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/faculty/sections')->assertForbidden();
    }
}
