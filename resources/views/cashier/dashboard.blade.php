<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Cashier Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.cashier-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 flex-shrink-0 z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleMobileSidebar()" aria-label="Open sidebar menu" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <span class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-200">Cashier operations</span>
                </div>
                <div class="flex items-center gap-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Cashier Staff'])
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                @if(session('success'))
                    <div class="bg-brandGreen/10 border border-brandGreen/20 text-brandGreen px-4 py-3 rounded text-sm flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded text-sm flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="font-heading text-lg font-semibold text-brandNavy dark:text-white">Cashier console</h1>
                        <p class="text-sm text-brandNavy/50 dark:text-slate-400">Review student transactions, handle validation workflows, and sign off clearances.</p>
                    </div>
                    <div class="text-xs font-mono text-brandNavy/50 dark:text-slate-400 bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 px-3 py-2 rounded">
                        System date: <span class="text-brandNavy dark:text-white font-medium">{{ date('Y-m-d') }}</span>
                    </div>
                </div>

                <div
                    id="cashier-dashboard-root"
                    data-context="{{ json_encode($context) }}"
                    data-csrf-token="{{ csrf_token() }}"
                    data-approve-url="{{ route('cashier.approve') }}"
                    data-hold-url="{{ route('cashier.hold') }}"
                    data-waive-down-payment-url="{{ route('cashier.waive-down-payment') }}"
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
