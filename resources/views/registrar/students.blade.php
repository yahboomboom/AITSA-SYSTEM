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

    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
            <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA Staff</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
            <p class="px-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-3">Management</p>
            <a href="{{ route('registrar.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.dashboard') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">Dashboard</a>
            <a href="{{ route('registrar.students') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.students') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">Student Records</a>
            <a href="{{ route('registrar.reports') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.reports') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">Reports</a>
            <a href="{{ route('registrar.slots') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.slots') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">Admission Slots</a>
            <a href="{{ route('registrar.curriculum') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.curriculum') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">Curriculum</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
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
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">Manage academic grades and standing for enrolled students.</p>
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
                                    <th class="px-5 py-3"></th>
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
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('registrar.students.grades', $s->id) }}"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-brandGreen hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                            Manage Grades <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                        </a>
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
