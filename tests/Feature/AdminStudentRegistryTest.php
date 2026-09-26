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

        $response = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students');

        $response->assertOk();
        $response->assertSee('Alice Student');
        $response->assertDontSee('Bob Chair');
        $response->assertDontSee('Carol Admin');
    }

    public function test_index_excludes_soft_deleted_students(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Deleted Student']);
        $student->delete();

        $response = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students');

        $response->assertDontSee('Deleted Student');
    }

    public function test_index_search_matches_name_or_login_id(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'Zed Zephyr', 'login_id' => '2026-99001']);
        User::factory()->create(['role' => 'student', 'name' => 'Other Person', 'login_id' => '2026-99002']);

        $byName = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students?q=Zephyr');
        $byName->assertSee('Zed Zephyr');
        $byName->assertDontSee('Other Person');

        $byId = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students?q=99002');
        $byId->assertSee('Other Person');
        $byId->assertDontSee('Zed Zephyr');
    }

    public function test_index_filters_by_program_and_year_level(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'BSOA First Year', 'major' => 'BSOA', 'year_level' => '1st Year']);
        User::factory()->create(['role' => 'student', 'name' => 'BSIT Second Year', 'major' => 'BSIT', 'year_level' => '2nd Year']);

        $response = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students?program=BSOA');
        $response->assertSee('BSOA First Year');
        $response->assertDontSee('BSIT Second Year');

        $response = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students?year_level=2nd+Year');
        $response->assertSee('BSIT Second Year');
        $response->assertDontSee('BSOA First Year');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/students')->assertForbidden();
    }

    public function test_admin_can_soft_delete_a_student(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'To Be Deleted']);

        $this->actingAs($this->admin)
            ->delete("/admin/students/{$student->id}")
            ->assertRedirect(route('admin.students.index'));

        $this->assertNotNull($student->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Student Account Deleted']);
    }

    public function test_destroy_returns_404_for_non_student_account(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($this->admin)
            ->delete("/admin/students/{$chair->id}")
            ->assertNotFound();

        $this->assertNull($chair->fresh()->deleted_at);
    }

    public function test_delete_confirmation_data_escapes_a_js_breakout_name(): void
    {
        $maliciousName = "Mallory');window.__xssFired=1;//";
        $student = User::factory()->create(['role' => 'student', 'name' => $maliciousName]);

        $response = $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/students');

        $response->assertOk();

        // The delete flow is React state driven (the student's name reaches the page only
        // via the JSON-encoded data-context attribute, never an inline onclick string), so
        // there is no JS-breakout vector to test for directly. Assert the raw breakout
        // sequence is not present unescaped in the rendered HTML, and that the name survives
        // intact inside the HTML-escaped JSON attribute (Blade's {{ }} escapes it for us).
        $response->assertDontSee("Mallory');window.__xssFired=1;", false);
        $response->assertSee(e(json_encode($maliciousName)), false);
    }

    public function test_non_admin_cannot_delete_a_student(): void
    {
        $requester = User::factory()->create(['role' => 'student']);
        $target = User::factory()->create(['role' => 'student']);

        $this->actingAs($requester)
            ->delete("/admin/students/{$target->id}")
            ->assertForbidden();

        $this->assertNull($target->fresh()->deleted_at);
    }
}
