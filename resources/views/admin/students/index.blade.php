<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Student Registry</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA HQ</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Core Control</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>System Overview</span>
            </a>
            <a href="{{ route('admin.students.create') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Create Student Account</span>
            </a>
            <a href="{{ route('admin.students.index') }}" class="flex items-center space-x-3 px-4 py-3 bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 rounded-xl font-bold text-sm">
                <span>Student Registry</span>
            </a>
            <a href="{{ route('admin.curriculum') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Curriculum</span>
            </a>
            <a href="{{ route('admin.departments') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Departments</span>
            </a>
            <a href="{{ route('admin.audit') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Audit Trail</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Reports</span>
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                    <i class="fa-solid fa-chevron-left mr-1"></i>Dashboard
                </a>
                <span class="text-brandNavy/20 dark:text-slate-700">/</span>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Student Registry</h2>
            </div>
            <div class="flex items-center gap-4">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', ['roleLabel' => 'Administrator'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10">

            @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-sm">
                {{ session('success') }}
            </div>
            @endif

            <div class="max-w-6xl mx-auto space-y-6">

                <div>
                    <h1 class="text-2xl font-black text-brandNavy dark:text-white">Student Registry</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-1">Search, filter, and manage existing student accounts.</p>
                </div>

                {{-- Search + Filters --}}
                <form method="GET" action="{{ route('admin.students.index') }}" class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl p-6 flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or Student ID"
                            class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Program</label>
                        <select name="program" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                            <option value="">All Programs</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->code }}" {{ request('program') == $program->code ? 'selected' : '' }}>{{ $program->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[160px]">
                        <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Year Level</label>
                        <select name="year_level" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                            <option value="">All Years</option>
                            <option value="1st Year" {{ request('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                            <option value="2nd Year" {{ request('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3rd Year" {{ request('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4th Year" {{ request('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-2.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors">Filter</button>
                    @if(request('q') || request('program') || request('year_level'))
                        <a href="{{ route('admin.students.index') }}" class="px-6 py-2.5 text-brandNavy/50 dark:text-slate-400 text-sm font-bold">Clear</a>
                    @endif
                </form>

                {{-- Table --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-lightBg dark:bg-slate-900/40 border-b border-brandNavy/8 dark:border-slate-800">
                            <tr class="text-[10px] font-black text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">
                                <th class="text-left px-6 py-3">Student ID</th>
                                <th class="text-left px-6 py-3">Name</th>
                                <th class="text-left px-6 py-3">Program</th>
                                <th class="text-left px-6 py-3">Year Level</th>
                                <th class="text-left px-6 py-3">Education Level</th>
                                <th class="text-right px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-6 py-3 font-mono text-brandNavy dark:text-slate-200">{{ $student->login_id }}</td>
                                    <td class="px-6 py-3 font-semibold text-brandNavy dark:text-white">{{ $student->name }}</td>
                                    <td class="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{{ $student->major ?? '—' }}</td>
                                    <td class="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{{ $student->year_level ?? '—' }}</td>
                                    <td class="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{{ $student->program_level ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right">
                                        {{-- delete form added in Task 3 --}}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-brandNavy/40 dark:text-slate-500">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>{{ $students->links() }}</div>

            </div>
        </div>
    </main>
</div>

@include('partials.notif-script')
</body>
</html>
