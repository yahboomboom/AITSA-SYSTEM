<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Draft curricula: 4 subjects per year/semester per enrollable program,
     * two section blocks (A, B) each, chained prerequisites across years.
     * Block A meets in the morning, block B runs the same slots shifted
     * to the afternoon so neither block conflicts with itself.
     * The AITSA team replaces titles/schedules with the real curriculum
     * via the Curriculum Editor.
     */
    public function run(): void
    {
        $schoolYear = Setting::get('school_year', '2026-2027');
        $slots = [
            ['days' => ['M', 'W'],  'start' => '08:00', 'end' => '09:30', 'pmStart' => '13:00', 'pmEnd' => '14:30'],
            ['days' => ['M', 'W'],  'start' => '10:00', 'end' => '11:30', 'pmStart' => '15:00', 'pmEnd' => '16:30'],
            ['days' => ['T', 'Th'], 'start' => '08:00', 'end' => '09:30', 'pmStart' => '13:00', 'pmEnd' => '14:30'],
            ['days' => ['T', 'Th'], 'start' => '10:00', 'end' => '11:30', 'pmStart' => '15:00', 'pmEnd' => '16:30'],
        ];

        foreach (Program::where('is_enrollable', true)->get() as $program) {
            for ($year = 1; $year <= $program->years; $year++) {
                foreach ([1, 2] as $semester) {
                    for ($n = 1; $n <= 4; $n++) {
                        $subject = Subject::updateOrCreate(
                            ['program_id' => $program->id, 'code' => sprintf('%s%d%d%d', $program->code, $year, $semester, $n)],
                            [
                                'title' => sprintf('%s Course %d (Year %d, Sem %d)', $program->code, $n, $year, $semester),
                                'units' => 3,
                                'year_level' => $year,
                                'semester' => $semester,
                                'mode' => $n === 4 ? 'Online' : 'F2F',
                            ]
                        );

                        // Subject N of year Y requires subject N of year Y-1 (same semester).
                        if ($year > 1) {
                            $prereq = Subject::where('program_id', $program->id)
                                ->where('code', sprintf('%s%d%d%d', $program->code, $year - 1, $semester, $n))
                                ->first();
                            if ($prereq) {
                                $subject->prerequisites()->syncWithoutDetaching([$prereq->id]);
                            }
                        }

                        foreach (['A', 'B'] as $block) {
                            Section::updateOrCreate(
                                ['subject_id' => $subject->id, 'block_label' => $block, 'school_year' => $schoolYear],
                                [
                                    'days' => $slots[$n - 1]['days'],
                                    'start_time' => $block === 'A' ? $slots[$n - 1]['start'] : $slots[$n - 1]['pmStart'],
                                    'end_time' => $block === 'A' ? $slots[$n - 1]['end'] : $slots[$n - 1]['pmEnd'],
                                    'room' => sprintf('Rm %d0%d', $year, $n),
                                    'professor' => 'TBA Faculty',
                                    'capacity' => 40,
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
}
