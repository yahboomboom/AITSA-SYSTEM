<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['code' => 'BKNC3',  'name' => 'Bookkeeping NC III',                                   'level' => 'tesda',     'years' => null, 'is_enrollable' => false],
            ['code' => 'EMNC3',  'name' => 'Events Management NC III',                             'level' => 'tesda',     'years' => null, 'is_enrollable' => false],
            ['code' => 'FBNC3',  'name' => 'Food & Beverages NC III',                              'level' => 'tesda',     'years' => null, 'is_enrollable' => false],
            ['code' => 'BOM',    'name' => 'Business Office Management',                           'level' => 'associate', 'years' => 2,    'is_enrollable' => true],
            ['code' => 'FSM',    'name' => 'Food Service Management',                              'level' => 'associate', 'years' => 2,    'is_enrollable' => true],
            ['code' => 'BSOA',   'name' => 'Bachelor of Science in Office Administration',         'level' => 'bachelor',  'years' => 4,    'is_enrollable' => true],
            ['code' => 'BTVTED', 'name' => 'Bachelor in Technical-Vocational Teacher Education',   'level' => 'bachelor',  'years' => 4,    'is_enrollable' => true],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(['code' => $program['code']], $program);
        }

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
    }
}
