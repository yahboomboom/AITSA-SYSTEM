<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="api-base" content="{{ url('/api') }}">
    <title>AITSA HQ | Curriculum Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
                    }
                }
            }
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
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">AITSA HQ</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Core Control</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.dashboard') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>System Overview</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50 rounded-xl font-medium text-sm transition-colors"><span>Manage Users</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50 rounded-xl font-medium text-sm transition-colors"><span>Clearance Settings</span>
            </a>
            <a href="{{ route('admin.curriculum') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.curriculum') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Curriculum</span>
            </a>
            <a href="{{ route('admin.departments') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.departments') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Departments</span>
            </a>
            <a href="{{ route('admin.audit') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.audit') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Audit Trail</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.reports') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Reports</span>
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden relative">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-8 z-10 flex-shrink-0 transition-colors duration-300">
            <div>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-200 tracking-wide">Curriculum Management</h2>
                <p class="text-[10px] text-brandNavy/50 dark:text-slate-500">Manage subject offerings per program and semester — A.Y. 2025–2026</p>
            </div>
            <div class="flex items-center gap-3">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', [
                    'roleLabel'     => 'Root Access Mode',
                    'roleClass'     => 'text-red-500 uppercase tracking-wider',
                    'avatarClass'   => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                    'avatarInitial' => 'A',
                ])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            {{-- Page title --}}
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Subject Catalogue</h1>
                <p class="text-xs text-brandNavy/60 dark:text-slate-400 mt-1">Select a program to view and manage its subject offerings. Toggle a subject's status to control student enrollment visibility.</p>
            </div>

            <div id="curriculum-root">
                <p class="text-sm text-slate-500">Loading curriculum editor…</p>
            </div>

        </div>
    </main>
</div>


@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/curriculum-app.jsx')
</body>
</html>
