<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Reports</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            body { background: white !important; }
            main { overflow: visible !important; }
        }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="flex h-screen overflow-hidden">

    @include('partials.admin-sidebar')

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <span class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-200">System reports</span>
            </div>
            <div class="flex items-center gap-3">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', [
                    'roleLabel'   => 'Root Access Mode',
                    'roleClass'   => 'font-medium text-red-500',
                    'avatarClass' => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                    'avatarInitial' => 'A',
                ])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

            <div class="no-print">
                <h1 class="font-heading text-lg font-semibold text-brandNavy dark:text-white">Enrollment &amp; clearance report</h1>
                @php
                    $reportSemester = (int) \App\Models\Setting::get('semester', '1');
                    $reportSemesterLabel = [1 => '1st', 2 => '2nd'][$reportSemester] ?? $reportSemester;
                    $reportSchoolYear = \App\Models\Setting::get('school_year', '2026-2027');
                @endphp
                <p class="text-sm text-brandNavy/50 dark:text-slate-400 mt-0.5">
                    Academic Year {{ $reportSchoolYear }} &nbsp;·&nbsp; {{ $reportSemesterLabel }} Semester &nbsp;·&nbsp;
                    Generated: {{ now()->format('F d, Y h:i A') }}
                </p>
            </div>

            @php
                $total      = $clearances->count();
                $cleared    = $clearances->filter(fn($c) => $c->chair_status === 'Approved' && $c->cashier_status === 'Approved' && $c->registrar_status === 'Approved')->count();
                $pending    = $total - $cleared;
                $cashierOk  = $clearances->where('cashier_status', 'Approved')->count();

                $context = [
                    'schoolYear' => $reportSchoolYear,
                    'semesterLabel' => $reportSemesterLabel,
                    'summary' => [
                        'total' => $total,
                        'cleared' => $cleared,
                        'pending' => $pending,
                        'cashierOk' => $cashierOk,
                    ],
                    'pipeline' => [
                        'pendingApplicants' => $pendingApplicants,
                        'verifiedApplicants' => $verifiedApplicants,
                        'totalStudents' => $totalStudents,
                    ],
                    'agreements' => $agreements,
                    'programBreakdown' => $programBreakdown->map(fn ($prog) => [
                        'major' => $prog->major,
                        'count' => $prog->count,
                        'pct' => $totalStudents > 0 ? round(($prog->count / $totalStudents) * 100) : 0,
                    ])->values(),
                    'rows' => $clearances->values()->map(function ($c) {
                        $isCleared = $c->chair_status === 'Approved' && $c->cashier_status === 'Approved' && $c->registrar_status === 'Approved';
                        return [
                            'id' => $c->id,
                            'studentName' => $c->user->name ?? 'Unknown',
                            'studentEmail' => $c->user->email ?? '',
                            'studentNo' => $c->user->login_id ?? 'N/A',
                            'program' => $c->user->major ?? '—',
                            'chairStatus' => $c->chair_status,
                            'cashierStatus' => $c->cashier_status,
                            'registrarStatus' => $c->registrar_status,
                            'isCleared' => $isCleared,
                        ];
                    }),
                ];
            @endphp

            <div id="admin-reports-root" data-context="{{ json_encode($context) }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>

            {{-- Print footer (only visible when printing) --}}
            <div class="hidden print:block mt-6 pt-4 border-t border-brandNavy/10 text-center text-xs text-brandNavy/40">
                AITSA Enrollment &amp; Clearance Report &nbsp;·&nbsp; AY {{ $reportSchoolYear }}, {{ $reportSemesterLabel }} Semester &nbsp;·&nbsp; Printed {{ now()->format('F d, Y h:i A') }}
            </div>

        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/admin-reports-app.jsx')
</body>
</html>
