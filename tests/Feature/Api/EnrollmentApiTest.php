<?php

namespace Tests\Feature\Api;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearedStudent(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(
            ['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year'],
            $overrides
        ));
        Clearance::create([
            'user_id' => $user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);

        return $user;
    }

    private function makeBlockSection(): Section
    {
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1]);

        return Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
    }

    public function test_context_for_regular_student_includes_block(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $this->makeBlockSection();

        $this->actingAs($user)->getJson('/api/enrollment/context')
            ->assertOk()
            ->assertJsonPath('student.type', 'regular')
            ->assertJsonPath('clearance_complete', true)
            ->assertJsonPath('block.label', 'A')
            ->assertJsonPath('catalogue', null)
            ->assertJsonPath('enrollment', null);
    }

    public function test_context_for_irregular_student_includes_catalogue(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'ZZ999', 'status' => 'Failed']);
        $this->makeBlockSection();

        $this->actingAs($user)->getJson('/api/enrollment/context')
            ->assertOk()
            ->assertJsonPath('student.type', 'irregular')
            ->assertJsonPath('block', null)
            ->assertJsonCount(1, 'catalogue');
    }

    public function test_regular_store_enrolls_immediately(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $this->makeBlockSection();

        $this->actingAs($user)->postJson('/api/enrollment', [])
            ->assertCreated()
            ->assertJsonPath('enrollment.status', 'enrolled')
            ->assertJsonPath('enrollment.block_label', 'A');
    }

    public function test_incomplete_clearance_is_a_409_with_message(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create(['user_id' => $user->id, 'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending']);

        $this->actingAs($user)->postJson('/api/enrollment', [])
            ->assertStatus(409)
            ->assertJsonStructure(['message']);
    }

    public function test_staff_roles_are_forbidden(): void
    {
        $this->seed(ProgramSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson('/api/enrollment/context')->assertForbidden();
    }
}
