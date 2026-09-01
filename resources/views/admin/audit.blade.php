<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Audit Trail</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="flex h-screen overflow-hidden">

    @include('partials.admin-sidebar')

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div>
                    <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Audit Trail</h2>
                    <p class="text-[10px] text-brandNavy/40 dark:text-slate-500">Immutable log of all system actions</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', [
                    'roleLabel'     => 'Root Access Mode',
                    'roleClass'     => 'text-red-500 uppercase tracking-wider',
                    'avatarClass'   => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                    'avatarInitial' => 'A',
                ])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-brandNavy dark:text-white">System Audit Trail</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">
                        {{ $logs->total() }} total entries &nbsp;&middot;&nbsp; Generated {{ now()->format('F d, Y h:i A') }}
                    </p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs"></i>
                        <input id="auditSearch" type="text" placeholder="Search actor or description…"
                            class="pl-8 pr-4 py-2 text-xs rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-500 outline-none focus:border-brandGreen/40 transition-colors w-56">
                    </div>
                    <select id="auditFilter" class="px-3 py-2 text-xs rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors">
                        <option value="all">All Actions</option>
                        <option value="Account Created">Account Created</option>
                        <option value="Applicant Verified">Applicant Verified</option>
                        <option value="Applicant Declined">Applicant Declined</option>
                        <option value="Clearance Signed">Clearance Signed</option>
                        <option value="Payment Approved">Payment Approved</option>
                        <option value="Admission Approved">Admission Approved</option>
                    </select>
                </div>
            </div>

            @php
                $ACTION_STYLE = [
                    'Account Created'   => ['dot' => 'bg-brandGreen',  'badge' => 'bg-brandGreen/10 text-brandGreen border-brandGreen/20',   'icon' => 'fa-user-plus'],
                    'Applicant Verified'=> ['dot' => 'bg-blue-500',    'badge' => 'bg-blue-500/10 text-blue-600 border-blue-500/20',         'icon' => 'fa-user-check'],
                    'Applicant Declined'=> ['dot' => 'bg-red-500',     'badge' => 'bg-red-500/10 text-red-500 border-red-500/20',            'icon' => 'fa-user-xmark'],
                    'Clearance Signed'  => ['dot' => 'bg-brandNavy',   'badge' => 'bg-brandNavy/10 text-brandNavy border-brandNavy/20 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600', 'icon' => 'fa-pen-nib'],
                    'Payment Approved'  => ['dot' => 'bg-orange-500',  'badge' => 'bg-orange-500/10 text-orange-600 border-orange-500/20',   'icon' => 'fa-cash-register'],
                    'Admission Approved'=> ['dot' => 'bg-purple-500',  'badge' => 'bg-purple-500/10 text-purple-600 border-purple-500/20',   'icon' => 'fa-circle-check'],
                ];
                $DEFAULT_STYLE = ['dot' => 'bg-slate-400', 'badge' => 'bg-brandNavy/5 text-brandNavy/50 border-brandNavy/10 dark:bg-slate-700 dark:text-slate-300', 'icon' => 'fa-bolt'];
            @endphp

            <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                <div class="bg-lightBg dark:bg-slate-800/40 px-5 py-3 border-b border-brandNavy/8 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i class="fa-solid fa-scroll mr-2 text-brandGreen"></i>Action Log
                    </span>
                    <span id="visibleCount" class="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500">{{ $logs->count() }} shown</span>
                </div>

                @if($logs->isEmpty())
                <div class="py-16 text-center">
                    <i class="fa-solid fa-scroll text-3xl text-brandNavy/15 dark:text-slate-700 mb-3 block"></i>
                    <p class="text-sm text-brandNavy/40 dark:text-slate-500">No audit entries yet.</p>
                    <p class="text-xs text-brandNavy/30 dark:text-slate-600 mt-1">Entries appear when staff perform actions.</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="auditTable">
                        <thead>
                            <tr class="text-[9px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800">
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">Timestamp</th>
                                <th class="py-3 px-4">Action</th>
                                <th class="py-3 px-4">Performed By</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4">Target</th>
                                <th class="py-3 px-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800" id="auditBody">
                            @foreach($logs as $i => $log)
                            @php $style = $ACTION_STYLE[$log->action] ?? $DEFAULT_STYLE; @endphp
                            <tr class="audit-row hover:bg-lightBg/50 dark:hover:bg-slate-800/20 transition-colors"
                                data-action="{{ $log->action }}"
                                data-search="{{ strtolower($log->actor_name . ' ' . $log->description) }}">
                                <td class="py-3 px-4 text-[10px] font-mono text-brandNavy/30 dark:text-slate-600">
                                    {{ str_pad(($logs->currentPage() - 1) * $logs->perPage() + $i + 1, 4, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-[11px] font-bold text-brandNavy dark:text-slate-200">{{ $log->created_at->format('M d, Y') }}</p>
                                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500 font-mono">{{ $log->created_at->format('h:i:s A') }}</p>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-black rounded border uppercase tracking-wider {{ $style['badge'] }}">
                                        <i class="fa-solid {{ $style['icon'] }} text-[8px]"></i>
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-[11px] font-bold text-brandNavy dark:text-slate-200">{{ $log->actor_name ?? 'System' }}</p>
                                    <p class="text-[9px] text-brandNavy/40 dark:text-slate-500">ID {{ $log->actor_id ?? '—' }}</p>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <p class="text-[11px] text-brandNavy/80 dark:text-slate-300 leading-snug">{{ $log->description }}</p>
                                </td>
                                <td class="py-3 px-4 text-[10px] font-mono text-brandNavy/50 dark:text-slate-500">
                                    @if($log->target_type)
                                        {{ $log->target_type }} #{{ $log->target_id }}
                                    @else —
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[10px] font-mono text-brandNavy/40 dark:text-slate-600">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                <div class="px-5 py-4 border-t border-brandNavy/8 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-[10px] text-brandNavy/40 dark:text-slate-500">
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
                    </p>
                    <div class="flex gap-1">
                        @if($logs->onFirstPage())
                            <span class="px-3 py-1.5 text-[10px] rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Prev</span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1.5 text-[10px] font-bold rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Prev</a>
                        @endif
                        @if($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1.5 text-[10px] font-bold rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Next</a>
                        @else
                            <span class="px-3 py-1.5 text-[10px] rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Next</span>
                        @endif
                    </div>
                </div>
                @endif
                @endif
            </div>

        </div>
    </main>
</div>

<script>
const rows        = document.querySelectorAll('.audit-row');
const searchInput = document.getElementById('auditSearch');
const filterSel   = document.getElementById('auditFilter');
const countEl     = document.getElementById('visibleCount');

function applyFilters() {
    const q      = searchInput ? searchInput.value.toLowerCase() : '';
    const action = filterSel ? filterSel.value : 'all';
    let visible  = 0;
    rows.forEach(row => {
        const matchSearch = row.dataset.search.includes(q);
        const matchAction = action === 'all' || row.dataset.action === action;
        const show = matchSearch && matchAction;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    if (countEl) countEl.textContent = visible + ' shown';
}

searchInput?.addEventListener('input', applyFilters);
filterSel?.addEventListener('change', applyFilters);
</script>

@include('partials.notif-script')
</body>
</html>
