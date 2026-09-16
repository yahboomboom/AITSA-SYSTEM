<?php

namespace Tests\Unit;

use App\Models\Program;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPrerequisiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_belongs_to_program_and_has_prerequisites(): void
    {
        $program = Program::factory()->create(['code' => 'BSOA']);
        $intro   = Subject::factory()->for($program)->create(['code' => 'OA101', 'year_level' => 1]);
        $advance = Subject::factory()->for($program)->create(['code' => 'OA201', 'year_level' => 2]);

        $advance->prerequisites()->attach($intro->id);

        $this->assertTrue($advance->program->is($program));
        $this->assertTrue($advance->prerequisites->first()->is($intro));
        $this->assertCount(0, $intro->prerequisites);
    }
}
