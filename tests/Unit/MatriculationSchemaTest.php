<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_with_items_and_relations(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id]);
        $old = Section::factory()->create();
        $new = Section::factory()->create(['subject_id' => $old->subject_id, 'block_label' => 'B']);

        $change = MatriculationChange::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        $change->items()->create([
            'action' => 'swap',
            'section_id' => $new->id,
            'replaced_section_id' => $old->id,
        ]);

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($user->id, $change->user->id);
        $this->assertSame($enrollment->id, $change->enrollment->id);

        $item = $change->items()->first();
        $this->assertSame('swap', $item->action);
        $this->assertSame($new->id, $item->section->id);
        $this->assertSame($old->id, $item->replacedSection->id);
        $this->assertSame($change->id, $item->change->id);
    }

    public function test_deleting_change_cascades_items(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id]);
        $section = Section::factory()->create();

        $change = MatriculationChange::create([
            'enrollment_id' => $enrollment->id, 'user_id' => $user->id, 'status' => 'pending',
        ]);
        $change->items()->create(['action' => 'add', 'section_id' => $section->id]);

        $change->delete();

        $this->assertDatabaseCount('matriculation_change_items', 0);
    }
}
