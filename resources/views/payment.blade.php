<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Ledger & Payments</title>
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
            <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
                <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
            </div>
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
                <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('dashboard') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Dashboard</span>
                </a>
                <a href="{{ route('clearance') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('clearance') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Clearance Routing</span>
                </a>
                <a href="{{ route('enrollment') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('enrollment') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Enrollment System</span>
                </a>
                <a href="{{ route('ledger') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('ledger') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Ledger & Payments</span>
                </a>
                <a href="/cor" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium"><span>Schedule</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">

            {{-- HEADER --}}
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center">
                    <button class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white mr-4">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">Ledger & Payments</h2>
                </div>

                <div class="flex items-center space-x-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT - Web Development'])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">
                
                @if(session('success'))
                    <div class="p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                        <i class="fa-solid fa-circle-xmark mr-2"></i>{{ session('error') }}
                    </div>
                @endif

                {{-- BALANCE OVERVIEW CARD --}}
                @php $settled = ($breakdown['balance'] ?? 0) <= 0; @endphp
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center">
                        <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                            <i class="fa-solid fa-wallet mr-2"></i>Account Balance
                        </span>
                        <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
                    </div>
                    <div class="p-6 lg:p-8 flex flex-col md:flex-row items-start justify-between gap-6">
                        <div class="space-y-2 text-center md:text-left">
                            @if($settled)
                                <p class="text-[10px] font-bold text-brandGreen uppercase tracking-widest">Outstanding Balance</p>
                                <h3 class="text-4xl font-black text-brandGreen">₱ 0.00</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">Your account has been fully settled with the Accounting Office.</p>
                            @else
                                <p class="text-[10px] font-bold text-brandGold uppercase tracking-widest">Outstanding Balance</p>
                                <h3 class="text-4xl font-black text-brandGold dark:text-amber-400">₱ {{ number_format($breakdown['balance'], 2) }}</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">Settle your balance to clear the cashier hold before enrollment.</p>
                            @endif

                            {{-- ASSESSMENT BREAKDOWN --}}
                            <div class="mt-4 bg-lightBg dark:bg-slate-900/40 border border-brandNavy/5 dark:border-slate-800 rounded-xl p-4 text-xs space-y-1.5 w-full md:w-80">
                                <p class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Assessment Breakdown</p>
                                <div class="flex justify-between">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Tuition ({{ $breakdown['units'] }} units × ₱{{ number_format($breakdown['rate'], 2) }})</span>
                                    <span class="font-bold text-brandNavy dark:text-slate-200">₱ {{ number_format($breakdown['tuition'], 2) }}</span>
                                </div>
                                @if($breakdown['discount_amount'] > 0)
                                    <div class="flex justify-between text-brandGreen">
                                        <span>{{ $breakdown['discount_name'] }} (−{{ $breakdown['discount_percent'] }}% tuition)</span>
                                        <span class="font-bold">− ₱ {{ number_format($breakdown['discount_amount'], 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Miscellaneous Fee</span>
                                    <span class="font-bold text-brandNavy dark:text-slate-200">₱ {{ number_format($breakdown['misc'], 2) }}</span>
                                </div>
                                <div class="flex justify-between pt-1.5 border-t border-brandNavy/10 dark:border-slate-800">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Total Assessment</span>
                                    <span class="font-bold text-brandNavy dark:text-slate-200">₱ {{ number_format($breakdown['assessment'], 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Payments Made</span>
                                    <span class="font-bold text-brandGreen">− ₱ {{ number_format($breakdown['paid'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 w-full md:w-auto">
                            @if($settled)
                                <button disabled class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-lightBg text-brandNavy/40 dark:bg-slate-800 dark:text-slate-500 font-bold rounded-xl text-xs uppercase tracking-wider cursor-not-allowed border border-brandNavy/10 dark:border-slate-700">
                                    <i class="fa-solid fa-circle-check"></i>Account Settled
                                </button>
                            @else
                                <form action="{{ route('ledger.checkout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md hover:shadow-brandGreen/25 hover:-translate-y-0.5 active:translate-y-0">
                                        <i class="fa-solid fa-credit-card"></i>Pay ₱ {{ number_format($breakdown['balance'], 2) }} via PayMongo
                                    </button>
                                </form>
                            @endif
                            @if($hasPendingGateway)
                                <form action="{{ route('ledger.verify') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-brandGold/10 hover:bg-brandGold text-brandGold hover:text-white border border-brandGold/30 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors">
                                        <i class="fa-solid fa-rotate"></i>Verify Payment
                                    </button>
                                </form>
                                <p class="text-[10px] text-brandNavy/50 dark:text-slate-500 text-center max-w-48">Finished paying on the gateway but the balance did not update? Verify here.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- PAYMENT HISTORY --}}
                @if(isset($history) && $history->isNotEmpty())
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <div class="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                            <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                                <i class="fa-solid fa-clock-rotate-left mr-2"></i>Payment History
                            </span>
                        </div>
                        <div class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                            @foreach($history as $row)
                                <div class="px-6 py-3.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <div>
                                        <span class="font-mono font-bold text-brandNavy dark:text-slate-200 block">{{ $row->reference_no }}</span>
                                        <span class="text-brandNavy/50 dark:text-slate-500">{{ $row->gateway === 'paymongo' ? 'PayMongo (online)' : 'Cashier window' }} · {{ $row->created_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="font-black text-brandNavy dark:text-slate-200">₱ {{ number_format($row->amount, 2) }}</span>
                                        @if($row->status === 'Settled')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Settled</span>
                                        @elseif($row->status === 'Pending')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                                        @elseif($row->status === 'Failed')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Failed</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-slate-500/10 text-slate-500 border border-slate-500/20 uppercase tracking-wider">{{ $row->status }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- CLEARANCE STATUS SUMMARY --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                        <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                            <i class="fa-solid fa-file-invoice mr-2"></i>Clearance Status Overview
                        </span>
                    </div>
                    <div class="p-6 space-y-3 text-xs">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid @if(isset($clearance) && $clearance->cashier_status === 'Approved') fa-circle-check text-brandGreen @else fa-circle-xmark text-brandGold @endif text-sm"></i>
                            <p class="text-brandNavy/70 dark:text-slate-400">Accounting Office — Balance Assessment</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-circle-check text-brandGreen text-sm"></i>
                            <p class="text-brandNavy/70 dark:text-slate-400">Library — No pending borrowed items on record</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid @if(isset($clearance) && $clearance->registrar_status === 'Approved') fa-circle-check text-brandGreen @else fa-circle-xmark text-red-500 @endif text-sm"></i>
                            <p class="text-brandNavy/70 dark:text-slate-400">Registrar — Administrative document verification</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid @if(isset($clearance) && $clearance->chair_status === 'Approved') fa-circle-check text-brandGreen @else fa-circle-xmark text-brandGold @endif text-sm"></i>
                            <p class="text-brandNavy/70 dark:text-slate-400">Department Head — Curriculum evaluation</p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
</body>
</html>