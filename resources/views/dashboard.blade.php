<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E'
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
        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
                <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Main Menu</p>

                <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 border-brandGreen text-brandGreen dark:text-emerald-400 font-bold text-sm transition-colors">
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('clearance') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Clearance Routing</span>
                </a>

                <a href="{{ route('enrollment') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Enrollment System</span>
                </a>

                <a href="{{ route('ledger') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Ledger & Payments</span>
                </a>

                <a href="/cor" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Schedule</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">

            {{-- HEADER --}}
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center">
                    <button class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white mr-4">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Student Portal</h2>
                </div>

                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT - Web Development'])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

                {{-- WELCOME SECTION --}}
                <div class="mb-6">
                    <h1 class="text-2xl font-black text-brandNavy dark:text-white mb-1">Welcome to <span class="text-brandGreen">AITSA</span></h1>
                    <p class="text-sm text-brandNavy/50 dark:text-slate-400">Asian Institute of Technology, Science &amp; Arts</p>
                </div>

                {{-- ANNOUNCEMENTS SECTION --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
                    <h2 class="text-sm font-bold text-brandNavy dark:text-white mb-4">
                        <i class="fa-solid fa-bullhorn text-brandGreen mr-2"></i>Announcements
                    </h2>
                    <div class="text-center text-brandNavy/40 dark:text-slate-500 py-4">
                        <p class="text-sm">No announcements at this time.</p>
                    </div>
                </div>

            </div>
        </main>
    </div>


@include('partials.notif-script')
</body>
</html>
