<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Grades — {{ $section->subject->code }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="min-h-screen flex flex-col">

    <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <a href="{{ route('faculty.sections') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                <i class="fa-solid fa-chevron-left mr-1"></i>My Sections
            </a>
            <span class="text-brandNavy/20 dark:text-slate-700">/</span>
            <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">{{ $section->subject->code }} — Block {{ $section->block_label }}</h2>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.notif-bell')
            <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
            </button>
            @include('partials.profile-menu', ['roleLabel' => 'Faculty'])
        </div>
    </header>

    <main class="flex-1 p-6 lg:p-10 max-w-3xl w-full mx-auto space-y-6">

        <div>
            <h1 class="text-xl font-black text-brandNavy dark:text-white">{{ $section->subject->title }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Enter each student's final grade (0–100). Leave blank if not yet available.
                Grades of <strong class="text-brandGreen">75 and above</strong> are marked <strong class="text-brandGreen">Passed</strong>; below 75 are marked <strong class="text-red-500">Failed</strong>.
            </p>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
            </div>
        @endif

        <form action="{{ route('faculty.sections.grades.store', $section->id) }}" method="POST" class="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
            @csrf

            @if ($students->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400 p-6">No students are enrolled in this section yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-lightBg dark:bg-slate-900/40 text-left text-[10px] uppercase tracking-wider text-brandNavy/50 dark:text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Student</th>
                            <th class="px-6 py-3">Student No.</th>
                            <th class="px-6 py-3 w-32">Grade</th>
                            <th class="px-6 py-3 w-24">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        @foreach ($students as $student)
                            @php $existing = $grades[$student->id] ?? null; @endphp
                            <tr>
                                <td class="px-6 py-3 font-semibold text-brandNavy dark:text-slate-200">{{ $student->name }}</td>
                                <td class="px-6 py-3 font-mono text-xs text-brandNavy/60 dark:text-slate-400">{{ $student->login_id }}</td>
                                <td class="px-6 py-3">
                                    <input type="number" name="grades[{{ $student->id }}]"
                                           value="{{ $existing->final_grade ?? '' }}"
                                           min="0" max="100" step="1" placeholder="—"
                                           class="w-20 text-center font-mono text-sm rounded py-1.5 px-2 border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 text-brandNavy dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brandGreen/30">
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-xs font-bold {{ ($existing->status ?? null) === 'Passed' ? 'text-brandGreen' : (($existing->status ?? null) === 'Failed' ? 'text-red-500' : 'text-brandNavy/30 dark:text-slate-600') }}">
                                        {{ $existing->status ?? 'Not Taken' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-brandNavy/10 dark:border-slate-800 flex justify-end">
                    <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-black rounded transition-colors">
                        <i class="fa-solid fa-floppy-disk"></i>Save Grades
                    </button>
                </div>
            @endif
        </form>

    </main>
</div>

@include('partials.notif-script')
</body>
</html>
