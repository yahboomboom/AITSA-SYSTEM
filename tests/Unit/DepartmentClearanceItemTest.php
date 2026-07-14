<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\ClearanceItem;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentClearanceItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_factory_and_officer_relation(): void
    {
        $department = Department::factory()->create(['name' => 'Library']);
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $this->assertTrue($officer->department->is($department));
        $this->assertTrue($department->officers()->whereKey($officer->id)->exists());
    }

    public function test_clearance_item_belongs_to_clearance_and_department(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $department = Department::factory()->create();

        $item = ClearanceItem::create([
            'clearance_id' => $clearance->id,
            'department_id' => $department->id,
            'status' => 'Pending',
        ]);

        $this->assertTrue($item->clearance->is($clearance));
        $this->assertTrue($item->department->is($department));
        $this->assertTrue($clearance->items->first()->is($item));
    }

    public function test_all_items_approved_true_when_no_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);

        $this->assertTrue($clearance->allItemsApproved());
    }

    public function test_all_items_approved_false_until_every_item_is_approved(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();
        $clearance->items()->create(['department_id' => $deptA->id, 'status' => 'Approved']);
        $clearance->items()->create(['department_id' => $deptB->id, 'status' => 'Pending']);

        $this->assertFalse($clearance->fresh('items')->allItemsApproved());

        $clearance->items()->where('department_id', $deptB->id)->update(['status' => 'Approved']);

        $this->assertTrue($clearance->fresh('items')->allItemsApproved());
    }
}
