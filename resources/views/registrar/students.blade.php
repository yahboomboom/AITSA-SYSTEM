<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Student Records</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    @include('partials.registrar-sidebar')

    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <a href="{{ route('registrar.dashboard') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                    <i class="fa-solid fa-chevron-left mr-1"></i>Dashboard
                </a>
                <span class="text-brandNavy/20 dark:text-slate-700">/</span>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Student Records</h2>
            </div>
            <div class="flex items-center gap-3">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', ['roleLabel' => 'Registrar Portal'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-brandNavy dark:text-white">Student Records</h1>
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">View academic standing for enrolled students. Grades are recorded by faculty per section.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden">
                    @if($students->isEmpty())
                        <div class="py-16 text-center text-brandNavy/40 dark:text-slate-500">
                            <i class="fa-solid fa-users text-3xl mb-3 block opacity-40"></i>
                            <p class="text-sm font-semibold">No student accounts found.</p>
                            <p class="text-xs mt-1">Student accounts are created by the Admin.</p>
                        </div>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40">
                                    <th class="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">Student</th>
                                    <th class="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden sm:table-cell">Student ID</th>
                                    <th class="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden md:table-cell">Program</th>
                                    <th class="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden md:table-cell">Year</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                                @foreach($students as $s)
                                @php
                                    $failCount = \App\Models\StudentGrade::where('user_id', $s->id)->where('status', 'Failed')->count();
                                @endphp
                                <tr class="hover:bg-lightBg dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-brandNavy/10 dark:bg-slate-700 flex items-center justify-center text-xs font-black text-brandNavy dark:text-slate-300 flex-shrink-0">
                                                {{ strtoupper(substr($s->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-brandNavy dark:text-slate-200 text-sm leading-tight">{{ $s->name }}</p>
                                                <p class="text-xs text-brandNavy/40 dark:text-slate-500">{{ $s->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 hidden sm:table-cell">
                                        <span class="font-mono text-xs text-brandNavy/70 dark:text-slate-400">{{ $s->login_id }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 hidden md:table-cell">
                                        <span class="text-xs text-brandNavy/60 dark:text-slate-400">{{ $s->major ?? '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 hidden md:table-cell">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-brandNavy/60 dark:text-slate-400">{{ $s->year_level ?? '—' }}</span>
                                            @if($failCount > 0)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/10 text-amber-600">Irregular</span>
                                            @else
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-500/10 text-blue-600">Regular</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            </div>
        </div>
    </main>
</div>

@include('partials.notif-script')
</body>
</html>
