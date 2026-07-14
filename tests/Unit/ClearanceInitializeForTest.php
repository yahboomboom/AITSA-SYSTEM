<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceInitializeForTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_clearance_item_for_every_active_department(): void
    {
        $active = Department::factory()->create(['is_active' => true]);
        $inactive = Department::factory()->create(['is_active' => false]);
        $student = User::factory()->create(['role' => 'student']);

        $clearance = Clearance::initializeFor($student->id, ['chair_status' => 'Pending']);

        $this->assertSame(1, $clearance->items()->count());
        $this->assertTrue($clearance->items()->where('department_id', $active->id)->exists());
        $this->assertFalse($clearance->items()->where('department_id', $inactive->id)->exists());
    }

    public function test_returns_existing_clearance_without_creating_new_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $first = Clearance::initializeFor($student->id);

        Department::factory()->create(['is_active' => true]);
        $second = Clearance::initializeFor($student->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(0, $second->items()->count());
    }
}
