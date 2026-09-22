<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_change_matriculation_window(): void
    {
        $admin = User::factory()->create(['role' => 'chair']);

        $this->actingAs($admin)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => true])
            ->assertOk()
            ->assertJsonPath('change_matriculation_open', true);
        $this->assertSame('1', Setting::get('change_matriculation_open'));

        $this->actingAs($admin)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => false])
            ->assertOk()
            ->assertJsonPath('change_matriculation_open', false);
        $this->assertSame('0', Setting::get('change_matriculation_open'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'Change Matriculation Window Opened']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Change Matriculation Window Closed']);
    }

    public function test_programs_index_exposes_window_state(): void
    {
        $admin = User::factory()->create(['role' => 'chair']);
        Setting::put('change_matriculation_open', '1');

        $this->actingAs($admin)->getJson('/api/admin/programs')
            ->assertOk()
            ->assertJsonPath('change_matriculation_open', true);
    }

    public function test_students_cannot_toggle(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => true])
            ->assertForbidden();
    }

    public function test_registrar_can_no_longer_toggle(): void
    {
        // Change-of-matriculation moved from Registrar to the Dept Chair
        // alongside the rest of curriculum editing.
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => true])
            ->assertForbidden();
    }
}
