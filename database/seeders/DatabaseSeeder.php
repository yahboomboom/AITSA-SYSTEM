<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clearance;
use Database\Seeders\ShsStrandsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ShsStrandsSeeder::class);

        $this->call([
            ProgramSeeder::class,
            CurriculumSeeder::class,
        ]);

        // 1. Structural Student Account
        User::updateOrCreate(
            ['email' => 'student@aitsa.edu.ph'],
            [
                'name' => 'Andrie Algara',
                'login_id' => '2026-10254',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]
        );

        // 2. Department Chair Account
        User::updateOrCreate(
            ['email' => 'chair@aitsa.edu.ph'],
            [
                'name' => 'Dr. Alex Santos',
                'login_id' => 'chair01',
                'password' => Hash::make('password123'),
                'role' => 'chair',
            ]
        );

        // 3. Finance Cashier Account
        User::updateOrCreate(
            ['email' => 'cashier@aitsa.edu.ph'],
            [
                'name' => 'Elena Cruz',
                'login_id' => 'cashier01',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
            ]
        );

        // 4. Institutional Registrar Account
        User::updateOrCreate(
            ['email' => 'registrar@aitsa.edu.ph'],
            [
                'name' => 'Roberto Diaz',
                'login_id' => 'registrar01',
                'password' => Hash::make('password123'),
                'role' => 'registrar',
            ]
        );
        
        // 5. System Administrator Account
        User::updateOrCreate(
            ['email' => 'admin@aitsa.edu.ph'],
            [
                'name' => 'System Administrator',
                'login_id' => 'admin01',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // 6. Active Tracking Row for Andrie's Student Account
        $student = User::where('email', 'student@aitsa.edu.ph')->first();
        if ($student) {
            Clearance::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'chair_status' => 'Approved',
                    'cashier_status' => 'Pending',
                    'registrar_status' => 'Pending',
                ]
            );
        }

        // 7. Demo students for the enrollment walkthrough (regular + irregular).
        // Weak passwords by design — never seed these outside local/dev environments.
        if (app()->environment('production')) {
            return;
        }

        $regular = User::firstOrCreate(
            ['login_id' => '2300410'],
            ['name' => 'Demo Regular Student', 'email' => 'regular.demo@aitsa.test', 'password' => Hash::make('password'),
             'role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']
        );
        Clearance::firstOrCreate(
            ['user_id' => $regular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']
        );

        $irregular = User::firstOrCreate(
            ['login_id' => '2300411'],
            ['name' => 'Demo Irregular Student', 'email' => 'irregular.demo@aitsa.test', 'password' => Hash::make('password'),
             'role' => 'student', 'major' => 'BSOA', 'year_level' => '2nd Year']
        );
        Clearance::firstOrCreate(
            ['user_id' => $irregular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']
        );
        \App\Models\StudentGrade::firstOrCreate(
            ['user_id' => $irregular->id, 'subject_code' => 'BSOA111'],
            ['status' => 'Failed', 'final_grade' => '5.00']
        );

        // 8. Faculty & room loading demo data (still inside the non-production guard above).
        $facultyOne = User::firstOrCreate(
            ['login_id' => 'faculty01'],
            ['name' => 'Prof. Liza Ramos', 'email' => 'faculty01@faculty.aitsa.test',
             'password' => Hash::make('password123'), 'role' => 'faculty']
        );
        $facultyTwo = User::firstOrCreate(
            ['login_id' => 'faculty02'],
            ['name' => 'Prof. Marco Dizon', 'email' => 'faculty02@faculty.aitsa.test',
             'password' => Hash::make('password123'), 'role' => 'faculty']
        );

        foreach (\App\Models\Section::query()->distinct()->pluck('room') as $roomName) {
            \App\Models\Room::firstOrCreate(['name' => $roomName], ['type' => 'physical']);
        }
        \App\Models\Room::firstOrCreate(['name' => 'Google Meet A'], ['type' => 'virtual']);

        // Link every section to its room entity; spread faculty greedily without double-booking.
        $roomsByName = \App\Models\Room::pluck('id', 'name');
        $assigned = [$facultyOne->id => [], $facultyTwo->id => []];

        foreach (\App\Models\Section::orderBy('id')->get() as $section) {
            $section->room_id = $roomsByName[$section->room] ?? null;

            if ($section->faculty_id === null) {
                foreach ([$facultyOne->id, $facultyTwo->id] as $facultyId) {
                    $clash = collect($assigned[$facultyId])->contains(
                        fn ($s) => $s->school_year === $section->school_year && $s->overlaps($section)
                    );
                    if (! $clash) {
                        $section->faculty_id = $facultyId;
                        $assigned[$facultyId][] = $section;
                        break;
                    }
                }
            }

            $section->save();
        }

        // Demo discount type for the cashier billing page
        \App\Models\DiscountType::firstOrCreate(['name' => 'Academic Scholar'], ['percent' => 50]);
    }
}