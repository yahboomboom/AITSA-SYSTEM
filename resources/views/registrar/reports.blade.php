<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Registrar Reports</title>
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
        function updateThemeIcon() {
            var icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
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

    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300 no-print">
        <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
            <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA Staff</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
            <p class="px-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-3">Management</p>
            <a href="{{ route('registrar.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors"><span>Registrar Workspaces</span></a>
            <a href="{{ route('registrar.students') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors"><span>Student Records</span></a>
            <a href="{{ route('registrar.reports') }}" class="flex items-center px-3 py-2.5 border-l-2 border-brandGreen text-brandGreen dark:text-emerald-400 font-bold text-sm"><span>Reports</span></a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-16 bg-white dark:bg-panelDark border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-8 z-10 no-print">
            <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Clearance Routing Report</h2>
            <div class="flex items-center gap-3">
                <button onclick="exportCSV()" class="hidden md:flex items-center gap-2 px-4 py-2 rounded text-xs font-bold bg-brandGreen hover:bg-brandNavy text-white transition-colors">
                    <i class="fa-solid fa-file-csv"></i>Export CSV
                </button>
                <button onclick="window.print()" class="hidden md:flex items-center gap-2 px-4 py-2 rounded text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                    <i class="fa-solid fa-print"></i>Print Report
                </button>
                <div class="flex items-center gap-3 border-l border-brandNavy/10 dark:border-slate-700 pl-3">
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel' => 'Registrar Portal',
                        'roleClass' => 'font-mono font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                    ])
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

            <div>
                <h1 class="text-2xl font-black text-brandNavy dark:text-white">Clearance Routing Report</h1>
                <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                    Academic Year 2025–2026 &nbsp;·&nbsp; 1st Semester &nbsp;·&nbsp;
                    Generated: {{ now()->format('F d, Y h:i A') }}
                </p>
            </div>

            @php
                $total      = $clearances->count();
                $regSigned  = $clearances->where('registrar_status', 'Approved')->count();
                $regPending = $total - $regSigned;
                $fullyClear = $clearances->filter(fn($c) =>
                    $c->chair_status === 'Approved' &&
                    $c->cashier_status === 'Approved' &&
                    $c->registrar_status === 'Approved'
                )->count();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/10 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-brandNavy/50 uppercase tracking-wider">Total Students</p>
                    <p class="text-3xl font-black text-brandNavy dark:text-white mt-1">{{ $total }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Active clearance records</p>
                </div>
                <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandGreen/20 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-brandGreen/70 uppercase tracking-wider">Registrar Signed</p>
                    <p class="text-3xl font-black text-brandGreen mt-1">{{ $regSigned }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Clearance signed off</p>
                </div>
                <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandGold/20 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-brandGold/70 uppercase tracking-wider">Awaiting Signature</p>
                    <p class="text-3xl font-black text-brandGold mt-1">{{ $regPending }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Still pending</p>
                </div>
                <div class="bg-white dark:bg-panelDark rounded-lg p-5 border border-blue-500/20 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-blue-500/70 uppercase tracking-wider">Fully Cleared</p>
                    <p class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $fullyClear }}</p>
                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">All offices approved</p>
                </div>
            </div>

            @if($pendingApplicants > 0)
            <div class="flex items-center gap-4 bg-brandGold/10 border border-brandGold/30 dark:bg-brandGold/5 dark:border-brandGold/20 rounded-lg px-5 py-4">
                <div class="w-9 h-9 rounded bg-brandGold/20 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-user-clock text-brandGold text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-brandNavy dark:text-slate-200">{{ $pendingApplicants }} Application(s) Awaiting Your Review</p>
                    <p class="text-xs text-brandNavy/60 dark:text-slate-400 mt-0.5">These applicants have not yet been verified. Go to the Registrar Workspace to process them.</p>
                </div>
                <a href="{{ route('registrar.dashboard') }}" class="flex-shrink-0 px-4 py-2 rounded text-xs font-bold bg-brandGold text-white hover:bg-brandNavy transition-colors">
                    Review Now
                </a>
            </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3 no-print">
                <div class="relative flex-1 max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs"></i>
                    <input id="searchInput" type="text" placeholder="Search student name..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-500 outline-none focus:border-brandGreen/40 transition-colors">
                </div>
                <select id="filterStatus" class="px-4 py-2 text-xs rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors">
                    <option value="all">All Students</option>
                    <option value="signed">Registrar Signed</option>
                    <option value="pending">Awaiting Signature</option>
                </select>
            </div>

            <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden">
                <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i class="fa-solid fa-table-list mr-2 text-brandGreen"></i>Clearance Routing Status per Student
                    </span>
                    <span id="rowCount" class="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500">{{ $total }} records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="reportTable">
                        <thead>
                            <tr class="text-[9px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-800/40">
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">Student Name</th>
                                <th class="py-3 px-4">Student No.</th>
                                <th class="py-3 px-4">Program</th>
                                <th class="py-3 px-4 text-center">Dept Chair</th>
                                <th class="py-3 px-4 text-center">Cashier</th>
                                <th class="py-3 px-4 text-center">Registrar</th>
                                <th class="py-3 px-4 text-center">Overall</th>
                                <th class="py-3 px-4 text-center no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800" id="reportBody">
                            @forelse($clearances as $i => $c)
                            @php
                                $regSigned = $c->registrar_status === 'Approved';
                                $isCleared = $c->chair_status === 'Approved' && $c->cashier_status === 'Approved' && $regSigned;
                            @endphp
                            <tr class="hover:bg-brandNavy/[0.02] dark:hover:bg-slate-800/20 transition-colors report-row"
                                data-name="{{ strtolower($c->user->name ?? '') }}"
                                data-status="{{ $regSigned ? 'signed' : 'pending' }}">
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
                                <td class="py-2.5 px-4 text-center">
                                    @if($isCleared)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black rounded bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                            <i class="fa-solid fa-circle-check text-[8px]"></i>Cleared
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black rounded bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                            <i class="fa-solid fa-clock text-[8px]"></i>Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-center no-print">
                                    @if(!$regSigned)
                                        <form action="{{ route('registrar.sign', $c->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 text-[10px] font-bold bg-brandNavy hover:bg-brandGreen text-white rounded transition-colors">
                                                <i class="fa-solid fa-pen-nib mr-1"></i>Sign
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] font-bold text-brandGreen/60 dark:text-emerald-600">
                                            <i class="fa-solid fa-check mr-1"></i>Signed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="py-10 text-center text-sm text-brandNavy/40 dark:text-slate-500">No student records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hidden print:block mt-6 pt-4 border-t border-slate-300 text-center text-[10px] text-slate-400">
                AITSA Clearance Routing Report &nbsp;·&nbsp; AY 2025–2026 1st Sem &nbsp;·&nbsp; Printed {{ now()->format('F d, Y h:i A') }}
            </div>

        </div>
    </main>
</div>

<script>
const searchInput  = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const rows         = document.querySelectorAll('.report-row');
const rowCount     = document.getElementById('rowCount');

function filterRows() {
    const q      = searchInput.value.toLowerCase();
    const status = filterStatus.value;
    let visible  = 0;
    rows.forEach(row => {
        const show = row.dataset.name.includes(q) && (status === 'all' || row.dataset.status === status);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    rowCount.textContent = visible + ' record' + (visible !== 1 ? 's' : '');
}

searchInput.addEventListener('input', filterRows);
filterStatus.addEventListener('change', filterRows);

function exportCSV() {
    const headers = ['#', 'Student Name', 'Student No.', 'Email', 'Dept Chair', 'Cashier', 'Registrar', 'Overall'];
    const visibleRows = [...rows].filter(r => r.style.display !== 'none');
    const csvRows = [headers.join(',')];
    visibleRows.forEach((row, i) => {
        const cells = row.querySelectorAll('td');
        csvRows.push([
            i + 1,
            '"' + (cells[1]?.querySelector('p')?.textContent.trim() ?? '') + '"',
            cells[2]?.textContent.trim() ?? '',
            '"' + (cells[1]?.querySelectorAll('p')[1]?.textContent.trim() ?? '') + '"',
            cells[3]?.textContent.trim() ?? '',
            cells[4]?.textContent.trim() ?? '',
            cells[5]?.textContent.trim() ?? '',
            cells[6]?.textContent.trim() ?? '',
        ].join(','));
    });
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'AITSA_Registrar_Report_AY2025–2026.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>
