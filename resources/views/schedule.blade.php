<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | My Schedule</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('js/qrcode.min.js') }}"></script>

    <style>
        @media print {
            body { background: white !important; }
            aside, header, .no-print { display: none !important; }
            main { overflow: visible !important; }
            .print-area { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="flex h-screen overflow-hidden">

    @include('partials.student-sidebar')

    {{-- ------------------- MAIN ------------------- --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        {{-- HEADER --}}
        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300 flex-shrink-0 no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">Schedule</h2>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()"
                    class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-print"></i>Print COR
                </button>
                <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.student-status-badge')
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT'])
                </div>
            </div>
        </header>

        {{-- CONTENT --}}
        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            <div id="schedule-root"
                 data-subjects="{{ json_encode($subjects ?? []) }}"
                 data-student-name="{{ Auth::user()->name ?? 'Student' }}"
                 data-student-id="{{ Auth::user()->login_id ?? 'N/A' }}"
                 data-student-program="{{ Auth::user()->major ?? 'BSIT - Web Development' }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>

        </div>{{-- end content --}}
    </main>
</div>


@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/schedule-app.jsx')
</body>
</html>
