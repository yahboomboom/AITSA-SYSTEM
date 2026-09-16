<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Registrar Reports</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
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
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Clearance Routing Report</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3 border-l border-brandNavy/10 dark:border-slate-700 pl-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel' => 'Registrar Portal',
                        'roleClass' => 'font-mono font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                    ])
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

            <div>
                <h1 class="text-2xl font-black text-brandNavy dark:text-white">Clearance Routing Report</h1>
                <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                    Academic Year 2025–2026 &nbsp;·&nbsp; 1st Semester &nbsp;·&nbsp;
                    Generated: {{ now()->format('F d, Y h:i A') }}
                </p>
            </div>

            <div id="registrar-reports-root" data-context="{{ json_encode($context) }}" data-csrf-token="{{ csrf_token() }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>

            <div class="hidden print:block mt-6 pt-4 border-t border-slate-300 text-center text-[10px] text-slate-400">
                AITSA Clearance Routing Report &nbsp;·&nbsp; AY 2025–2026 1st Sem &nbsp;·&nbsp; Printed {{ now()->format('F d, Y h:i A') }}
            </div>

        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/registrar-reports-app.jsx')
</body>
</html>
