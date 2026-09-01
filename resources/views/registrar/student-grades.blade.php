<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Grade Editor — {{ $student->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased">

@php
$curriculum = [
    ['code' => 'CC 101',  'name' => 'Introduction to Computing',        'units' => 3, 'year' => 1, 'sem' => 1],
    ['code' => 'CC 102',  'name' => 'Computer Programming 1',           'units' => 3, 'year' => 1, 'sem' => 1],
    ['code' => 'GEC 1',   'name' => 'Understanding the Self',           'units' => 3, 'year' => 1, 'sem' => 1],
    ['code' => 'GEC 2',   'name' => 'Readings in Philippine History',   'units' => 3, 'year' => 1, 'sem' => 1],
    ['code' => 'CC 103',  'name' => 'Computer Programming 2',           'units' => 3, 'year' => 1, 'sem' => 2],
    ['code' => 'CC 104',  'name' => 'Data Structures & Algorithms',     'units' => 3, 'year' => 1, 'sem' => 2],
    ['code' => 'GEC 3',   'name' => 'The Contemporary World',           'units' => 3, 'year' => 1, 'sem' => 2],
    ['code' => 'PATH 1',  'name' => 'Physical Activity 1 — Movement',   'units' => 2, 'year' => 1, 'sem' => 2],
    ['code' => 'CC 211',  'name' => 'Object-Oriented Programming',      'units' => 3, 'year' => 2, 'sem' => 1],
    ['code' => 'CC 212',  'name' => 'Discrete Mathematics',             'units' => 3, 'year' => 2, 'sem' => 1],
    ['code' => 'GEC 5',   'name' => 'Purposive Communication',          'units' => 3, 'year' => 2, 'sem' => 1],
    ['code' => 'GEC 6',   'name' => 'Art Appreciation',                 'units' => 3, 'year' => 2, 'sem' => 1],
    ['code' => 'CC 213',  'name' => 'Database Systems 1',               'units' => 3, 'year' => 2, 'sem' => 2],
    ['code' => 'CC 214',  'name' => 'Web Development Fundamentals',     'units' => 3, 'year' => 2, 'sem' => 2],
    ['code' => 'CC 215',  'name' => 'Systems Analysis & Design',        'units' => 3, 'year' => 2, 'sem' => 2],
    ['code' => 'CC 216',  'name' => 'Operating Systems',                'units' => 3, 'year' => 2, 'sem' => 2],
    ['code' => 'CC 314',  'name' => 'Web Systems & Technologies',       'units' => 3, 'year' => 3, 'sem' => 1],
    ['code' => 'CC 315',  'name' => 'Software Engineering',             'units' => 3, 'year' => 3, 'sem' => 1],
    ['code' => 'CC 311',  'name' => 'Database Systems 2',               'units' => 3, 'year' => 3, 'sem' => 1],
    ['code' => 'GEC 7',   'name' => 'Science, Technology & Society',    'units' => 3, 'year' => 3, 'sem' => 1],
    ['code' => 'PATH FIT','name' => 'Physical Activity — Team Sports',  'units' => 2, 'year' => 3, 'sem' => 1],
    ['code' => 'CC 313',  'name' => 'Advanced Database Systems',        'units' => 3, 'year' => 3, 'sem' => 2],
    ['code' => 'CC 316',  'name' => 'Human-Computer Interaction',       'units' => 3, 'year' => 3, 'sem' => 2],
    ['code' => 'CC 317',  'name' => 'Network Administration',           'units' => 3, 'year' => 3, 'sem' => 2],
    ['code' => 'CC 318',  'name' => 'Integrative Programming & Tech',   'units' => 3, 'year' => 3, 'sem' => 2],
    ['code' => 'CC 411',  'name' => 'Information Assurance & Security', 'units' => 3, 'year' => 4, 'sem' => 1],
    ['code' => 'CC 412',  'name' => 'System Administration',            'units' => 3, 'year' => 4, 'sem' => 1],
    ['code' => 'CC 413',  'name' => 'Advanced Web Development',         'units' => 3, 'year' => 4, 'sem' => 1],
    ['code' => 'CC CAP1', 'name' => 'Capstone Project 1',               'units' => 3, 'year' => 4, 'sem' => 1],
    ['code' => 'CC 414',  'name' => 'IT Project Management',            'units' => 3, 'year' => 4, 'sem' => 2],
    ['code' => 'CC 415',  'name' => 'Technopreneurship',                'units' => 3, 'year' => 4, 'sem' => 2],
    ['code' => 'CC CAP2', 'name' => 'Capstone Project 2',               'units' => 3, 'year' => 4, 'sem' => 2],
    ['code' => 'CC OJT',  'name' => 'On-the-Job Training (OJT)',        'units' => 6, 'year' => 4, 'sem' => 2],
];

$grouped    = [];
foreach ($curriculum as $s) { $grouped[$s['year']][$s['sem']][] = $s; }
$yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
$semLabels  = [1 => '1st Semester', 2 => '2nd Semester'];

$failCount = $grades->where('status', 'Failed')->count();
$passCount = $grades->where('status', 'Passed')->count();
@endphp

<div class="flex h-screen overflow-hidden">

    @include('partials.registrar-sidebar')

    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <a href="{{ route('registrar.students') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                    <i class="fa-solid fa-chevron-left mr-1"></i>Student Records
                </a>
                <span class="text-brandNavy/20 dark:text-slate-700">/</span>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100 truncate max-w-xs">{{ $student->name }}</h2>
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

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-black text-brandNavy dark:text-white">{{ $student->name }}</h1>
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">
                            {{ $student->login_id }} &nbsp;·&nbsp; {{ $student->year_level ?? '—' }} &nbsp;·&nbsp; {{ $student->major ?? '—' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="px-3 py-1.5 rounded bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold">{{ $passCount }} Passed</span>
                        <span class="px-3 py-1.5 rounded bg-red-500/10 text-red-600 font-bold">{{ $failCount }} Failed</span>
                        @if($failCount > 0)
                            <span class="px-3 py-1.5 rounded bg-amber-500/10 text-amber-600 border border-amber-400/20 font-bold">Irregular</span>
                        @else
                            <span class="px-3 py-1.5 rounded bg-blue-500/10 text-blue-600 font-bold">Regular</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-lg bg-lightBg dark:bg-slate-900/40 border border-brandNavy/8 dark:border-slate-800 text-xs text-brandNavy/60 dark:text-slate-400">
                    <i class="fa-solid fa-circle-info text-brandNavy/30 dark:text-slate-600 mt-0.5 flex-shrink-0"></i>
                    <span>Enter the student's final grade (0–100). Leave blank if the subject has not been taken. Grades of <strong class="text-brandGreen dark:text-emerald-400">75 and above</strong> are automatically marked <strong class="text-brandGreen dark:text-emerald-400">Passed</strong>; below 75 are marked <strong class="text-red-500">Failed</strong>.</span>
                </div>

                @if(session('success'))
                <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
                </div>
                @endif

                <form action="{{ route('registrar.students.grades.store', $student->id) }}" method="POST">
                    @csrf

                    <div class="space-y-6">
                        @foreach($grouped as $year => $sems)
                        <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden">

                            <div class="px-5 py-3 bg-brandNavy dark:bg-slate-900 flex items-center justify-between">
                                <span class="text-sm font-black text-white tracking-wide">{{ $yearLabels[$year] }}</span>
                                <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">BSIT Curriculum</span>
                            </div>

                            @foreach($sems as $sem => $subjects)
                            <div class="@if(!$loop->last) border-b border-brandNavy/8 dark:border-slate-800 @endif">

                                <div class="px-5 py-2.5 bg-lightBg dark:bg-slate-900/50 border-b border-brandNavy/8 dark:border-slate-800">
                                    <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-widest">{{ $semLabels[$sem] }}</span>
                                </div>

                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-brandNavy/5 dark:border-slate-800">
                                            <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-24">Code</th>
                                            <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider">Subject</th>
                                            <th class="text-center px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-14">Units</th>
                                            <th class="text-center px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-36">
                                                Grade <span class="font-normal normal-case text-brandNavy/25 dark:text-slate-700">(0–100)</span>
                                            </th>
                                            <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-24">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-brandNavy/4 dark:divide-slate-800/80">
                                        @foreach($subjects as $subj)
                                        @php
                                            $gradeRecord   = $grades[$subj['code']] ?? null;
                                            $storedGrade   = $gradeRecord ? (int) $gradeRecord->final_grade : null;
                                            $storedStatus  = $gradeRecord->status ?? 'not_taken';
                                        @endphp
                                        <tr class="grade-row transition-colors
                                            @if($storedStatus === 'Passed') bg-brandGreen/[0.03] dark:bg-brandGreen/[0.06]
                                            @elseif($storedStatus === 'Failed') bg-red-50 dark:bg-red-900/[0.08]
                                            @else hover:bg-lightBg dark:hover:bg-slate-800/20
                                            @endif"
                                            data-initial="{{ $storedStatus }}">
                                            <td class="px-5 py-3">
                                                <span class="font-mono text-xs font-bold text-brandNavy dark:text-slate-300">{{ $subj['code'] }}</span>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="text-xs text-brandNavy/80 dark:text-slate-300">{{ $subj['name'] }}</span>
                                            </td>
                                            <td class="px-5 py-3 text-center">
                                                <span class="text-xs text-brandNavy/50 dark:text-slate-500">{{ $subj['units'] }}</span>
                                            </td>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center justify-center gap-2">
                                                    <input
                                                        type="number"
                                                        name="grades[{{ $subj['code'] }}]"
                                                        value="{{ $storedGrade ?? '' }}"
                                                        min="0" max="100" step="1"
                                                        placeholder="—"
                                                        class="grade-input w-20 text-center font-mono text-sm rounded py-1.5 px-2 border transition-colors focus:outline-none focus:ring-2 focus:ring-brandGreen/30
                                                            @if($storedStatus === 'Passed') border-brandGreen/40 bg-brandGreen/5 dark:bg-brandGreen/10 dark:border-brandGreen/30
                                                            @elseif($storedStatus === 'Failed') border-red-400/40 bg-red-50 dark:bg-red-900/10 dark:border-red-800/40
                                                            @else border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60
                                                            @endif
                                                            text-brandNavy dark:text-slate-200">
                                                    <span class="grade-label text-[10px] font-bold w-10 text-left
                                                        @if($storedStatus === 'Passed') text-brandGreen
                                                        @elseif($storedStatus === 'Failed') text-red-500
                                                        @else text-brandNavy/25 dark:text-slate-600
                                                        @endif">
                                                        @if($storedStatus === 'Passed') Pass
                                                        @elseif($storedStatus === 'Failed') Fail
                                                        @else —
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="grade-status text-xs font-semibold
                                                    @if($storedStatus === 'Passed') text-brandGreen
                                                    @elseif($storedStatus === 'Failed') text-red-500
                                                    @else text-brandNavy/25 dark:text-slate-600
                                                    @endif">
                                                    @if($storedStatus === 'Passed') Passed
                                                    @elseif($storedStatus === 'Failed') Failed
                                                    @else Not Taken
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-4 pb-6">
                        <p class="text-[11px] text-brandNavy/40 dark:text-slate-500">
                            Changes here immediately affect the student's enrollment page.
                        </p>
                        <button type="submit"
                            class="flex-shrink-0 flex items-center gap-2 px-8 py-3 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-black rounded transition-colors">
                            <i class="fa-solid fa-floppy-disk"></i>Save Grades
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.grade-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const val   = this.value === '' ? null : parseInt(this.value, 10);
            const label  = this.nextElementSibling;
            const row    = this.closest('tr');
            const status = row.querySelector('.grade-status');

            if (val === null || isNaN(val) || val === 0) {
                label.textContent  = '—';
                label.style.color  = '';
                status.textContent = 'Not Taken';
                status.style.color = '';
                applyRowStyle(this, row, 'none');
            } else if (val >= 75) {
                label.textContent  = 'Pass';
                label.style.color  = '#1D7A46';
                status.textContent = 'Passed';
                status.style.color = '#1D7A46';
                applyRowStyle(this, row, 'pass');
            } else {
                label.textContent  = 'Fail';
                label.style.color  = '#ef4444';
                status.textContent = 'Failed';
                status.style.color = '#ef4444';
                applyRowStyle(this, row, 'fail');
            }
        });
    });
});

function applyRowStyle(input, row, type) {
    const isDark = document.documentElement.classList.contains('dark');
    if (type === 'pass') {
        input.style.borderColor     = 'rgba(29,122,70,0.4)';
        input.style.backgroundColor = isDark ? 'rgba(29,122,70,0.1)' : 'rgba(29,122,70,0.05)';
        row.style.backgroundColor   = isDark ? 'rgba(29,122,70,0.06)' : 'rgba(29,122,70,0.03)';
    } else if (type === 'fail') {
        input.style.borderColor     = 'rgba(239,68,68,0.4)';
        input.style.backgroundColor = isDark ? 'rgba(239,68,68,0.1)' : 'rgba(239,68,68,0.05)';
        row.style.backgroundColor   = isDark ? 'rgba(239,68,68,0.08)' : 'rgba(239,68,68,0.04)';
    } else {
        input.style.borderColor     = '';
        input.style.backgroundColor = '';
        row.style.backgroundColor   = '';
    }
}
</script>

@include('partials.notif-script')
</body>
</html>
