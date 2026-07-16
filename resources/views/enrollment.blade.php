<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E'
            }}}
        }
    </script>
    <script>
        function updateThemeIcon() {
            var icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black text-brandNavy dark:text-white">AITSA</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('dashboard') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Dashboard</a>
            <a href="{{ route('clearance') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('clearance') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Clearance Routing</a>
            <a href="{{ route('enrollment') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('enrollment') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Enrollment</a>
            <a href="{{ route('ledger') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('ledger') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Payments</a>
            <a href="/cor" class="flex items-center px-4 py-3 rounded-xl text-sm text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium transition-colors duration-300">Schedule</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">Enrollment</h2>
            </div>
            <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-5">

                <div id="enrollment-root">
                    <p class="text-sm text-slate-500">Loading your enrollment…</p>
                </div>


        </div>
    </main>
</div>


@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/enrollment-app.jsx')
</body>
</html>
