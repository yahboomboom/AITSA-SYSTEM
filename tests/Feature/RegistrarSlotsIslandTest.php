<?php

namespace Tests\Feature;

use App\Models\AdmissionSlotLimit;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarSlotsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $program = collect(config('curricula'))->first();
        AdmissionSlotLimit::forProgram($program['id'], $program['name'], '2026-2027')
            ->update(['total_slots' => 120, 'sections' => 3]);

        $response = $this->actingAs($registrar)->get('/registrar/slots');

        $response->assertOk();
        $response->assertSee('id="registrar-slots-root"', false);
        $response->assertSee($program['name']);
        $response->assertSee('&quot;totalSlots&quot;:120', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/slots')->assertRedirect();
    }

    public function test_non_registrar_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/slots')->assertForbidden();
    }
}
