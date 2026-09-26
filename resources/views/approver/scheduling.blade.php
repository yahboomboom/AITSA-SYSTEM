<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="api-base" content="{{ url('/api') }}">
    <title>AITSA Staff | Scheduling</title>
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
                    <button onclick="toggleMobileSidebar()" aria-label="Open sidebar menu" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Scheduling</span>
                </div>
                <div class="flex items-center space-x-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel'     => 'CCS Academic Approver',
                        'roleClass'     => 'font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                        'avatarInitial' => strtoupper(substr(Auth::user()->name ?? 'C', 0, 1)),
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Section Scheduling</h1>
                    <p class="text-xs text-brandNavy/60 dark:text-slate-400 mt-1">Browse a program's subjects to schedule their sections, and manage faculty/room assignments and loading.</p>
                </div>

                <div id="approver-scheduling-root">
                    <p class="text-sm text-slate-500">Loading scheduling…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/approver-scheduling-app.jsx')
</body>
</html>
