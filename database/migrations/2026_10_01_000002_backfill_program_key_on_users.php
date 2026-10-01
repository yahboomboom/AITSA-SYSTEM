<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * program_key used to be silently dropped on create (it was missing from
     * User::$fillable), so existing applicants/students only have `major`
     * (the program code). Map it back so slot counts and reinstate checks
     * see them.
     */
    public function up(): void
    {
        foreach (config('curricula') as $program) {
            if (empty($program['program_code'])) {
                continue;
            }

            DB::table('users')
                ->whereNull('program_key')
                ->where('major', $program['program_code'])
                ->update(['program_key' => $program['id']]);
        }
    }

    public function down(): void
    {
        // Data backfill only — nothing to undo.
    }
};
