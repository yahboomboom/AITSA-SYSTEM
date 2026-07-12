<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\MatriculationChange;
use App\Models\Section;
use App\Services\EnrollmentService;
use App\Services\MatriculationChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatriculationController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly MatriculationChangeService $service,
    ) {
    }

    public function context(Request $request): JsonResponse
    {
        $user = $request->user();
        $enrollment = $this->enrollments->activeEnrollment($user);
        $enrolled = $enrollment && $enrollment->status === 'enrolled';
        $latest = $enrolled ? $this->service->latestRequestFor($enrollment) : null;

        return response()->json([
            'window_open' => $this->service->windowOpen(),
            'enrollment' => $enrolled ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'sections' => $enrollment->sections()->with('subject')->get()
                    ->map(fn (Section $s) => $this->sectionPayload($s))->values(),
            ] : null,
            'request' => $latest ? $this->requestPayload($latest) : null,
            'catalogue' => $enrolled ? $this->enrollments->catalogueFor($user) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.action' => ['required', 'in:add,drop,swap'],
            'items.*.section_id' => ['required', 'integer'],
            'items.*.replaced_section_id' => ['nullable', 'integer'],
        ]);

        try {
            $change = $this->service->submit($request->user(), $data['items']);
        } catch (EnrollmentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['request' => $this->requestPayload($change)], 201);
    }

    private function requestPayload(MatriculationChange $change): array
    {
        $change->loadMissing('items.section.subject', 'items.replacedSection.subject');

        return [
            'id' => $change->id,
            'status' => $change->status,
            'remarks' => $change->remarks,
            'items' => $change->items->map(fn ($item) => [
                'action' => $item->action,
                'section' => $this->sectionPayload($item->section),
                'replaced_section' => $item->replacedSection ? $this->sectionPayload($item->replacedSection) : null,
            ])->values(),
        ];
    }

    private function sectionPayload(Section $section): array
    {
        return [
            'id' => $section->id,
            'subject_id' => $section->subject_id,
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
