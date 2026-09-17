<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproverSchedulingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_view_the_scheduling_page(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)->get('/approver/scheduling')
            ->assertOk()
            ->assertSee('id="approver-scheduling-root"', false);
    }

    public function test_registrar_is_forbidden(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->get('/approver/scheduling')->assertForbidden();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/approver/scheduling')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/approver/scheduling')->assertRedirect();
    }
}
