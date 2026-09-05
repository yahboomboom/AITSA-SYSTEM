<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Central Administration</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.admin-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white mr-4">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Super Administrator</h2>
                </div>

                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>

                    @include('partials.profile-menu', [
                        'roleLabel'   => 'Root Access Mode',
                        'roleClass'   => 'text-red-500 uppercase tracking-wider',
                        'avatarClass' => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                        'avatarInitial' => 'A',
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-8">

                <div class="mb-6">
                    <h3 class="text-xl font-black text-brandNavy dark:text-white">System Analytics</h3>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">Institutional metrics for running portals, clearances, and user registration modules.</p>
                </div>

                @if(session('success'))
                <div class="p-3.5 rounded-lg bg-brandGreen/8 border border-brandGreen/20 text-brandGreen font-bold text-xs mb-4">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                </div>
                @endif

                <div id="admin-dashboard-root" data-context="{{ json_encode($context) }}">
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/admin-dashboard-app.jsx')
</body>
</html>
