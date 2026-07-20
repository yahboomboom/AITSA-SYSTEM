<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Department Chair Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
                <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Dean / Chair Controls</p>

                <a href="{{ route('approver.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 border-brandGreen text-brandGreen dark:text-emerald-400 font-bold text-sm transition-colors">
                    <span>Academic Approvals</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-bold text-brandNavy dark:text-slate-200">College Desk System</span>
                </div>
                <div class="flex items-center space-x-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel'     => 'CCS Academic Approver',
                        'roleClass'     => 'font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                        'avatarInitial' => strtoupper(substr(Auth::user()->name ?? 'C', 0, 1)),
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                @if(session('success'))
                    <div class="p-3.5 rounded-lg bg-brandGreen/8 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                <div class="space-y-1">
                    <h1 class="text-xl font-extrabold tracking-tight text-brandNavy dark:text-white">Department Chair Enrollment Approval</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400">Verify structural student clearance flags and sign off on active program updates.</p>
                </div>

                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest">
                                    <th class="py-3.5 px-5">Student Info</th>
                                    <th class="py-3.5 px-5">Student ID</th>
                                    <th class="py-3.5 px-5">Program</th>
                                    <th class="py-3.5 px-5 text-center">Status</th>
                                    <th class="py-3.5 px-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @foreach($clearances as $row)
                                <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="py-4 px-5 font-bold text-brandNavy dark:text-white">
                                        {{ $row->user->name ?? '—' }}
                                    </td>

                                    <td class="py-4 px-5 font-mono text-brandNavy/50 dark:text-slate-400">
                                        {{ $row->user->login_id ?? '—' }}
                                    </td>

                                    <td class="py-4 px-5 text-brandNavy/70 dark:text-slate-300">
                                        {{ $row->user->major ?? '—' }}
                                    </td>

                                    <td class="py-4 px-5 text-center">
                                        @if(($row->registrar_status ?? 'Pending') !== 'Approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                                Awaiting Registrar
                                            </span>
                                        @elseif(($row->chair_status ?? 'Pending') === 'Approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                                Fully Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 uppercase tracking-wider">
                                                Ready for Chair
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-4 px-5 text-right">
                                        @if(($row->registrar_status ?? 'Pending') !== 'Approved')
                                            <button disabled class="px-3.5 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 text-[11px] font-bold rounded cursor-not-allowed border border-brandNavy/8 dark:border-slate-700" title="Registrar must sign off first">
                                                <i class="fa-solid fa-lock mr-1.5"></i>Locked
                                            </button>
                                        @elseif(($row->chair_status ?? 'Pending') === 'Approved')
                                            <button disabled class="px-3.5 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-500 text-[11px] font-bold rounded cursor-not-allowed border border-brandNavy/8 dark:border-slate-700">
                                                <i class="fa-solid fa-check-double mr-1.5"></i>Approved
                                            </button>
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('approver.sign', isset($row->id) ? $row->id : 1) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-3.5 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('approver.hold', isset($row->id) ? $row->id : 1) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold" class="w-36 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                    <button type="submit" class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors">
                                                        Hold
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Irregular Enrollment Approval Queue --}}
                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
                    <h2 class="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                        <i class="fa-solid fa-user-graduate mr-2 text-brandGreen"></i>Pending Irregular Enrollments
                    </h2>

                    @if (session('error'))
                        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-4 py-3 text-sm">{{ session('error') }}</div>
                    @endif

                    @forelse (($pendingEnrollments ?? []) as $pending)
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <p class="font-semibold text-brandNavy dark:text-slate-100">{{ $pending->user->name }} ({{ $pending->user->login_id }})</p>
                                    <p class="text-xs text-slate-500">{{ $pending->user->major }} — {{ $pending->user->year_level }} — submitted {{ $pending->updated_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('approver.enrollments.approve', $pending) }}">
                                        @csrf
                                        <button class="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('approver.enrollments.reject', $pending) }}" class="flex gap-2">
                                        @csrf
                                        <input name="remarks" required maxlength="500" placeholder="Reason for rejection"
                                               class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm" />
                                        <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                                    </form>
                                </div>
                            </div>
                            <table class="w-full mt-3 text-sm">
                                <thead class="text-left text-xs uppercase text-slate-400">
                                    <tr><th class="py-1">Code</th><th>Title</th><th>Schedule</th><th>Room</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($pending->sections as $section)
                                        <tr class="border-t border-slate-100 dark:border-slate-800">
                                            <td class="py-1 font-mono">{{ $section->subject->code }}</td>
                                            <td>{{ $section->subject->title }}</td>
                                            <td>{{ implode('/', $section->days) }} {{ $section->start_time }}–{{ $section->end_time }}</td>
                                            <td>{{ $section->room }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No enrollments awaiting approval.</p>
                    @endforelse
                </div>

                {{-- Change of Matriculation Queue --}}
                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
                    <h2 class="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                        <i class="fa-solid fa-arrows-rotate mr-2 text-brandGold"></i>Change of Matriculation Requests
                    </h2>

                    @forelse (($pendingChanges ?? []) as $change)
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <p class="font-semibold text-brandNavy dark:text-slate-100">{{ $change->user->name }} ({{ $change->user->login_id }})</p>
                                    <p class="text-xs text-slate-500">{{ $change->user->major }} — {{ $change->user->year_level }} — filed {{ $change->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('approver.matriculation.approve', $change) }}">
                                        @csrf
                                        <button class="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('approver.matriculation.reject', $change) }}" class="flex gap-2">
                                        @csrf
                                        <input name="remarks" required maxlength="500" placeholder="Reason for rejection"
                                               class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm" />
                                        <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                                    </form>
                                </div>
                            </div>
                            <ul class="mt-3 space-y-1 text-sm">
                                @foreach ($change->items as $item)
                                    <li class="border-t border-slate-100 dark:border-slate-800 pt-1">
                                        @if ($item->action === 'add')
                                            <span class="font-bold text-brandGreen uppercase text-xs mr-2">Add</span>
                                            <span class="font-mono">{{ $item->section->subject->code }}</span>
                                            (Block {{ $item->section->block_label }}, {{ implode('/', $item->section->days) }} {{ $item->section->start_time }}–{{ $item->section->end_time }}, {{ $item->section->room }})
                                        @elseif ($item->action === 'drop')
                                            <span class="font-bold text-red-600 uppercase text-xs mr-2">Drop</span>
                                            <span class="font-mono">{{ $item->section->subject->code }}</span>
                                            (Block {{ $item->section->block_label }}, {{ implode('/', $item->section->days) }} {{ $item->section->start_time }}–{{ $item->section->end_time }})
                                        @else
                                            <span class="font-bold text-brandGold uppercase text-xs mr-2">Swap</span>
                                            <span class="font-mono">{{ $item->replacedSection->subject->code }}</span>
                                            Block {{ $item->replacedSection->block_label }} →
                                            Block {{ $item->section->block_label }}
                                            ({{ implode('/', $item->section->days) }} {{ $item->section->start_time }}–{{ $item->section->end_time }}, {{ $item->section->room }})
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No change requests awaiting approval.</p>
                    @endforelse
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
</body>
</html>
