<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Grades</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    @include('partials.student-sidebar')

    <main class="flex-1 flex flex-col overflow-hidden relative">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-100">Grades</h2>
            </div>
            <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.student-status-badge')
                @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-5">

            <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                <h1 class="font-heading text-lg font-semibold text-brandNavy dark:text-slate-100">My grades</h1>
                <p class="text-sm text-brandNavy/50 dark:text-slate-400 mt-1">Final grades recorded by your instructors for each subject.</p>
            </div>

            <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                @if ($grades->isEmpty())
                    <p class="text-sm text-brandNavy/50 dark:text-slate-400">No grades have been recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="ui-table">
                            <thead>
                                <tr class="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                                    <th class="border-brandNavy/8 dark:border-slate-800">Subject code</th>
                                    <th class="border-brandNavy/8 dark:border-slate-800">Final grade</th>
                                    <th class="border-brandNavy/8 dark:border-slate-800">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grades as $grade)
                                    <tr>
                                        <td class="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy dark:text-slate-200">{{ $grade->subject_code }}</td>
                                        <td class="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{{ $grade->final_grade }}</td>
                                        <td class="border-brandNavy/8 dark:border-slate-800">
                                            <span class="ui-badge-outline {{ $grade->status === 'Passed' ? 'border-brandGreen text-brandGreen' : 'border-red-500 text-red-600' }}">
                                                {{ $grade->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </main>
</div>

@include('partials.notif-script')
</body>
</html>
