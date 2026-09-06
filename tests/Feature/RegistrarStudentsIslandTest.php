<?php

namespace Tests\Feature;

use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarStudentsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $regular = User::factory()->create(['role' => 'student', 'name' => 'Regular Registry Student']);
        $irregular = User::factory()->create(['role' => 'student', 'name' => 'Irregular Registry Student']);
        StudentGrade::create(['user_id' => $irregular->id, 'subject_code' => 'CC 101', 'status' => 'Failed', 'final_grade' => '60']);

        $response = $this->actingAs($registrar)->get('/registrar/students');

        $response->assertOk();
        $response->assertSee('id="registrar-students-root"', false);
        $response->assertSee('Regular Registry Student');
        $response->assertSee('Irregular Registry Student');
        $response->assertSee('&quot;isIrregular&quot;:true', false);
        $response->assertDontSee('Manage Grades');
    }

    public function test_students_page_handles_no_accounts_yet(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($registrar)->get('/registrar/students');

        $response->assertOk();
        $response->assertSee('&quot;rows&quot;:[]', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/students')->assertRedirect();
    }

    public function test_non_registrar_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/students')->assertForbidden();
    }
}
