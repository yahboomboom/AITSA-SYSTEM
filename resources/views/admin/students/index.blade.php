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

    @include('partials.admin-sidebar')

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
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
                                        <button type="button"
                                            onclick="openDeleteStudentModal({{ Illuminate\Support\Js::from(route('admin.students.destroy', $student)) }}, {{ Illuminate\Support\Js::from($student->name) }})"
                                            class="text-red-500 hover:text-red-700 font-bold text-xs">
                                            <i class="fa-solid fa-trash mr-1"></i>Delete
                                        </button>
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

{{-- Permanent delete requires typing the student's name — no accidental single-click deletes. --}}
<div id="delete-student-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-panelDark rounded-2xl shadow-2xl max-w-sm w-full p-6">
        <div class="w-12 h-12 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center mb-4">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
        </div>
        <h3 class="text-base font-black text-brandNavy dark:text-white mb-1">Delete student account?</h3>
        <p class="text-xs text-brandNavy/60 dark:text-slate-400 mb-4">
            This permanently deletes <strong id="delete-student-target-name" class="text-brandNavy dark:text-white"></strong>'s
            account and cannot be undone. Type the student's name to confirm.
        </p>
        <input type="hidden" id="delete-student-expected-name">
        <input type="text" id="delete-student-confirm-input" oninput="checkDeleteStudentInput()"
            placeholder="Type the student's full name"
            class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-red-500 transition-colors mb-4">
        <div class="flex gap-3">
            <button type="button" onclick="closeDeleteStudentModal()"
                class="flex-1 py-2.5 rounded-xl text-sm font-bold text-brandNavy/70 dark:text-slate-300 bg-brandNavy/5 dark:bg-slate-800 hover:bg-brandNavy/10 transition-colors">
                Cancel
            </button>
            <button type="button" id="delete-student-confirm-btn" disabled onclick="submitDeleteStudent()"
                class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-red-500 hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                Delete
            </button>
        </div>
    </div>
</div>
<form id="delete-student-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
<script>
    function openDeleteStudentModal(url, name) {
        document.getElementById('delete-student-form').action = url;
        document.getElementById('delete-student-target-name').textContent = name;
        document.getElementById('delete-student-expected-name').value = name;
        document.getElementById('delete-student-confirm-input').value = '';
        document.getElementById('delete-student-confirm-btn').disabled = true;
        document.getElementById('delete-student-modal').classList.remove('hidden');
    }
    function closeDeleteStudentModal() {
        document.getElementById('delete-student-modal').classList.add('hidden');
    }
    function checkDeleteStudentInput() {
        var expected = document.getElementById('delete-student-expected-name').value.trim();
        var typed = document.getElementById('delete-student-confirm-input').value.trim();
        document.getElementById('delete-student-confirm-btn').disabled = (typed !== expected);
    }
    function submitDeleteStudent() {
        var btn = document.getElementById('delete-student-confirm-btn');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.textContent = 'Deleting…';
        document.getElementById('delete-student-form').submit();
    }
</script>

@include('partials.notif-script')
</body>
</html>
