<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Department Chair Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.approver-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center space-x-3">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <span class="text-sm font-bold text-brandNavy dark:text-slate-200">College Desk System</span>
                </div>
                <div class="flex items-center space-x-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel'     => 'CCS Academic Approver',
                        'roleClass'     => 'font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                        'avatarInitial' => strtoupper(substr(Auth::user()->name ?? 'C', 0, 1)),
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                @if(session('success'))
                    <div class="p-3.5 rounded-lg bg-brandGreen/8 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-4 py-3 text-sm">{{ session('error') }}</div>
                @endif

                <div class="space-y-1">
                    <h1 class="text-xl font-extrabold tracking-tight text-brandNavy dark:text-white">Department Chair Enrollment Approval</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400">Verify structural student clearance flags and sign off on active program updates.</p>
                </div>

                @php
                    $context = [
                        'clearances' => $clearances->map(fn ($row) => [
                            'id' => $row->id,
                            'studentName' => $row->user->name ?? '—',
                            'studentId' => $row->user->login_id ?? '—',
                            'program' => $row->user->major ?? '—',
                            'state' => ($row->registrar_status ?? 'Pending') !== 'Approved'
                                ? 'locked'
                                : (($row->chair_status ?? 'Pending') === 'Approved' ? 'approved' : 'ready'),
                            'signUrl' => route('approver.sign', $row->id),
                            'holdUrl' => route('approver.hold', $row->id),
                        ])->values(),
                        'enrollments' => $pendingEnrollments->map(fn ($pending) => [
                            'id' => $pending->id,
                            'studentName' => $pending->user->name,
                            'studentId' => $pending->user->login_id,
                            'major' => $pending->user->major,
                            'yearLevel' => $pending->user->year_level,
                            'submittedAgo' => $pending->updated_at->diffForHumans(),
                            'sections' => $pending->sections->map(fn ($section) => [
                                'code' => $section->subject->code,
                                'title' => $section->subject->title,
                                'scheduleLabel' => implode('/', $section->days) . ' ' . $section->start_time . '–' . $section->end_time,
                                'room' => $section->roomLabel(),
                            ])->values(),
                            'approveUrl' => route('approver.enrollments.approve', $pending),
                            'rejectUrl' => route('approver.enrollments.reject', $pending),
                        ])->values(),
                        'changes' => $pendingChanges->map(fn ($change) => [
                            'id' => $change->id,
                            'studentName' => $change->user->name,
                            'studentId' => $change->user->login_id,
                            'major' => $change->user->major,
                            'yearLevel' => $change->user->year_level,
                            'filedAgo' => $change->created_at->diffForHumans(),
                            'items' => $change->items->map(fn ($item) => [
                                'action' => $item->action,
                                'subjectCode' => $item->action === 'swap' ? $item->replacedSection->subject->code : $item->section->subject->code,
                                'blockLabel' => $item->section->block_label,
                                'replacedBlockLabel' => $item->action === 'swap' ? $item->replacedSection->block_label : null,
                                'scheduleLabel' => implode('/', $item->section->days) . ' ' . $item->section->start_time . '–' . $item->section->end_time,
                                'room' => $item->action !== 'drop' ? $item->section->roomLabel() : null,
                            ])->values(),
                            'approveUrl' => route('approver.matriculation.approve', $change),
                            'rejectUrl' => route('approver.matriculation.reject', $change),
                        ])->values(),
                    ];
                @endphp

                <div id="approver-dashboard-root" data-context="{{ json_encode($context) }}" data-csrf-token="{{ csrf_token() }}">
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/approver-dashboard-app.jsx')
</body>
</html>
