<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarCurriculumPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_can_view_the_curriculum_page(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->get('/registrar/curriculum')
            ->assertOk()
            ->assertSee('id="curriculum-root"', false);
    }

    public function test_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/registrar/curriculum')->assertForbidden();
    }

    public function test_chair_is_forbidden(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)->get('/registrar/curriculum')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/curriculum')->assertRedirect();
    }
}
