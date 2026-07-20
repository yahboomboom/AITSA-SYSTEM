<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Teaching Schedule</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="min-h-screen flex flex-col">

    <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover">
            <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">My Teaching Schedule</h2>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.notif-bell')
            <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
            </button>
            @include('partials.profile-menu', ['roleLabel' => 'Faculty'])
        </div>
    </header>

    <main class="flex-1 p-6 lg:p-10 max-w-5xl w-full mx-auto space-y-6">
        <div>
            <h1 class="text-3xl font-black text-brandNavy dark:text-white">Weekly Teaching Schedule</h1>
            <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                {{ Auth::user()->name }} — A.Y. {{ \App\Models\Setting::get('school_year', '2026-2027') }}
            </p>
        </div>

        @php
            $dayNames = ['M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
            $byDay = [];
            foreach ($sections as $section) {
                foreach ($section->days as $day) {
                    $byDay[$day][] = $section;
                }
            }
        @endphp

        @if ($sections->isEmpty())
            <div class="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-10 text-center">
                <i class="fa-solid fa-chalkboard-user text-3xl text-brandNavy/20 dark:text-slate-600 mb-3"></i>
                <p class="text-sm text-brandNavy/60 dark:text-slate-400">No teaching load assigned yet for this school year.</p>
            </div>
        @else
            @foreach ($dayNames as $key => $label)
                @if (!empty($byDay[$key]))
                    <div class="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                            <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                                <i class="fa-solid fa-calendar-day mr-2 text-brandGreen"></i>{{ $label }}
                            </span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($byDay[$key] as $section)
                                <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <div>
                                        <span class="font-bold font-mono">{{ $section->subject->code }}</span>
                                        <span class="text-brandNavy/70 dark:text-slate-400">— {{ $section->subject->title }}</span>
                                        <span class="ml-2 text-xs text-brandNavy/50 dark:text-slate-500">Block {{ $section->block_label }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="font-semibold">{{ $section->start_time }}–{{ $section->end_time }}</span>
                                        <span class="text-brandNavy/60 dark:text-slate-400">{{ $section->roomLabel() }}</span>
                                        @if ($section->roomEntity && ! $section->roomEntity->isPhysical())
                                            <span class="px-1.5 py-0.5 rounded bg-brandGold/15 text-brandGold font-bold text-[10px] uppercase">Online</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </main>
</div>

@include('partials.notif-script')
</body>
</html>
