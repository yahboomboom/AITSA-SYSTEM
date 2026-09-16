{{-- partials/student-status-badge.blade.php --}}
{{-- Shows Year Level · Current Semester · Enrollment Status beside the profile avatar. --}}
{{-- Server-rendered so it's correct on every student page and for every account, new or old. --}}
@php
    $__enrollmentService = app(\App\Services\EnrollmentService::class);
    $__term = $__enrollmentService->currentTerm();
    $__activeEnrollment = $__enrollmentService->activeEnrollment(Auth::user());

    $__statusStyles = [
        'pending' => ['Pending', 'text-amber-600'],
        'enrolled' => ['Enrolled', 'text-brandGreen'],
        'rejected' => ['Rejected', 'text-red-500'],
    ];
    [$__statusLabel, $__statusTone] = $__statusStyles[$__activeEnrollment->status ?? ''] ?? ['Not Enrolled', 'text-slate-500 dark:text-slate-400'];
@endphp
<div class="hidden md:block text-right border-r border-brandNavy/10 dark:border-slate-700 pr-4 mr-1">
    <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ Auth::user()->year_level ?? '—' }} &middot; Sem {{ $__term['semester'] }} &middot;
        <span class="{{ $__statusTone }} font-semibold">{{ $__statusLabel }}</span>
    </p>
</div>
