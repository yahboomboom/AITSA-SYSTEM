<?php

namespace Tests\Feature;

use App\Models\DiscountType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->create(['role' => 'cashier']);
    }

    public function test_billing_page_renders_with_fees_and_discounts(): void
    {
        DiscountType::factory()->create(['name' => 'Academic Scholar', 'percent' => 50]);
        User::factory()->create(['role' => 'student', 'name' => 'Test Student']);

        $response = $this->actingAs($this->cashier)->get('/cashier/billing');

        $response->assertOk()
            ->assertSee('Billing Configuration')
            ->assertSee('Academic Scholar')
            ->assertSee('Test Student');
    }

    public function test_cashier_can_update_fees(): void
    {
        $this->actingAs($this->cashier)
            ->post('/cashier/billing/fees', [
                'tuition_per_unit' => 450,
                'misc_fee' => 2000,
                'reservation_fee' => 500,
                'tesda_tuition_fee' => 1500,
                'down_payment_percent' => 40,
            ])
            ->assertRedirect(route('cashier.billing'));

        $this->assertSame('450', Setting::get('tuition_per_unit'));
        $this->assertSame('2000', Setting::get('misc_fee'));
        $this->assertSame('40', Setting::get('down_payment_percent'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Fees Updated']);
    }

    public function test_down_payment_percent_over_100_is_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->from('/cashier/billing')
            ->post('/cashier/billing/fees', [
                'tuition_per_unit' => 300, 'misc_fee' => 1500, 'reservation_fee' => 500,
                'tesda_tuition_fee' => 1500, 'down_payment_percent' => 150,
            ])
            ->assertSessionHasErrors('down_payment_percent');
    }

    public function test_negative_fees_are_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->from('/cashier/billing')
            ->post('/cashier/billing/fees', ['tuition_per_unit' => -5, 'misc_fee' => 2000])
            ->assertSessionHasErrors('tuition_per_unit');
    }

    public function test_cashier_can_create_and_delete_discount_types(): void
    {
        $this->actingAs($this->cashier)
            ->post('/cashier/billing/discounts', ['name' => 'Sibling Discount', 'percent' => 10])
            ->assertRedirect(route('cashier.billing'));

        $type = DiscountType::where('name', 'Sibling Discount')->first();
        $this->assertNotNull($type);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Type Added']);

        $student = User::factory()->create(['role' => 'student', 'discount_type_id' => $type->id]);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/discounts/{$type->id}/delete")
            ->assertRedirect(route('cashier.billing'));

        $this->assertNull(DiscountType::find($type->id));
        $this->assertNull($student->fresh()->discount_type_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Type Removed']);
    }
    public function test_cashier_can_toggle_discount_type_active_state(): void
    {
        $type = DiscountType::factory()->create(['name' => 'Barranggay Scholar', 'percent' => 30]);
        $this->assertTrue($type->fresh()->is_active);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/discounts/{$type->id}/toggle")
            ->assertRedirect(route('cashier.billing'));

        $this->assertFalse($type->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Type Deactivated']);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/discounts/{$type->id}/toggle");

        $this->assertTrue($type->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Type Activated']);
    }
    public function test_invalid_percent_and_duplicate_name_are_rejected(): void
    {
        DiscountType::factory()->create(['name' => 'Academic Scholar']);

        $this->actingAs($this->cashier)->from('/cashier/billing')
            ->post('/cashier/billing/discounts', ['name' => 'Overkill', 'percent' => 150])
            ->assertSessionHasErrors('percent');

        $this->actingAs($this->cashier)->from('/cashier/billing')
            ->post('/cashier/billing/discounts', ['name' => 'Academic Scholar', 'percent' => 20])
            ->assertSessionHasErrors('name');
    }

    public function test_cashier_can_assign_and_clear_a_student_discount(): void
    {
        $type = DiscountType::factory()->create();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/assign/{$student->id}", ['discount_type_id' => $type->id])
            ->assertRedirect(route('cashier.billing'));
        $this->assertSame($type->id, $student->fresh()->discount_type_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Student Discount Updated']);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/assign/{$student->id}", ['discount_type_id' => null])
            ->assertRedirect(route('cashier.billing'));
        $this->assertNull($student->fresh()->discount_type_id);
    }

    public function test_non_cashier_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/cashier/billing')->assertForbidden();
        $this->actingAs($student)->post('/cashier/billing/fees', ['tuition_per_unit' => 1, 'misc_fee' => 1])->assertForbidden();
    }
}
