<?php

namespace Tests\Unit;

use App\Models\DiscountType;
use App\Models\TransactionLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_type_assignment_and_null_on_delete(): void
    {
        $type = DiscountType::factory()->create(['name' => 'Academic Scholar', 'percent' => 50]);
        $student = User::factory()->create(['role' => 'student', 'discount_type_id' => $type->id]);

        $this->assertTrue($student->discountType->is($type));
        $this->assertTrue($type->students()->whereKey($student->id)->exists());

        $type->delete();
        $this->assertNull($student->fresh()->discount_type_id);
    }

    public function test_gateway_ledger_row_defaults(): void
    {
        $row = TransactionLedger::factory()->create();

        $this->assertSame('paymongo', $row->gateway);
        $this->assertSame('Pending', $row->status);
        $this->assertNull($row->processed_by);
        $this->assertNull($row->paid_at);

        $row->update(['status' => 'Settled', 'paid_at' => now()]);
        $this->assertNotNull($row->fresh()->paid_at);
    }
}
