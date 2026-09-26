<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Registrar Reports</title>
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

    @include('partials.registrar-sidebar')

    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300 no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" aria-label="Open sidebar menu" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h2 class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-100">Clearance routing report</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3 border-l border-brandNavy/10 dark:border-slate-700 pl-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel' => 'Registrar Portal',
                        'roleClass' => 'font-medium text-brandGreen',
                    ])
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

            @php
                $reportSemester = (int) \App\Models\Setting::get('semester', '1');
                $reportSemesterLabel = [1 => '1st', 2 => '2nd'][$reportSemester] ?? $reportSemester;
                $reportSchoolYear = \App\Models\Setting::get('school_year', '2026-2027');
            @endphp

            <div>
                <h1 class="font-heading text-lg font-semibold text-brandNavy dark:text-white">Clearance routing report</h1>
                <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                    Academic Year {{ $reportSchoolYear }} &nbsp;·&nbsp; {{ $reportSemesterLabel }} Semester &nbsp;·&nbsp;
                    Generated: {{ now()->format('F d, Y h:i A') }}
                </p>
            </div>

            <div id="registrar-reports-root" data-context="{{ json_encode($context) }}" data-csrf-token="{{ csrf_token() }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>

            <div class="hidden print:block mt-6 pt-4 border-t border-brandNavy/10 text-center text-xs text-brandNavy/40">
                AITSA Clearance Routing Report &nbsp;·&nbsp; A.Y. {{ $reportSchoolYear }}, {{ $reportSemesterLabel }} Semester &nbsp;·&nbsp; Printed {{ now()->format('F d, Y h:i A') }}
            </div>

        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/registrar-reports-app.jsx')
</body>
</html>
