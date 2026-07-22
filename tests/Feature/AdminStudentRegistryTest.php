<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentRegistryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_index_lists_only_student_accounts(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'Alice Student']);
        User::factory()->create(['role' => 'chair', 'name' => 'Bob Chair']);
        User::factory()->create(['role' => 'admin', 'name' => 'Carol Admin']);

        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee('Alice Student');
        $response->assertDontSee('Bob Chair');
        $response->assertDontSee('Carol Admin');
    }

    public function test_index_excludes_soft_deleted_students(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Deleted Student']);
        $student->delete();

        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertDontSee('Deleted Student');
    }

    public function test_index_search_matches_name_or_login_id(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'Zed Zephyr', 'login_id' => '2026-99001']);
        User::factory()->create(['role' => 'student', 'name' => 'Other Person', 'login_id' => '2026-99002']);

        $byName = $this->actingAs($this->admin)->get('/admin/students?q=Zephyr');
        $byName->assertSee('Zed Zephyr');
        $byName->assertDontSee('Other Person');

        $byId = $this->actingAs($this->admin)->get('/admin/students?q=99002');
        $byId->assertSee('Other Person');
        $byId->assertDontSee('Zed Zephyr');
    }

    public function test_index_filters_by_program_and_year_level(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'BSOA First Year', 'major' => 'BSOA', 'year_level' => '1st Year']);
        User::factory()->create(['role' => 'student', 'name' => 'BSIT Second Year', 'major' => 'BSIT', 'year_level' => '2nd Year']);

        $response = $this->actingAs($this->admin)->get('/admin/students?program=BSOA');
        $response->assertSee('BSOA First Year');
        $response->assertDontSee('BSIT Second Year');

        $response = $this->actingAs($this->admin)->get('/admin/students?year_level=2nd+Year');
        $response->assertSee('BSIT Second Year');
        $response->assertDontSee('BSOA First Year');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/students')->assertForbidden();
    }
}
