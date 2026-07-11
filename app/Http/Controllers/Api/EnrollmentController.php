<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $service)
    {
    }

    public function context(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $user->isIrregularStudent() ? 'irregular' : 'regular';
        $enrollment = $this->service->activeEnrollment($user);
        $hasActive = $enrollment && $enrollment->status !== 'rejected';

        $block = null;
        if ($type === 'regular' && ! $hasActive) {
            $found = $this->service->blockFor($user);
            $block = $found ? [
                'label' => $found['label'],
                'sections' => $found['sections']->map(fn ($s) => $this->sectionPayload($s))->values(),
            ] : null;
        }

        return response()->json([
            'term' => $this->service->currentTerm(),
            'student' => [
                'name' => $user->name,
                'login_id' => $user->login_id,
                'program' => $user->major,
                'program_name' => $user->program()?->name,
                'year_level' => $user->year_level,
                'type' => $type,
            ],
            'clearance_complete' => $this->service->clearanceComplete($user),
            'enrollment' => $enrollment ? $this->enrollmentPayload($enrollment) : null,
            'block' => $block,
            'catalogue' => $type === 'irregular' && ! $hasActive ? $this->service->catalogueFor($user) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $enrollment = $user->isIrregularStudent()
                ? $this->service->enrollIrregular($user, $request->validate(['section_ids' => ['required', 'array'], 'section_ids.*' => ['integer']])['section_ids'])
                : $this->service->enrollRegular($user);
        } catch (EnrollmentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['enrollment' => $this->enrollmentPayload($enrollment)], 201);
    }

    private function enrollmentPayload(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('sections.subject');

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->status,
            'type' => $enrollment->type,
            'block_label' => $enrollment->block_label,
            'remarks' => $enrollment->remarks,
            'sections' => $enrollment->sections->map(fn ($s) => $this->sectionPayload($s))->values(),
        ];
    }

    private function sectionPayload($section): array
    {
        return [
            'code' => $section->subject->code,
            'title' => $section->subject->title,
            'units' => $section->subject->units,
            'block_label' => $section->block_label,
            'days' => $section->days,
            'start_time' => $section->start_time,
            'end_time' => $section->end_time,
            'room' => $section->room,
            'professor' => $section->professor,
        ];
    }
}
