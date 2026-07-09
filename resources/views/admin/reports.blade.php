<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
            }}}
        }
    </script>
    <script>
        if ((localStorage.getItem('theme') || 'light') === 'dark') document.documentElement.classList.add('dark');
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            body { background: white !important; }
            main { overflow: visible !important; }
        }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-slate-200 dark:border-slate-800 no-print">
        <div class="h-20 flex items-center px-8 border-b border-slate-200 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">AITSA HQ</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Core Control</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors"><span>System Overview</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors"><span>Manage Users</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors"><span>Clearance Settings</span>
            </a>
            <a href="{{ route('admin.curriculum') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.curriculum') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Curriculum</span>
            </a>
            <a href="{{ route('admin.audit') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.audit') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Audit Trail</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.reports') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Reports</span>
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 no-print">
            <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">System Reports</h2>
            <div class="flex items-center gap-3">
                <button onclick="exportCSV()" class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brandGreen hover:bg-brandNavy text-white transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-file-csv"></i>Export CSV
                </button>
                <button onclick="window.print()" class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-print"></i>Print Report
                </button>
                <div class="flex items-center gap-3 border-l border-slate-200 dark:border-slate-700 pl-3">
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel'   => 'Root Access Mode',
                        'roleClass'   => 'text-red-500 uppercase tracking-wider',
                        'avatarClass' => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                        'avatarInitial' => 'A',
                    ])
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            {{-- Title --}}
            <div>
                <h1 class="text-3xl font-black text-brandNavy dark:text-white">Enrollment & Clearance Report</h1>
                <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                    Academic Year 2025–2026 &nbsp;·&nbsp; 1st Semester &nbsp;·&nbsp;
                    Generated: {{ now()->format('F d, Y h:i A') }}
                </p>
            </div>

            {{-- Summary Cards --}}
            @php
                $total      = $clearances->count();
                $cleared    = $clearances->filter(fn($c) => $c->chair_status === 'Approved' && $c->cashier_status === 'Approved' && $c->registrar_status === 'Approved')->count();
                $pending    = $total - $cleared;
                $cashierOk  = $clearances->where('cashier_status', 'Approved')->count();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-panelDark rounded-2xl p-5 border border-brandNavy/10 dark:border-slate-800 shadow-sm">
                    <p class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-wider">Enrolled Students</p>
                    <p class="text-3xl font-black text-brandNavy dark:text-white mt-1">{{ $total }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Active clearance records</p>
                </div>
                <div class="bg-white dark:bg-panelDark rounded-2xl p-5 border border-brandGreen/20 dark:border-slate-800 shadow-sm">
                    <p class="text-[10px] font-bold text-brandGreen/70 uppercase tracking-wider">Fully Cleared</p>
                    <p class="text-3xl font-black text-brandGreen mt-1">{{ $cleared }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">All offices signed off</p>
                </div>
                <div class="bg-white dark:bg-panelDark rounded-2xl p-5 border border-brandGold/20 dark:border-slate-800 shadow-sm">
                    <p class="text-[10px] font-bold text-brandGold/70 uppercase tracking-wider">Pending Clearance</p>
                    <p class="text-3xl font-black text-brandGold mt-1">{{ $pending }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Still in process</p>
                </div>
                <div class="bg-white dark:bg-panelDark rounded-2xl p-5 border border-blue-500/20 dark:border-slate-800 shadow-sm">
                    <p class="text-[10px] font-bold text-blue-500/70 uppercase tracking-wider">Payment Settled</p>
                    <p class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $cashierOk }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Cashier approved</p>
                </div>
            </div>

            {{-- Admission Pipeline --}}
            <div class="bg-white dark:bg-panelDark rounded-2xl border border-brandNavy/10 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i class="fa-solid fa-arrows-turn-right mr-2 text-brandGreen"></i>Admission Pipeline
                    </span>
                </div>
                <div class="grid grid-cols-3 divide-x divide-brandNavy/8 dark:divide-slate-700">
                    <div class="p-5 text-center">
                        <p class="text-[9px] font-bold text-brandGold/80 uppercase tracking-wider mb-1">Pending Review</p>
                        <p class="text-3xl font-black text-brandGold">{{ $pendingApplicants }}</p>
                        <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Awaiting Registrar</p>
                    </div>
                    <div class="p-5 text-center">
                        <p class="text-[9px] font-bold text-blue-500/80 uppercase tracking-wider mb-1">Verified</p>
                        <p class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ $verifiedApplicants }}</p>
                        <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Awaiting Account</p>
                    </div>
                    <div class="p-5 text-center">
                        <p class="text-[9px] font-bold text-brandGreen/80 uppercase tracking-wider mb-1">Enrolled</p>
                        <p class="text-3xl font-black text-brandGreen">{{ $totalStudents }}</p>
                        <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Active student accounts</p>
                    </div>
                </div>
            </div>

            {{-- Program Breakdown --}}
            @if($programBreakdown->count() > 0)
            <div class="bg-white dark:bg-panelDark rounded-2xl border border-brandNavy/10 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i class="fa-solid fa-chart-bar mr-2 text-brandGreen"></i>Students by Program
                    </span>
                </div>
                <div class="p-5 space-y-3">
                    @foreach($programBreakdown as $prog)
                    @php $pct = $totalStudents > 0 ? round(($prog->count / $totalStudents) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-brandNavy dark:text-slate-200">{{ $prog->major }}</span>
                            <span class="text-[10px] font-black text-brandNavy/50 dark:text-slate-400">{{ $prog->count }} <span class="font-normal">({{ $pct }}%)</span></span>
                        </div>
                        <div class="h-1.5 bg-brandNavy/8 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-brandGreen rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Filter + Search --}}
            <div class="flex flex-col sm:flex-row gap-3 no-print">
                <div class="relative flex-1 max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs"></i>
                    <input id="searchInput" type="text" placeholder="Search student name..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-500 outline-none focus:border-brandGreen/40 transition-colors">
                </div>
                <select id="filterStatus" class="px-4 py-2 text-xs rounded-xl border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors">
                    <option value="all">All Students</option>
                    <option value="cleared">Fully Cleared</option>
                    <option value="pending">Pending Clearance</option>
                </select>
            </div>

            {{-- Report Table --}}
            <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i class="fa-solid fa-table-list mr-2 text-brandGreen"></i>Enrollment & Clearance Status
                    </span>
                    <span id="rowCount" class="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500">{{ $total }} records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="reportTable">
                        <thead>
                            <tr class="text-[9px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800">
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">Student Name</th>
                                <th class="py-3 px-4">Student No.</th>
                                <th class="py-3 px-4">Program</th>
                                <th class="py-3 px-4 text-center">Chair</th>
                                <th class="py-3 px-4 text-center">Cashier</th>
                                <th class="py-3 px-4 text-center">Registrar</th>
                                <th class="py-3 px-4 text-center">Library</th>
                                <th class="py-3 px-4 text-center">Clinic</th>
                                <th class="py-3 px-4 text-center">Overall</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800" id="reportBody">
                            @forelse($clearances as $i => $c)
                            @php
                                $isCleared = $c->chair_status === 'Approved'
                                    && $c->cashier_status === 'Approved'
                                    && $c->registrar_status === 'Approved';
                            @endphp
                            <tr class="hover:bg-brandNavy/[0.02] dark:hover:bg-slate-800/20 transition-colors report-row"
                                data-name="{{ strtolower($c->user->name ?? '') }}"
                                data-status="{{ $isCleared ? 'cleared' : 'pending' }}">
                                <td class="py-2.5 px-4 text-[11px] font-mono text-brandNavy/40 dark:text-slate-500">{{ str_pad($i + 1, 3, '0', STR_PAD_LEFT) }}</td>
                                <td class="py-2.5 px-4">
                                    <p class="text-[11px] font-bold text-brandNavy dark:text-slate-200">{{ $c->user->name ?? 'Unknown' }}</p>
                                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500">{{ $c->user->email ?? '' }}</p>
                                </td>
                                <td class="py-2.5 px-4 text-[11px] font-mono text-brandNavy/60 dark:text-slate-400">{{ $c->user->login_id ?? 'N/A' }}</td>
                                <td class="py-2.5 px-4 text-[11px] text-brandNavy/70 dark:text-slate-400">{{ $c->user->major ?? '—' }}</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->chair_status])</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->cashier_status])</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->registrar_status])</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->library_status])</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->clinic_status])</td>
                                <td class="py-2.5 px-4 text-center">
                                    @if($isCleared)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black rounded-full bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                            <i class="fa-solid fa-circle-check text-[8px]"></i>Cleared
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black rounded-full bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                            <i class="fa-solid fa-clock text-[8px]"></i>Pending
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="py-10 text-center text-sm text-brandNavy/40 dark:text-slate-500">No student records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Print footer (only visible when printing) --}}
            <div class="hidden print:block mt-6 pt-4 border-t border-slate-300 text-center text-[10px] text-slate-400">
                AITSA Enrollment & Clearance Report &nbsp;·&nbsp; AY 2025–2026 1st Sem &nbsp;·&nbsp; Printed {{ now()->format('F d, Y h:i A') }}
            </div>

        </div>
    </main>
</div>

<script>
// -- Search & Filter -----------------------------------------------------------
const searchInput  = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const rows         = document.querySelectorAll('.report-row');
const rowCount     = document.getElementById('rowCount');

function filterRows() {
    const q      = searchInput.value.toLowerCase();
    const status = filterStatus.value;
    let visible  = 0;
    rows.forEach(row => {
        const nameMatch   = row.dataset.name.includes(q);
        const statusMatch = status === 'all' || row.dataset.status === status;
        const show = nameMatch && statusMatch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    rowCount.textContent = visible + ' record' + (visible !== 1 ? 's' : '');
}

searchInput.addEventListener('input', filterRows);
filterStatus.addEventListener('change', filterRows);

// -- CSV Export ----------------------------------------------------------------
function exportCSV() {
    const headers = ['#', 'Student Name', 'Student No.', 'Email', 'Chair', 'Cashier', 'Registrar', 'Library', 'Clinic', 'Overall'];
    const visibleRows = [...rows].filter(r => r.style.display !== 'none');

    const csvRows = [headers.join(',')];
    visibleRows.forEach((row, i) => {
        const cells = row.querySelectorAll('td');
        const rowData = [
            i + 1,
            '"' + (cells[1]?.querySelector('p')?.textContent.trim() ?? '') + '"',
            cells[2]?.textContent.trim() ?? '',
            '"' + (cells[1]?.querySelectorAll('p')[1]?.textContent.trim() ?? '') + '"',
            cells[3]?.textContent.trim() ?? '',
            cells[4]?.textContent.trim() ?? '',
            cells[5]?.textContent.trim() ?? '',
            cells[6]?.textContent.trim() ?? '',
            cells[7]?.textContent.trim() ?? '',
            cells[8]?.textContent.trim() ?? '',
        ];
        csvRows.push(rowData.join(','));
    });

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'AITSA_Report_AY2025–2026_1stSem.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>
