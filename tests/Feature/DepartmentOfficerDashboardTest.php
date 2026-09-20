<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentOfficerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_only_lists_items_for_the_current_terms_clearance(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 1);

        Setting::put('semester', '2');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($officer)->get('/department/dashboard');

        $response->assertOk();
        $response->assertViewHas('items', fn ($items) => $items->where('clearance.user_id', $student->id)->count() === 1);
    }

    public function test_officer_sees_only_their_own_department_queue(): void
    {
        $deptA = Department::factory()->create(['name' => 'Library']);
        $deptB = Department::factory()->create(['name' => 'Clinic']);
        $officerA = User::factory()->create(['role' => 'department_officer', 'department_id' => $deptA->id]);

        $studentA = User::factory()->create(['role' => 'student', 'name' => 'Student A']);
        $clearanceA = Clearance::create(['user_id' => $studentA->id]);
        $clearanceA->items()->create(['department_id' => $deptA->id, 'status' => 'Pending']);

        $studentB = User::factory()->create(['role' => 'student', 'name' => 'Student B']);
        $clearanceB = Clearance::create(['user_id' => $studentB->id]);
        $clearanceB->items()->create(['department_id' => $deptB->id, 'status' => 'Pending']);

        $response = $this->actingAs($officerA)->get('/department/dashboard');

        $response->assertOk()->assertSee('Student A')->assertDontSee('Student B');
    }

    public function test_officer_can_approve_an_item(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending', 'remarks' => 'old note']);

        $this->actingAs($officer)
            ->post("/department/items/{$item->id}/approve")
            ->assertRedirect(route('department.dashboard'));

        $item->refresh();
        $this->assertSame('Approved', $item->status);
        $this->assertNull($item->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clearance Signed']);
    }

    public function test_officer_hold_requires_remarks(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);

        $this->actingAs($officer)->from('/department/dashboard')
            ->post("/department/items/{$item->id}/hold", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($officer)
            ->post("/department/items/{$item->id}/hold", ['remarks' => 'Missing borrowed book.'])
            ->assertRedirect(route('department.dashboard'));

        $item->refresh();
        $this->assertSame('Hold', $item->status);
        $this->assertSame('Missing borrowed book.', $item->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clearance Held']);
    }

    public function test_officer_cannot_act_on_another_departments_item(): void
    {
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();
        $officerA = User::factory()->create(['role' => 'department_officer', 'department_id' => $deptA->id]);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $item = $clearance->items()->create(['department_id' => $deptB->id, 'status' => 'Pending']);

        $this->actingAs($officerA)->post("/department/items/{$item->id}/approve")->assertForbidden();

        $this->assertSame('Pending', $item->fresh()->status);
    }

    public function test_dashboard_excludes_items_for_a_soft_deleted_student(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Deleted Student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);
        $student->delete();

        $response = $this->actingAs($officer)->get('/department/dashboard');

        $response->assertOk();
        $response->assertDontSee('Deleted Student');
        $response->assertDontSee('Unknown');
        $response->assertViewHas('items', fn ($items) => $items->count() === 0);
    }

    public function test_non_officer_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/department/dashboard')->assertForbidden();
    }
}
