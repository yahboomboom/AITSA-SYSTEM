<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_clearance_page_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk();
        $response->assertSee('id="clearance-root"', false);
        $response->assertDontSee('Clearance Action Items');
        $response->assertDontSee('On-Hold Record Status');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/clearance')->assertRedirect();
    }

    public function test_clearance_context_carries_the_real_school_year_and_semester(): void
    {
        \App\Models\Setting::put('school_year', '2027-2028');
        \App\Models\Setting::put('semester', '2');
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk();
        $response->assertSee('&quot;schoolYear&quot;:&quot;2027-2028&quot;', false);
        $response->assertSee('&quot;semester&quot;:2', false);
    }
}
