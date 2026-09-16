<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentClearancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearance_page_shows_a_card_per_active_department(): void
    {
        Department::factory()->create(['name' => 'Library']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('Library');

        $this->assertDatabaseHas('clearance_items', [
            'department_id' => Department::where('name', 'Library')->value('id'),
            'status' => 'Pending',
        ]);
    }

    public function test_held_item_shows_remarks_on_the_page(): void
    {
        $department = Department::factory()->create(['name' => 'Clinic']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        $clearance = $student->clearance;
        $clearance->items()->where('department_id', $department->id)->update(['status' => 'Hold', 'remarks' => 'Visit the clinic for a checkup.']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('Visit the clinic for a checkup.');
    }

    public function test_pending_department_item_blocks_officially_cleared_badge(): void
    {
        Department::factory()->create(['name' => 'Library']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        $clearance = $student->clearance;
        $clearance->update([
            'admission_status' => 'Approved',
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"isCleared":false');
    }

    public function test_hold_department_item_blocks_officially_cleared_badge(): void
    {
        $department = Department::factory()->create(['name' => 'Clinic']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        $clearance = $student->clearance;
        $clearance->update([
            'admission_status' => 'Approved',
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);
        $clearance->items()->where('department_id', $department->id)
            ->update(['status' => 'Hold', 'remarks' => 'Missing requirement.']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"isCleared":false');
    }

    public function test_all_items_approved_shows_officially_cleared_badge(): void
    {
        Department::factory()->create(['name' => 'Library']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        $clearance = $student->clearance;
        $clearance->update([
            'admission_status' => 'Approved',
            'chair_status' => 'Approved',
            'cashier_status' => 'Approved',
            'registrar_status' => 'Approved',
        ]);
        $clearance->items()->update(['status' => 'Approved']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"isCleared":true');
    }

    public function test_department_added_after_clearance_exists_does_not_retroactively_appear(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        Department::factory()->create(['name' => 'Guidance Office']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertDontSee('Guidance Office');
    }
}
