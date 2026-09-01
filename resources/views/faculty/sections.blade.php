<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | My Sections</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="min-h-screen flex flex-col">

    <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <a href="{{ route('faculty.schedule') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                <i class="fa-solid fa-chevron-left mr-1"></i>Teaching Schedule
            </a>
            <span class="text-brandNavy/20 dark:text-slate-700">/</span>
            <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">My Sections</h2>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.notif-bell')
            <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
            </button>
            @include('partials.profile-menu', ['roleLabel' => 'Faculty'])
        </div>
    </header>

    <main class="flex-1 p-6 lg:p-10 max-w-4xl w-full mx-auto space-y-6">

        <div>
            <h1 class="text-xl font-black text-brandNavy dark:text-white">My Sections</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Select a section to enter or update final grades for enrolled students.</p>
        </div>

        <div class="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
            @if ($sections->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400 p-6">You are not assigned to any sections this term.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-lightBg dark:bg-slate-900/40 text-left text-[10px] uppercase tracking-wider text-brandNavy/50 dark:text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Block</th>
                            <th class="px-6 py-3">Enrolled</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        @foreach ($sections as $section)
                            <tr>
                                <td class="px-6 py-3">
                                    <span class="font-mono font-bold text-brandNavy dark:text-slate-200">{{ $section['subjectCode'] }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $section['subjectTitle'] }}</span>
                                </td>
                                <td class="px-6 py-3 text-brandNavy/80 dark:text-slate-300">{{ $section['blockLabel'] }}</td>
                                <td class="px-6 py-3 text-brandNavy/80 dark:text-slate-300">{{ $section['enrolledCount'] }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('faculty.sections.grades', $section['id']) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brandNavy hover:bg-brandGreen text-white text-xs font-bold transition-colors">
                                        <i class="fa-solid fa-pen-to-square"></i>Enter Grades
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

    </main>
</div>

@include('partials.notif-script')
</body>
</html>
