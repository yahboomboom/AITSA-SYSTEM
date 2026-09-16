<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudentGrade;
use App\Models\User;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: Do NOT wrap passwords in Hash::make() here.
        // The User model has 'password' => 'hashed' cast which auto-hashes on save.

        User::updateOrCreate(['email' => 'student@aitsa.edu.ph'], [
            'name'       => 'Andrie Algara',
            'login_id'   => '2026-10254',
            'password'   => 'password123',
            'role'       => 'student',
            'year_level' => '3rd Year',
            'major'      => 'BSIT - Web Development',
        ]);

        User::updateOrCreate(['email' => 'chair@aitsa.edu.ph'], [
            'name'     => 'Dr. Alex Santos',
            'login_id' => 'chair01',
            'password' => 'password123',
            'role'     => 'chair',
        ]);

        User::updateOrCreate(['email' => 'cashier@aitsa.edu.ph'], [
            'name'     => 'Elena Cruz',
            'login_id' => 'cashier01',
            'password' => 'password123',
            'role'     => 'cashier',
        ]);

        User::updateOrCreate(['email' => 'registrar@aitsa.edu.ph'], [
            'name'     => 'Roberto Diaz',
            'login_id' => 'registrar01',
            'password' => 'password123',
            'role'     => 'registrar',
        ]);

        User::updateOrCreate(['email' => 'admin@aitsa.edu.ph'], [
            'name'     => 'System Administrator',
            'login_id' => 'admin01',
            'password' => 'password123',
            'role'     => 'admin',
        ]);

        // Seed academic grades for the demo student.
        // Andrie Algara is a 3rd year irregular student:
        //   passed all of 1st year and 2nd year 1st sem,
        //   but failed CC 213 (Database Systems 1) and CC 216 (Operating Systems).
        $student = User::where('email', 'student@aitsa.edu.ph')->first();
        if ($student) {
            // Grades keyed by subject code → final grade (0-100).
            // 75+ = Passed, below 75 = Failed.
            $gradeSheet = [
                'CC 101'  => 88,  // 1st year, sem 1
                'CC 102'  => 82,
                'GEC 1'   => 91,
                'GEC 2'   => 85,
                'CC 103'  => 79,  // 1st year, sem 2
                'CC 104'  => 83,
                'GEC 3'   => 87,
                'PATH 1'  => 90,
                'CC 211'  => 81,  // 2nd year, sem 1
                'CC 212'  => 77,
                'GEC 5'   => 88,
                'GEC 6'   => 92,
                'CC 213'  => 68,  // 2nd year, sem 2 — FAILED
                'CC 214'  => 84,
                'CC 215'  => 80,
                'CC 216'  => 70,  // 2nd year, sem 2 — FAILED
            ];

            foreach ($gradeSheet as $code => $grade) {
                StudentGrade::updateOrCreate(
                    ['user_id' => $student->id, 'subject_code' => $code],
                    [
                        'status'      => $grade >= 75 ? 'Passed' : 'Failed',
                        'final_grade' => (string) $grade,
                    ]
                );
            }
        }
    }
}