{{-- partials/student-status-badge.blade.php --}}
{{-- Program · Year Level · Current Semester · Enrollment Status, shown under the student's name in the profile menu. --}}
{{-- Server-rendered so it's correct on every student page and for every account, new or old. --}}
{{-- Props: $programLabel --}}
@php
    $__enrollmentService = app(\App\Services\EnrollmentService::class);
    $__term = $__enrollmentService->currentTerm();
    $__activeEnrollment = $__enrollmentService->activeEnrollment(Auth::user());

    $__statusStyles = [
        'pending' => ['Pending', 'text-brandGold'],
        'enrolled' => ['Enrolled', 'text-brandGreen'],
        'rejected' => ['Rejected', 'text-red-500'],
    ];
    [$__statusLabel, $__statusTone] = $__statusStyles[$__activeEnrollment->status ?? ''] ?? ['Not Enrolled', 'text-slate-500 dark:text-slate-400'];
@endphp
<p class="text-[10px] font-medium text-brandNavy/60 dark:text-slate-400">
    {{ $programLabel }} &middot; {{ Auth::user()->year_level ?? '—' }} &middot; Sem {{ $__term['semester'] }} &middot;
    <span class="{{ $__statusTone }} font-semibold">{{ $__statusLabel }}</span>
</p>
