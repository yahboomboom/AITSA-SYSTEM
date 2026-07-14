<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_a_department(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/departments', ['name' => 'Library'])
            ->assertRedirect(route('admin.departments'));

        $this->assertDatabaseHas('departments', ['name' => 'Library', 'code' => 'library', 'is_active' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Department Created']);
    }

    public function test_duplicate_department_name_is_rejected(): void
    {
        Department::factory()->create(['name' => 'Library']);

        $this->actingAs($this->admin)->from('/admin/departments')
            ->post('/admin/departments', ['name' => 'Library'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_toggle_department_active_state(): void
    {
        $department = Department::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->post("/admin/departments/{$department->id}/toggle")
            ->assertRedirect(route('admin.departments'));

        $this->assertFalse((bool) $department->fresh()->is_active);
    }

    public function test_admin_can_create_a_department_officer_account(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->admin)
            ->post("/admin/departments/{$department->id}/officers", ['name' => 'Lib Officer', 'login_id' => 'lib01'])
            ->assertRedirect(route('admin.departments'));

        $officer = User::where('login_id', 'lib01')->first();
        $this->assertNotNull($officer);
        $this->assertSame('department_officer', $officer->role);
        $this->assertSame($department->id, $officer->department_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Department Officer Created']);
    }

    public function test_cannot_create_officer_for_inactive_department(): void
    {
        $department = Department::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin)
            ->post("/admin/departments/{$department->id}/officers", ['name' => 'X', 'login_id' => 'x01'])
            ->assertRedirect(route('admin.departments'));

        $this->assertNull(User::where('login_id', 'x01')->first());
    }

    public function test_non_admin_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/departments')->assertForbidden();
        $this->actingAs($student)->post('/admin/departments', ['name' => 'X'])->assertForbidden();
    }
}
