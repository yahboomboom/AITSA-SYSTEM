<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Cashier Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <h1 class="text-base font-black text-brandNavy dark:text-white">AITSA <span class="text-xs text-brandGreen font-mono px-1.5 py-0.5 bg-brandGreen/10 rounded ml-1">Staff</span></h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Management</p>

                <a href="{{ route('cashier.dashboard') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is('cashier.dashboard') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }}">
                    <span>Cashier Hub</span>
                </a>

                <a href="{{ route('cashier.transactions') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is('cashier.transactions') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }}">
                    <span>Transactions</span>
                </a>

                <a href="{{ route('cashier.accounts') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is('cashier.accounts') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }}">
                    <span>Student Accounts</span>
                </a>

                <a href="{{ route('cashier.billing') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is('cashier.billing') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }}">
                    <span>Billing Setup</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 flex-shrink-0 z-10 transition-colors duration-300">
                <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Cashier Operations</span>
                <div class="flex items-center gap-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Cashier Staff'])
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                @if(session('success'))
                    <div class="bg-brandGreen/8 border border-brandGreen/20 text-brandGreen px-4 py-3 rounded-lg text-xs font-semibold flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-500/8 border border-red-500/20 text-red-500 px-4 py-3 rounded-lg text-xs font-semibold flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-brandNavy dark:text-white">Cashier Console</h1>
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400">Review student transactions, handle validation workflows, and sign off clearances.</p>
                    </div>
                    <div class="text-xs font-mono text-brandNavy/50 dark:text-slate-400 bg-lightBg dark:bg-slate-800 border border-brandNavy/8 dark:border-slate-700 px-3 py-2 rounded">
                        System Date: <span class="text-brandNavy dark:text-white font-bold">{{ date('Y-m-d') }}</span>
                    </div>
                </div>

                <div
                    id="cashier-dashboard-root"
                    data-context="{{ json_encode($context) }}"
                    data-csrf-token="{{ csrf_token() }}"
                    data-approve-url="{{ route('cashier.approve') }}"
                    data-hold-url="{{ route('cashier.hold') }}"
                >
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/cashier-dashboard-app.jsx')
</body>
</html>
