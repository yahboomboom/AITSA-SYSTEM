<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_dashboard_renders_real_data_not_fake_placeholders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'chair', 'name' => 'Real Chair Name', 'email' => 'realchair@example.test', 'login_id' => 'realchair01']);
        User::factory()->create(['role' => 'cashier', 'name' => 'Real Cashier Name', 'email' => 'realcashier@example.test', 'login_id' => 'realcashier01']);
        User::factory()->create(['role' => 'registrar', 'name' => 'Real Registrar Name', 'email' => 'realregistrar@example.test', 'login_id' => 'realregistrar01']);

        // Non-core roles must never appear in the accounts table.
        User::factory()->create(['role' => 'faculty', 'name' => 'Should Not Appear Faculty']);
        User::factory()->create(['role' => 'department_officer', 'name' => 'Should Not Appear Officer']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');

        // No active departments seeded yet, so Clearance::allItemsApproved()
        // is trivially true (empty items collection) — only the three legacy status
        // columns determine settled-vs-pending here.
        $clearedStudent = User::factory()->create(['role' => 'student']);
        Clearance::initializeFor($clearedStudent->id, '2026-2027', 1, [
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);

        $pendingStudent = User::factory()->create(['role' => 'student']);
        Clearance::initializeFor($pendingStudent->id, '2026-2027', 1, [
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);

        // Out-of-term clearance: fully approved on the legacy statuses, but a
        // different school year. If term-scoping were broken, this would wrongly
        // count toward clearancesSettled.
        $otherTermStudent = User::factory()->create(['role' => 'student']);
        Clearance::initializeFor($otherTermStudent->id, '2025-2026', 1, [
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);

        // Department created only now, so it did not exist when the clearances
        // above were initialized — it won't retroactively add items to them.
        // A new clearance initialized after this point will get an auto-created
        // Pending ClearanceItem for it, exercising allItemsApproved()'s
        // non-trivial (items-not-empty) branch.
        Department::factory()->create(['is_active' => true]);

        $itemPendingStudent = User::factory()->create(['role' => 'student']);
        Clearance::initializeFor($itemPendingStudent->id, '2026-2027', 1, [
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('id="admin-dashboard-root"', false);

        // Real staff data present.
        $response->assertSee('Real Chair Name');
        $response->assertSee('realcashier01');
        $response->assertSee('realregistrar@example.test');

        // Non-core roles excluded from the accounts table.
        $response->assertDontSee('Should Not Appear Faculty');
        $response->assertDontSee('Should Not Appear Officer');

        // Old fake placeholders gone.
        $response->assertDontSee('Dr. Alex Santos');
        $response->assertDontSee('Elena Cruz');
        $response->assertDontSee('Roberto Diaz');
        $response->assertDontSee('1,248');
        $response->assertDontSee('&quot;clearancesSettled&quot;:412', false);
        $response->assertDontSee('&quot;pendingQueues&quot;:836', false);
        $response->assertDontSee('All database links connected');

        // Real computed counts this term: 1 fully settled (chair/cashier/registrar
        // approved + no items), 2 pending (the legacy-pending student, plus the
        // student whose legacy statuses are all Approved but whose auto-created
        // department ClearanceItem is still Pending, so allItemsApproved() is false).
        // The out-of-term (2025-2026) clearance is excluded from both counts.
        $response->assertSee('&quot;clearancesSettled&quot;:1', false);
        $response->assertSee('&quot;pendingQueues&quot;:2', false);
    }
}
