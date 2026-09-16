<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The apply form previously showed "6 months" for all three TESDA programs —
 * wrong, and identically wrong for all three. TESDA's own Training
 * Regulations give each program a different nominal hour count, none of
 * them close to 6 months. Displaying nominal hours (rather than a guessed
 * calendar length) is the only figure TESDA actually publishes; AITSA's own
 * daily schedule determines the real calendar length, which isn't modeled
 * here yet.
 */
class ApplyPageTesdaDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_tesda_program_shows_its_own_official_nominal_hours(): void
    {
        $response = $this->get('/apply');

        $response->assertOk();
        $response->assertSee('292 training hours');
        $response->assertSee('108 training hours');
        $response->assertSee('230 training hours');
        $response->assertDontSee('6 months');
    }
}
