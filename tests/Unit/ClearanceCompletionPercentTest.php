<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceCompletionPercentTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_percent_is_zero_when_nothing_approved(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);

        $this->assertSame(0, $clearance->completionPercent());
    }

    public function test_completion_percent_counts_legacy_stages_and_department_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);
        $department = Department::factory()->create();
        $clearance->items()->create(['department_id' => $department->id, 'status' => 'Approved']);

        // approved: chair + item = 2, total: 3 legacy stages + 1 item = 4 -> 50%
        $this->assertSame(50, $clearance->fresh('items')->completionPercent());
    }

    public function test_completion_percent_is_100_when_fully_cleared(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);

        $this->assertSame(100, $clearance->fresh('items')->completionPercent());
    }
}
