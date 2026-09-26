<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Department Clearance Queue</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">
        @include('partials.department-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleMobileSidebar()" aria-label="Open sidebar menu" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h2 class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-100">{{ Auth::user()->department->name ?? 'Department' }} clearance queue</h2>
                </div>
                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Department Officer'])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">
                @if(session('success'))
                    <div class="p-4 rounded bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-sm">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded bg-red-600/10 border border-red-600/20 text-red-600 text-sm">{{ $errors->first() }}</div>
                @endif

                @php
                    $context = $items->map(fn ($item) => [
                        'id' => $item->id,
                        'studentName' => $item->clearance->user->name ?? 'Unknown',
                        'studentId' => $item->clearance->user->login_id ?? 'N/A',
                        'status' => $item->status,
                        'remarks' => $item->remarks,
                        'approveUrl' => route('department.items.approve', $item),
                        'holdUrl' => route('department.items.hold', $item),
                    ])->values();
                @endphp

                <div id="department-dashboard-root" data-context="{{ json_encode($context) }}" data-csrf-token="{{ csrf_token() }}">
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>
            </div>
        </main>
    </div>
@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/department-dashboard-app.jsx')
</body>
</html>
