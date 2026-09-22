<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproverCurriculumPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_view_the_curriculum_page(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)->get('/approver/curriculum')
            ->assertOk()
            ->assertSee('id="curriculum-root"', false);
    }

    public function test_registrar_is_forbidden(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->get('/approver/curriculum')->assertForbidden();
    }

    public function test_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/approver/curriculum')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/approver/curriculum')->assertRedirect();
    }
}
