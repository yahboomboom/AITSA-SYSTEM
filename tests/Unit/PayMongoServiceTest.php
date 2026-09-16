<?php

namespace Tests\Unit;

use App\Exceptions\PaymentGatewayException;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PayMongoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Task 4 defines the real routes; the service only needs the names to exist.
        if (! Route::has('ledger.payment.return')) {
            Route::get('/ledger/payment/return', fn () => 'ok')->name('ledger.payment.return');
            Route::get('/ledger/payment/cancel', fn () => 'ok')->name('ledger.payment.cancel');
            Route::getRoutes()->refreshNameLookups();
        }
    }

    public function test_create_checkout_session_posts_line_item_and_returns_url(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
                ],
            ], 200),
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $session = app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');

        $this->assertSame('cs_test_123', $session['id']);
        $this->assertSame('https://checkout.paymongo.com/cs_test_123', $session['checkout_url']);
        Http::assertSent(function ($request) {
            $attrs = $request->data()['data']['attributes'];

            return str_contains($request->url(), '/checkout_sessions')
                && $attrs['line_items'][0]['amount'] === 300000
                && $attrs['line_items'][0]['currency'] === 'PHP'
                && $attrs['payment_method_types'] === ['gcash', 'card', 'paymaya'];
        });
    }

    public function test_api_failure_throws_gateway_exception(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $user = User::factory()->create(['role' => 'student']);

        $this->expectException(PaymentGatewayException::class);
        app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');
    }

    public function test_connection_failure_throws_gateway_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 6: Could not resolve host: api.paymongo.com');
        });
        $user = User::factory()->create(['role' => 'student']);

        $this->expectException(PaymentGatewayException::class);
        app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');
    }

    public function test_missing_key_throws_gateway_exception(): void
    {
        config(['services.paymongo.secret' => null]);
        $user = User::factory()->create(['role' => 'student']);

        $this->expectException(PaymentGatewayException::class);
        app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');
    }

    public function test_session_is_paid_inspects_payments(): void
    {
        $service = app(PayMongoService::class);

        $paid = ['attributes' => ['payments' => [['attributes' => ['status' => 'paid']]]]];
        $unpaid = ['attributes' => ['payments' => []]];
        $failed = ['attributes' => ['payments' => [['attributes' => ['status' => 'failed']]]]];

        $this->assertTrue($service->sessionIsPaid($paid));
        $this->assertFalse($service->sessionIsPaid($unpaid));
        $this->assertFalse($service->sessionIsPaid($failed));
    }
}
