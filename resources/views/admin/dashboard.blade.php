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

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

                    <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                        <p class="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Total Active Users</p>
                        <h4 class="text-2xl font-black text-brandNavy dark:text-white">1,248</h4>
                        <p class="text-[11px] text-brandNavy/40 dark:text-slate-500 mt-1">Across all role profiles</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                        <p class="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Clearances Settled</p>
                        <h4 class="text-2xl font-black text-brandNavy dark:text-white">412</h4>
                        <p class="text-[11px] text-brandGreen font-medium mt-1">33% total completion</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                        <p class="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Pending Queues</p>
                        <h4 class="text-2xl font-black text-brandNavy dark:text-white">836</h4>
                        <p class="text-[11px] text-brandNavy/40 dark:text-slate-500 mt-1">Awaiting staff signature reviews</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                        <p class="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">System Operational</p>
                        <h4 class="text-2xl font-black text-brandGreen">100%</h4>
                        <p class="text-[11px] text-brandNavy/40 dark:text-slate-500 mt-1">All database links connected</p>
                    </div>
                </div>

                @if(session('success'))
                <div class="p-3.5 rounded-lg bg-brandGreen/8 border border-brandGreen/20 text-brandGreen font-bold text-xs mb-4">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                </div>
                @endif

                <div class="mb-4">
                    <h4 class="text-base font-bold text-brandNavy dark:text-white">System Accounts</h4>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400">Quick oversight of operational node access tiers.</p>
                </div>

                <div class="bg-white dark:bg-panelDark rounded-lg border border-brandNavy/8 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 text-brandNavy/40 dark:text-slate-500 text-[10px] font-bold uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800">
                                    <th class="py-3.5 px-5">Official Name</th>
                                    <th class="py-3.5 px-5">Email</th>
                                    <th class="py-3.5 px-5">System ID</th>
                                    <th class="py-3.5 px-5">Role</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60 text-sm">
                                <tr class="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="py-4 px-5 font-bold text-brandNavy dark:text-white">Dr. Alex Santos</td>
                                    <td class="py-4 px-5 text-brandNavy/50 dark:text-slate-400">chair@aitsa.edu.ph</td>
                                    <td class="py-4 px-5 font-mono text-xs text-brandNavy/40 dark:text-slate-500">chair01</td>
                                    <td class="py-4 px-5"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">Department Chair</span></td>
                                </tr>
                                <tr class="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="py-4 px-5 font-bold text-brandNavy dark:text-white">Elena Cruz</td>
                                    <td class="py-4 px-5 text-brandNavy/50 dark:text-slate-400">cashier@aitsa.edu.ph</td>
                                    <td class="py-4 px-5 font-mono text-xs text-brandNavy/40 dark:text-slate-500">cashier01</td>
                                    <td class="py-4 px-5"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">Finance Cashier</span></td>
                                </tr>
                                <tr class="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="py-4 px-5 font-bold text-brandNavy dark:text-white">Roberto Diaz</td>
                                    <td class="py-4 px-5 text-brandNavy/50 dark:text-slate-400">registrar@aitsa.edu.ph</td>
                                    <td class="py-4 px-5 font-mono text-xs text-brandNavy/40 dark:text-slate-500">registrar01</td>
                                    <td class="py-4 px-5"><span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400">Institutional Registrar</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
</body>
</html>
