<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionLedgerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'reference_no' => 'PMG-' . strtoupper(Str::random(10)),
            'amount' => 5000.00,
            'status' => 'Pending',
            'gateway' => 'paymongo',
            'checkout_session_id' => 'cs_' . Str::random(24),
            'paid_at' => null,
            'processed_by' => null,
            'remarks' => null,
        ];
    }
}
