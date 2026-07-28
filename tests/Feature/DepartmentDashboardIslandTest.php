<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_dashboard_renders_the_react_island_mount_point(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $response = $this->actingAs($officer)->get('/department/dashboard');

        $response->assertOk();
        $response->assertSee('id="department-dashboard-root"', false);
        $response->assertSee('data-context="[]"', false);
        $response->assertDontSee('No students in your queue.');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/department/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/department/dashboard')->assertForbidden();
    }

    public function test_dashboard_context_includes_all_three_statuses(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $approvedStudent = User::factory()->create(['role' => 'student', 'name' => 'Approved Student', 'login_id' => '2300901']);
        $approvedClearance = Clearance::create(['user_id' => $approvedStudent->id]);
        $approvedClearance->items()->create(['department_id' => $department->id, 'status' => 'Approved']);

        $holdStudent = User::factory()->create(['role' => 'student', 'name' => 'Hold Student', 'login_id' => '2300902']);
        $holdClearance = Clearance::create(['user_id' => $holdStudent->id]);
        $holdItem = $holdClearance->items()->create(['department_id' => $department->id, 'status' => 'Hold', 'remarks' => 'Missing borrowed book.']);

        $pendingStudent = User::factory()->create(['role' => 'student', 'name' => 'Pending Student', 'login_id' => '2300903']);
        $pendingClearance = Clearance::create(['user_id' => $pendingStudent->id]);
        $pendingClearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);

        $response = $this->actingAs($officer)->get('/department/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;status&quot;:&quot;Approved&quot;', false);
        $response->assertSee('&quot;status&quot;:&quot;Hold&quot;', false);
        $response->assertSee('&quot;status&quot;:&quot;Pending&quot;', false);
        $response->assertSee('&quot;remarks&quot;:&quot;Missing borrowed book.&quot;', false);

        // The approveUrl/holdUrl keys are a cross-file contract with the JSX
        // component, which reads row.approveUrl / row.holdUrl as form action
        // attributes. A key-name typo on either side would silently omit the
        // action, submitting to the current (GET-only) page and causing a 405.
        $expectedApproveUrl = str_replace('/', '\/', route('department.items.approve', $holdItem));
        $expectedHoldUrl = str_replace('/', '\/', route('department.items.hold', $holdItem));

        $response->assertSee('&quot;approveUrl&quot;:&quot;' . $expectedApproveUrl . '&quot;', false);
        $response->assertSee('&quot;holdUrl&quot;:&quot;' . $expectedHoldUrl . '&quot;', false);
    }
}
