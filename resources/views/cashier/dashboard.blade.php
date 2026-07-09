<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Cashier Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D',
                        brandGreen: '#1D7A46',
                        brandGold: '#E2A700',
                        darkBg: '#121212',
                        lightBg: '#EFF3F7',
                        panelDark: '#1E1E1E'
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-5 space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brandNavy/40 dark:text-slate-500">Total Outstanding</p>
                        <h3 class="text-2xl font-black text-brandGold">₱ {{ number_format(($totalOutstandingDocs ?? 0) * 3500, 2) }}</h3>
                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-500">Estimated value across remaining clear routes.</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-5 space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brandNavy/40 dark:text-slate-500">Settled Base</p>
                        <h3 class="text-2xl font-black text-brandGreen">₱ 24,500.00</h3>
                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-500">7 verified gateway updates logged.</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-5 space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brandNavy/40 dark:text-slate-500">Pending Actions</p>
                        <h3 class="text-2xl font-black text-brandNavy dark:text-slate-200">{{ $totalOutstandingDocs ?? 0 }} Students</h3>
                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-500">Awaiting clearance validation.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                    <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <h2 class="text-sm font-bold text-brandNavy dark:text-white">Clearance Evaluation Queue</h2>

                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" id="queueSearchInput" placeholder="Search student name or ID..." class="w-full text-xs bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="p-4">Student Info</th>
                                    <th class="p-4">Reference</th>
                                    <th class="p-4">Outstanding Bal</th>
                                    <th class="p-4">Clearance</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($clearances as $item)
                                    <tr class="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="p-4">
                                            <div class="font-bold text-brandNavy dark:text-white">
                                                {{ $item->user->name ?? 'Unknown Student' }}
                                            </div>
                                            <div class="text-[10px] text-brandNavy/40 dark:text-slate-500">
                                                {{ $item->user->email ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="p-4 font-mono text-brandNavy/40 dark:text-slate-500">
                                            TXN-{{ 10000 + ($item->user_id ?? 0) }}-WIT
                                        </td>
                                        <td class="p-4 font-bold {{ $item->cashier_status === 'Approved' ? 'text-brandGreen' : 'text-brandGold' }}">
                                            {{ $item->cashier_status === 'Approved' ? '₱ 0.00' : '₱ 3,500.00' }}
                                        </td>
                                        <td class="p-4">
                                            @if($item->cashier_status === 'Approved')
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Approved</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGold/10 text-brandGold border border-brandGold/20 rounded">Pending</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-right">
                                            @if($item->cashier_status === 'Approved')
                                                <button disabled class="px-3 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 rounded text-[11px] font-medium border border-brandNavy/8 dark:border-slate-700 cursor-not-allowed">
                                                    <i class="fa-solid fa-check mr-1"></i>Settled
                                                </button>
                                            @else
                                                <button onclick="openReviewModal({{ $item->user_id ?? 0 }}, '{{ addslashes($item->user->name ?? 'Unknown') }}', '₱ 3,500.00', 'TXN-{{ 10000 + ($item->user_id ?? 0) }}-WIT')" class="px-3 py-1.5 bg-brandNavy hover:bg-brandGreen text-white rounded transition-colors font-bold text-[11px]">
                                                    Review
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">
                                            No students pending clearance.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    {{-- Review Modal --}}
    <div id="reviewModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg w-full max-w-md overflow-hidden">
            <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800 flex items-center justify-between">
                <span class="text-sm font-bold text-brandNavy dark:text-white">Review Clearance</span>
                <button type="button" onclick="closeReviewModal()" class="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6 space-y-5">
                <div class="space-y-1">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-brandNavy/40 dark:text-slate-500">Evaluating</p>
                    <h3 id="modalStudentName" class="text-base font-bold text-brandNavy dark:text-white">Student Name</h3>
                </div>

                <div class="bg-lightBg dark:bg-slate-800/60 rounded-lg p-4 border border-brandNavy/8 dark:border-slate-700 text-xs space-y-2">
                    <div class="flex justify-between"><span class="text-brandNavy/50 dark:text-slate-400">Payment Ref:</span><span id="modalRef" class="text-brandNavy dark:text-slate-300 font-mono">---</span></div>
                    <div class="flex justify-between items-center font-bold pt-2 border-t border-brandNavy/8 dark:border-slate-700"><span class="text-brandNavy/50 dark:text-slate-400">Balance:</span><span id="modalBalance" class="text-brandGold text-base">₱ 0.00</span></div>
                </div>

                <form id="overrideForm" action="{{ route('cashier.approve') }}" method="POST" class="grid grid-cols-1 gap-2">
                    @csrf
                    <input type="hidden" name="user_id" id="modalStudentId" value="">
                    <input type="hidden" name="reference_no" id="modalFormRef" value="">
                    <input type="hidden" name="amount" id="modalFormAmount" value="">

                    <button type="submit" class="w-full py-3 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded text-xs uppercase tracking-wider transition-colors">
                        <i class="fa-solid fa-circle-check mr-2"></i>Approve & Sign Off
                    </button>
                    <button type="button" onclick="closeReviewModal()" class="w-full py-3 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/5 text-brandNavy/60 dark:text-slate-400 text-xs font-bold rounded transition-colors">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openReviewModal(userId, name, balance, ref) {
            document.getElementById('modalStudentName').innerText = name;
            document.getElementById('modalBalance').innerText = balance;
            document.getElementById('modalRef').innerText = ref;
            document.getElementById('modalStudentId').value = userId;
            document.getElementById('modalFormRef').value = ref;
            document.getElementById('modalFormAmount').value = balance;
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.getElementById('reviewModal').classList.remove('flex');
        }

        document.getElementById('queueSearchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.cells.length === 1) return;
                const name = row.cells[0].textContent.toLowerCase();
                const reference = row.cells[1].textContent.toLowerCase();
                row.style.display = (name.includes(searchTerm) || reference.includes(searchTerm)) ? '' : 'none';
            });
        });
    </script>
@include('partials.notif-script')
</body>
</html>
