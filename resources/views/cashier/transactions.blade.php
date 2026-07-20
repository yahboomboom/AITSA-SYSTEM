<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Cashier Ledger</title>
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

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-brandNavy dark:text-white">Transactions Ledger</h1>
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400">Historical clearance logging, transaction summaries, and audit records.</p>
                    </div>
                    <div class="text-xs font-mono text-brandNavy/50 dark:text-slate-400 bg-lightBg dark:bg-slate-800 border border-brandNavy/8 dark:border-slate-700 px-3 py-2 rounded">
                        System Date: <span class="text-brandNavy dark:text-white font-bold">{{ date('Y-m-d') }}</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                    <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <h2 class="text-sm font-bold text-brandNavy dark:text-white">Settled Audit Records</h2>

                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" id="ledgerSearchInput" placeholder="Search ref or student..." class="w-full text-xs bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="p-4">Transaction ID</th>
                                    <th class="p-4">Student Name</th>
                                    <th class="p-4">Reference</th>
                                    <th class="p-4">Amount</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4">Processor</th>
                                    <th class="p-4 text-right">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @if(isset($transactions) && count($transactions))
                                    @foreach($transactions as $t)
                                        <tr class="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                            <td class="p-4 font-mono text-brandNavy/40 dark:text-slate-500">
                                                #{{ sprintf('%05d', $t->id) }}
                                            </td>
                                            <td class="p-4 font-bold text-brandNavy dark:text-white">
                                                {{ $t->user->name ?? 'Unknown Student' }}
                                            </td>
                                            <td class="p-4 font-mono text-brandNavy/40 dark:text-slate-500">
                                                {{ $t->reference_no ?? 'N/A' }}
                                            </td>
                                            <td class="p-4 font-bold text-brandGreen">
                                                ₱ {{ number_format($t->amount ?? 3500, 2) }}
                                            </td>
                                            <td class="p-4">
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">
                                                    {{ $t->status ?? 'Success' }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-brandNavy/50 dark:text-slate-400">
                                                {{ $t->processor->name ?? 'System Override' }}
                                            </td>
                                            <td class="p-4 text-right text-brandNavy/40 dark:text-slate-500 font-mono">
                                                {{ $t->created_at ? $t->created_at->format('Y-m-d H:i') : date('Y-m-d H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">
                                            No transaction records yet.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        document.getElementById('ledgerSearchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.cells.length === 1) return;
                const name = row.cells[1].textContent.toLowerCase();
                const reference = row.cells[2].textContent.toLowerCase();
                row.style.display = (name.includes(searchTerm) || reference.includes(searchTerm)) ? '' : 'none';
            });
        });
    </script>
@include('partials.notif-script')
</body>
</html>
