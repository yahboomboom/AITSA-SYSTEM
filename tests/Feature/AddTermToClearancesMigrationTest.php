<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddTermToClearancesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearances_table_has_term_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('clearances', ['school_year', 'semester']));
    }

    public function test_existing_rows_are_backfilled_with_the_current_setting_term(): void
    {
        \App\Models\Setting::put('school_year', '2026-2027');
        \App\Models\Setting::put('semester', '1');
        \App\Models\Setting::clearCache();

        $user = User::factory()->create(['role' => 'student']);
        // Insert directly, bypassing the model, to simulate a pre-migration row —
        // the migration itself is what must backfill this, not application code.
        $id = \Illuminate\Support\Facades\DB::table('clearances')->insertGetId([
            'user_id' => $user->id,
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = \Illuminate\Support\Facades\DB::table('clearances')->find($id);

        $this->assertSame('2026-2027', $row->school_year);
        $this->assertSame(1, (int) $row->semester);
    }

    public function test_cannot_create_two_clearances_for_the_same_user_and_term(): void
    {
        // Uses the query builder, not Clearance::create() — 'school_year'/'semester'
        // aren't added to the model's $fillable until Task 2, so this test (Task 1's
        // scope: schema only) must not depend on mass-assignment working yet.
        $user = User::factory()->create(['role' => 'student']);
        \Illuminate\Support\Facades\DB::table('clearances')->insert([
            'user_id' => $user->id, 'school_year' => '2026-2027', 'semester' => 1,
            'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        \Illuminate\Support\Facades\DB::table('clearances')->insert([
            'user_id' => $user->id, 'school_year' => '2026-2027', 'semester' => 1,
            'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
