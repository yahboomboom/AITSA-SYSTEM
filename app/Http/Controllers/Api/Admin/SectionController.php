<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $section = Section::create($this->validated($request));

        AuditLog::record('Curriculum Updated', "Section {$section->block_label} added to subject #{$section->subject_id}.", 'Section', $section->id);

        return response()->json(['section' => $section], 201);
    }

    public function update(Request $request, Section $section): JsonResponse
    {
        $section->update($this->validated($request, $section));

        AuditLog::record('Curriculum Updated', "Section #{$section->id} updated.", 'Section', $section->id);

        return response()->json(['section' => $section->fresh()]);
    }

    public function destroy(Section $section): JsonResponse
    {
        if ($section->enrollments()->where('enrollments.status', '!=', 'rejected')->exists()) {
            return response()->json(['message' => 'This section has enrolled students and cannot be deleted. Edit it instead.'], 409);
        }

        AuditLog::record('Curriculum Updated', "Section #{$section->id} deleted.", 'Section', $section->id);
        $section->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?Section $section = null): array
    {
        $required = $section ? 'sometimes' : 'required';

        return $request->validate([
            'subject_id' => [$required, 'integer', 'exists:subjects,id'],
            'block_label' => [$required, 'string', 'max:10'],
            'days' => [$required, 'array', 'min:1'],
            'days.*' => ['in:M,T,W,Th,F,Sat,Sun'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i', 'after:start_time'],
            'room' => [$required, 'string', 'max:50'],
            'professor' => [$required, 'string', 'max:100'],
            'capacity' => [$required, 'integer', 'between:1,500'],
            'school_year' => [$required, 'string', 'max:20'],
        ]);
    }
}
