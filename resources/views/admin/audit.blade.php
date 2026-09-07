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
            </div>

            @php
                $context = [
                    'logs' => $logs->values()->map(fn ($log, $i) => [
                        'seq' => ($logs->currentPage() - 1) * $logs->perPage() + $i + 1,
                        'action' => $log->action,
                        'date' => $log->created_at->format('M d, Y'),
                        'time' => $log->created_at->format('h:i:s A'),
                        'actorName' => $log->actor_name ?? 'System',
                        'actorId' => $log->actor_id,
                        'description' => $log->description,
                        'targetType' => $log->target_type,
                        'targetId' => $log->target_id,
                        'ipAddress' => $log->ip_address,
                    ]),
                    'pagination' => [
                        'total' => $logs->total(),
                        'firstItem' => $logs->firstItem(),
                        'lastItem' => $logs->lastItem(),
                        'prevPageUrl' => $logs->previousPageUrl(),
                        'nextPageUrl' => $logs->nextPageUrl(),
                    ],
                ];
            @endphp

            <div id="admin-audit-root" data-context="{{ json_encode($context) }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>

        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/admin-audit-app.jsx')
</body>
</html>
