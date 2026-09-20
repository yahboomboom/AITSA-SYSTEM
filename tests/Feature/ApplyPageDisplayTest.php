<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two display bugs found during a live evaluator pass: the hero banner and
 * footer had "Academic Year 2025-2026" hardcoded regardless of the actual
 * current term Setting, and the "Events Management NC III" card used
 * fa-calendar-star, a Font Awesome Pro-only icon that silently renders
 * nothing under the Free stylesheet this app actually loads.
 */
class ApplyPageDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_academic_year_reflects_the_real_current_setting(): void
    {
        Setting::put('school_year', '2027-2028');
        Setting::clearCache();

        $response = $this->get('/apply');

        $response->assertOk();
        $response->assertSee('Academic Year 2027-2028 Enrollment');
        $response->assertSee('A.Y. 2027-2028');
        $response->assertDontSee('2025-2026');
        $response->assertDontSee('2025–2026');
    }

    public function test_every_tesda_program_card_uses_a_real_font_awesome_free_icon(): void
    {
        $response = $this->get('/apply');

        $response->assertOk();
        $response->assertSee('fa-calendar-days');
        $response->assertDontSee('fa-calendar-star');
    }
}
