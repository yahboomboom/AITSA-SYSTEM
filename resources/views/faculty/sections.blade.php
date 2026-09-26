<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | My Sections</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="min-h-screen flex flex-col">

    <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <a href="{{ route('faculty.schedule') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                <i class="fa-solid fa-chevron-left mr-1"></i>Teaching schedule
            </a>
            <span class="text-brandNavy/20 dark:text-slate-700">/</span>
            <h2 class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-100">My sections</h2>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.notif-bell')
            <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
            </button>
            @include('partials.profile-menu', ['roleLabel' => 'Faculty'])
        </div>
    </header>

    <main class="flex-1 p-6 lg:p-10 max-w-4xl w-full mx-auto space-y-6">

        <div>
            <h1 class="font-heading text-lg font-semibold text-brandNavy dark:text-white">My sections</h1>
            <p class="text-sm text-brandNavy/50 dark:text-slate-400 mt-1">Select a section to enter or update final grades for enrolled students.</p>
        </div>

        @php
            $context = $sections->map(fn ($section) => [
                'id' => $section['id'],
                'subjectCode' => $section['subjectCode'],
                'subjectTitle' => $section['subjectTitle'],
                'blockLabel' => $section['blockLabel'],
                'enrolledCount' => $section['enrolledCount'],
                'gradesUrl' => route('faculty.sections.grades', $section['id']),
            ])->values();
        @endphp

        <div id="faculty-sections-root" data-context="{{ json_encode($context) }}">
            <p class="text-sm text-slate-500">Loading…</p>
        </div>

    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/faculty-sections-app.jsx')
</body>
</html>
