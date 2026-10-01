<?php

namespace Tests\Feature;

use App\Models\AdmissionSlotLimit;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
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

    public function test_page_gets_the_current_term_and_the_suggested_next_term(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        foreach ([
            ['2026-2027', '1', '&quot;next&quot;:{&quot;schoolYear&quot;:&quot;2026-2027&quot;,&quot;semester&quot;:2}'],
            ['2026-2027', '2', '&quot;next&quot;:{&quot;schoolYear&quot;:&quot;2027-2028&quot;,&quot;semester&quot;:1}'],
        ] as [$year, $sem, $expectedNext]) {
            Setting::put('school_year', $year);
            Setting::put('semester', $sem);

            $this->actingAs($registrar)->get('/registrar/slots')->assertOk()
                ->assertSee('&quot;current&quot;:{&quot;schoolYear&quot;:&quot;' . $year . '&quot;,&quot;semester&quot;:' . $sem . '}', false)
                ->assertSee($expectedNext, false);
        }
    }

    public function test_saving_a_slot_limit_shows_a_snackbar_not_a_static_banner(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $program = collect(config('curricula'))->first();
        $limit = AdmissionSlotLimit::forProgram($program['id'], $program['name'], '2026-2027');

        $this->actingAs($registrar)
            ->from('/registrar/slots')
            ->post("/registrar/slots/{$limit->id}", ['total_slots' => 150])
            ->assertRedirect('/registrar/slots');

        $response = $this->get('/registrar/slots');

        $response->assertOk();
        $response->assertSee('id="snackbar"', false);
        $response->assertSee('data-type="success"', false);
        $response->assertSee('updated.', false);
    }

    public function test_registrar_sets_only_total_slots_no_sections(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $program = collect(config('curricula'))->first();
        $limit = AdmissionSlotLimit::forProgram($program['id'], $program['name'], '2026-2027');

        // Blocks are the Chair's job; Admission Slots only caps how many applicants get in.
        $this->actingAs($registrar)->from('/registrar/slots')
            ->post("/registrar/slots/{$limit->id}", ['total_slots' => 150])
            ->assertRedirect('/registrar/slots')
            ->assertSessionHasNoErrors();

        $this->assertSame(150, $limit->fresh()->total_slots);
        $this->get('/registrar/slots')->assertDontSee('perSection', false);
    }

    public function test_each_card_shows_the_chairs_first_year_block_seats(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');

        $bsoa = Program::factory()->create(['code' => 'BSOA']);
        $math = Subject::factory()->for($bsoa)->create(['year_level' => 1, 'semester' => 1]);
        $eng = Subject::factory()->for($bsoa)->create(['year_level' => 1, 'semester' => 1]);
        // Block A: a student needs a seat in every class, so the smaller capacity (35) counts.
        Section::factory()->for($math)->create(['block_label' => 'A', 'capacity' => 40]);
        Section::factory()->for($eng)->create(['block_label' => 'A', 'capacity' => 35]);
        foreach (['B', 'C'] as $block) {
            Section::factory()->for($math)->create(['block_label' => $block, 'capacity' => 40]);
            Section::factory()->for($eng)->create(['block_label' => $block, 'capacity' => 40]);
        }
        // Not counted: 2nd-year, 2nd-semester and other-year blocks.
        Section::factory()->for(Subject::factory()->for($bsoa)->create(['year_level' => 2, 'semester' => 1]))->create(['block_label' => 'A']);
        Section::factory()->for(Subject::factory()->for($bsoa)->create(['year_level' => 1, 'semester' => 2]))->create(['block_label' => 'D']);
        Section::factory()->for($math)->create(['block_label' => 'E', 'school_year' => '2025-2026']);

        $registrar = User::factory()->create(['role' => 'registrar']);
        $response = $this->actingAs($registrar)->get('/registrar/slots')->assertOk();

        $response->assertSee('&quot;programKey&quot;:&quot;bsoa&quot;', false);
        $response->assertSee('&quot;blocks&quot;:{&quot;count&quot;:3,&quot;seats&quot;:115}', false);
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
