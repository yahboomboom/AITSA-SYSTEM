<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\JsonResponse;

class ProgramController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'programs' => Program::withCount('subjects')->orderBy('level')->orderBy('code')->get()
                ->map(fn (Program $p) => [
                    'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'level' => $p->level,
                    'years' => $p->years, 'is_enrollable' => $p->is_enrollable, 'subjects_count' => $p->subjects_count,
                ]),
        ]);
    }

    public function subjects(Program $program): JsonResponse
    {
        return response()->json([
            'subjects' => $program->subjects()->with(['prerequisites', 'sections'])
                ->orderBy('year_level')->orderBy('semester')->orderBy('code')->get()
                ->map(fn ($s) => [
                    'id' => $s->id, 'code' => $s->code, 'title' => $s->title, 'units' => $s->units,
                    'year_level' => $s->year_level, 'semester' => $s->semester, 'mode' => $s->mode,
                    'prerequisite_ids' => $s->prerequisites->pluck('id'),
                    'sections' => $s->sections->map(fn ($sec) => [
                        'id' => $sec->id, 'block_label' => $sec->block_label, 'days' => $sec->days,
                        'start_time' => $sec->start_time, 'end_time' => $sec->end_time, 'room' => $sec->room,
                        'professor' => $sec->professor, 'capacity' => $sec->capacity,
                        'school_year' => $sec->school_year, 'enrolled_count' => $sec->enrolledCount(),
                    ]),
                ]),
        ]);
    }
}
