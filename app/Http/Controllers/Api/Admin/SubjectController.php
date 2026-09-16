<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $subject = Subject::create($data);
        $subject->prerequisites()->sync($request->input('prerequisite_ids', []));

        AuditLog::record('Curriculum Updated', "Subject {$subject->code} created.", 'Subject', $subject->id);

        return response()->json(['subject' => $subject->load('prerequisites')], 201);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $subject->update($this->validated($request, $subject));
        if ($request->has('prerequisite_ids')) {
            $subject->prerequisites()->sync($request->input('prerequisite_ids', []));
        }

        AuditLog::record('Curriculum Updated', "Subject {$subject->code} updated.", 'Subject', $subject->id);

        return response()->json(['subject' => $subject->fresh()->load('prerequisites')]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $hasEnrollments = $subject->sections()
            ->whereHas('enrollments', fn ($q) => $q->where('enrollments.status', '!=', 'rejected'))
            ->exists();

        if ($hasEnrollments) {
            return response()->json(['message' => 'This subject has enrolled students and cannot be deleted. Edit it instead.'], 409);
        }

        AuditLog::record('Curriculum Updated', "Subject {$subject->code} deleted.", 'Subject', $subject->id);
        $subject->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $required = $subject ? 'sometimes' : 'required';

        return $request->validate([
            'program_id' => [$required, 'integer', 'exists:programs,id'],
            'code' => [$required, 'string', 'max:20'],
            'title' => [$required, 'string', 'max:255'],
            'units' => [$required, 'integer', 'between:1,12'],
            'year_level' => [$required, 'integer', 'between:1,4'],
            'semester' => [$required, 'integer', 'between:1,2'],
            'mode' => [$required, 'in:F2F,Online'],
        ]);
    }
}
