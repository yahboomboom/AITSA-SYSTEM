<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_build_a_demoable_catalogue(): void
    {
        $this->seed([\Database\Seeders\ProgramSeeder::class, \Database\Seeders\CurriculumSeeder::class]);

        $this->assertSame(7, Program::count());
        $this->assertSame(4, Program::where('is_enrollable', true)->count());
        $this->assertSame('2026-2027', Setting::get('school_year'));
        $this->assertSame('1', Setting::get('semester'));

        $bsoa = Program::where('code', 'BSOA')->first();
        // 4 years x 2 semesters x 4 subjects
        $this->assertSame(32, $bsoa->subjects()->count());

        // Year 1 semester 1 has two full blocks of sections
        $y1s1 = Subject::where('program_id', $bsoa->id)->where('year_level', 1)->where('semester', 1)->pluck('id');
        $labels = Section::whereIn('subject_id', $y1s1)->pluck('block_label')->unique()->sort()->values();
        $this->assertSame(['A', 'B'], $labels->all());

        // A year-2 subject requires its year-1 counterpart
        $y2subject = Subject::where('program_id', $bsoa->id)->where('year_level', 2)->where('semester', 1)->first();
        $this->assertCount(1, $y2subject->prerequisites);
        $this->assertSame(1, $y2subject->prerequisites->first()->year_level);
    }

    public function test_seeded_blocks_have_no_internal_schedule_conflicts(): void
    {
        $this->seed([\Database\Seeders\ProgramSeeder::class, \Database\Seeders\CurriculumSeeder::class]);

        $bsoa = Program::where('code', 'BSOA')->first();
        $y1s1 = Subject::where('program_id', $bsoa->id)->where('year_level', 1)->where('semester', 1)->pluck('id');

        foreach (['A', 'B'] as $block) {
            $sections = Section::whereIn('subject_id', $y1s1)->where('block_label', $block)->get();

            foreach ($sections as $i => $a) {
                foreach ($sections->slice($i + 1) as $b) {
                    $this->assertFalse(
                        $a->overlaps($b),
                        "Block {$block} sections {$a->id} and {$b->id} overlap"
                    );
                }
            }
        }
    }
}
