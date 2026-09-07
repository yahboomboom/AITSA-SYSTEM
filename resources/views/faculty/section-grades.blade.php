<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Grades — {{ $section->subject->code }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        @php
            $context = [
                'students' => $students->map(function ($student) use ($grades) {
                    $existing = $grades[$student->id] ?? null;
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'loginId' => $student->login_id,
                        'grade' => $existing->final_grade ?? null,
                        'status' => $existing->status ?? null,
                    ];
                })->values(),
            ];
        @endphp

        <div
            id="section-grades-root"
            data-context="{{ json_encode($context) }}"
            data-csrf-token="{{ csrf_token() }}"
            data-store-url="{{ route('faculty.sections.grades.store', $section->id) }}"
        >
            <p class="text-sm text-slate-500">Loading…</p>
        </div>

    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/section-grades-app.jsx')
</body>
</html>
