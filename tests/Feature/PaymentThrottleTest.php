<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithBalance(): User
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);

        return $student;
    }

    public function test_checkout_is_rate_limited_after_six_attempts_per_minute(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $student = $this->studentWithBalance();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($student)->post('/ledger/checkout');
        }

        $this->actingAs($student)->post('/ledger/checkout')->assertStatus(429);
    }

    public function test_verify_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Pending',
            'gateway' => 'paymongo', 'checkout_session_id' => 'cs_test_throttle',
        ]);
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_test_throttle' => Http::response([
                'data' => ['id' => 'cs_test_throttle', 'attributes' => ['payments' => []]],
            ], 200),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->post('/ledger/verify');
        }

        $this->actingAs($student)->post('/ledger/verify')->assertStatus(429);
    }

    public function test_payment_return_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Pending',
            'gateway' => 'paymongo', 'checkout_session_id' => 'cs_test_throttle',
        ]);
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_test_throttle' => Http::response([
                'data' => ['id' => 'cs_test_throttle', 'attributes' => ['payments' => []]],
            ], 200),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->get('/ledger/payment/return');
        }

        $this->actingAs($student)->get('/ledger/payment/return')->assertStatus(429);
    }

    public function test_checkout_and_verify_rate_limit_buckets_are_independent(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $student = $this->studentWithBalance();

        // Exhaust verify's bucket first (10/10 for its throttle:10,1 limit).
        // Its count (10) exceeds checkout's limit (6), so if the two routes
        // shared a single rate-limit key (no distinct prefix), the very
        // next checkout call would be blocked immediately with 429 even
        // though checkout has made zero requests of its own.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->post('/ledger/verify');
        }
        $this->actingAs($student)->post('/ledger/verify')->assertStatus(429);

        // Checkout has its own, independent bucket and must start fresh.
        $this->actingAs($student)->post('/ledger/checkout')->assertStatus(302);
    }
}
