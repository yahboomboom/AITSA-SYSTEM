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
                    'library_status' => 'Approved',
                    'clinic_status' => 'Approved',
                ]
            );
        }
    }
}