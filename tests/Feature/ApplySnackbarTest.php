<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplySnackbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_success_flash_renders_a_success_snackbar(): void
    {
        $response = $this->withSession(['success' => 'Your application has been received!'])->get('/apply');

        $response->assertOk();
        $response->assertSee('id="snackbar"', false);
        $response->assertSee('data-type="success"', false);
        $response->assertSee('Your application has been received!');
    }

    public function test_an_error_flash_renders_an_error_snackbar(): void
    {
        $response = $this->withSession(['error' => 'That program has reached its slot limit.'])->get('/apply');

        $response->assertOk();
        $response->assertSee('id="snackbar"', false);
        $response->assertSee('data-type="error"', false);
        $response->assertSee('That program has reached its slot limit.');
    }

    public function test_a_receipt_flash_does_not_render_the_plain_success_snackbar(): void
    {
        $response = $this->withSession([
            'success' => 'Application successfully submitted and reservation fee paid.',
            'receipt' => ['reference_no' => 'RES-TEST'],
        ])->get('/apply');

        $response->assertOk();
        $response->assertDontSee('id="snackbar"', false);
    }

    public function test_no_flash_means_no_snackbar(): void
    {
        $response = $this->get('/apply');

        $response->assertOk();
        $response->assertDontSee('id="snackbar"', false);
    }
}
