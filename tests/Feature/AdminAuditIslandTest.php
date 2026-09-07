<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        AuditLog::record('Account Created', 'Admin created student account for Audit Test Student.', 'User', 1);

        $response = $this->actingAs($admin)->get('/admin/audit');

        $response->assertOk();
        $response->assertSee('id="admin-audit-root"', false);
        $response->assertSee('Audit Test Student');
    }

    public function test_audit_page_handles_no_entries_yet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/audit');

        $response->assertOk();
        $response->assertSee('&quot;logs&quot;:[]', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/audit')->assertRedirect();
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/audit')->assertForbidden();
    }
}
