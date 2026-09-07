<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Admission Slots</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.registrar-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center space-x-3">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Admission Slots</span>
                </div>
                <div class="flex items-center space-x-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel' => 'Registrar Portal',
                        'roleClass' => 'font-mono font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

                @if(session('success'))
                    <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Admission Slots</h1>
                    <p class="text-sm text-brandNavy/60 dark:text-slate-400">
                        Set how many total slots each curriculum has for <span class="font-bold">{{ $schoolYear }}</span>, split evenly across sections.
                        Slots are consumed as applicants get marked "Reserved" from the Applicants list.
                    </p>
                </div>

                @php
                    $context = [
                        'curricula' => $curricula->values(),
                        'errors' => array_map(fn ($m) => $m[0], $errors->getMessages()),
                        'old' => [
                            'school_year' => old('school_year', $schoolYear),
                            'semester' => old('semester'),
                        ],
                    ];
                @endphp

                <div
                    id="registrar-slots-root"
                    data-context="{{ json_encode($context) }}"
                    data-csrf-token="{{ csrf_token() }}"
                    data-start-term-url="{{ route('registrar.start-new-term') }}"
                >
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/registrar-slots-app.jsx')
</body>
</html>
