<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_page_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/ledger');

        $response->assertOk();
        $response->assertSee('id="payment-root"', false);
        $response->assertDontSee('Assessment Breakdown');
        $response->assertDontSee('Clearance Status Overview');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/ledger')->assertRedirect();
    }
}
