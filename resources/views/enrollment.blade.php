<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E'
            }}}
        }
    </script>
    <script>
        function updateThemeIcon() {
            var icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black text-brandNavy dark:text-white">AITSA</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('dashboard') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Dashboard</a>
            <a href="{{ route('clearance') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('clearance') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Clearance Routing</a>
            <a href="{{ route('enrollment') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('enrollment') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Enrollment</a>
            <a href="{{ route('ledger') }}" class="flex items-center px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('ledger') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">Ledger & Payments</a>
            <a href="/cor" class="flex items-center px-4 py-3 rounded-xl text-sm text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-500 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium transition-colors duration-300">Schedule</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">Enrollment</h2>
            </div>
            <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

            @php
                $isCleared = isset($clearance) && (
                    $clearance->cashier_status   === 'Approved' &&
                    $clearance->registrar_status === 'Approved' &&
                    $clearance->chair_status     === 'Approved'
                );
            @endphp

            {{-- Clearance lock banner --}}
            @if(!$isCleared)
            <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                        <p class="font-bold text-brandNavy dark:text-white text-sm">Enrollment Locked</p>
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">Complete all clearance steps before accessing enrollment.</p>
                    </div>
                </div>
                @if(isset($clearance) && $clearance->cashier_status !== 'Approved')
                    <a href="{{ route('ledger', ['triggerPay' => 'true']) }}" class="text-xs bg-brandGold hover:bg-yellow-500 text-brandNavy px-5 py-2.5 rounded-lg font-bold uppercase tracking-wide transition-all flex-shrink-0">
                        Settle Balance First
                    </a>
                @else
                    <a href="{{ route('clearance') }}" class="text-xs bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-5 py-2.5 rounded-lg font-bold uppercase tracking-wide transition-all flex-shrink-0">
                        Go to Clearance
                    </a>
                @endif
            </div>
            @endif

            {{-- Enrollment workspace (shown only when cleared) --}}
            <div class="@if(!$isCleared) hidden @endif">

                {{-- Header row: student info + classification badge --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">

                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-brandNavy/8 dark:border-slate-800">
                        <div>
                            <p class="text-xs text-brandNavy/50 dark:text-slate-400 uppercase tracking-wider font-bold">Student Enrollment</p>
                            <h2 class="text-lg font-black text-brandNavy dark:text-white mt-0.5">{{ Auth::user()->name }}</h2>
                            <p class="text-xs text-brandNavy/50 dark:text-slate-400">
                                {{ Auth::user()->major ?? 'BSIT' }} &nbsp;·&nbsp; {{ Auth::user()->year_level ?? '—' }} &nbsp;·&nbsp; A.Y. 2025–2026
                            </p>
                        </div>
                        <div id="classificationBadge" class="self-start sm:self-center px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider border"></div>
                    </div>

                    {{-- Year tabs (irregular only, hidden for regular) --}}
                    <div id="yearTabBar" class="hidden border-b border-brandNavy/8 dark:border-slate-800 px-2 flex gap-0 overflow-x-auto">
                        @foreach([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'] as $n => $label)
                        <button id="yearBtn{{ $n }}" onclick="setYear({{ $n }})"
                            class="px-5 py-3.5 text-xs font-bold whitespace-nowrap border-b-2 transition-colors duration-150">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>

                    {{-- Sem tabs --}}
                    <div id="semTabBar" class="hidden border-b border-brandNavy/8 dark:border-slate-800 px-2 flex gap-0">
                        <button id="semBtn1" onclick="setSem(1)" class="px-5 py-2.5 text-xs font-bold border-b-2 transition-colors duration-150">1st Semester</button>
                        <button id="semBtn2" onclick="setSem(2)" class="px-5 py-2.5 text-xs font-bold border-b-2 transition-colors duration-150">2nd Semester</button>
                    </div>

                    {{-- Regular workflow --}}
                    <div id="regularWorkflowSection" class="hidden">
                        <div class="px-6 py-3 bg-blue-500/5 border-b border-brandNavy/5 dark:border-slate-800 text-xs text-brandNavy/60 dark:text-slate-400">
                            Your subjects have been pre-assigned to your section block for this semester. Review below and confirm.
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-brandNavy/5 dark:border-slate-800">
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-24">Code</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider">Subject</th>
                                        <th class="text-center px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-16">Units</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider hidden md:table-cell">Schedule</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider hidden lg:table-cell w-24">Section</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider hidden lg:table-cell w-24">Room</th>
                                    </tr>
                                </thead>
                                <tbody id="regularTbody" class="divide-y divide-brandNavy/4 dark:divide-slate-800"></tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t border-brandNavy/5 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <span id="regularUnitCount" class="text-xs text-brandNavy/50 dark:text-slate-400"></span>
                            <button onclick="confirmRegular()"
                                class="sm:ml-auto inline-flex items-center gap-2 px-7 py-3 bg-brandGreen hover:bg-emerald-700 text-white text-xs font-black rounded-lg transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0">
                                <i class="fa-solid fa-circle-check"></i>Confirm Enrollment
                            </button>
                        </div>
                    </div>

                    {{-- Irregular workflow --}}
                    <div id="irregularWorkflowSection" class="hidden">
                        <div class="px-6 py-2.5 bg-amber-500/5 border-b border-brandNavy/5 dark:border-slate-800 flex items-center justify-between">
                            <p id="semLabel" class="text-xs text-brandNavy/50 dark:text-slate-400"></p>
                            <span id="selectionCount" class="text-[11px] font-bold text-brandNavy/40 dark:text-slate-500">0 subjects · 0 units</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-brandNavy/5 dark:border-slate-800">
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-24">Code</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider">Subject</th>
                                        <th class="text-center px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-16">Units</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider hidden md:table-cell">Schedule</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider hidden lg:table-cell w-24">Room</th>
                                        <th class="text-left px-5 py-2.5 text-[10px] font-bold text-brandNavy/40 dark:text-slate-600 uppercase tracking-wider w-24">Status</th>
                                        <th class="px-5 py-2.5 w-32"></th>
                                    </tr>
                                </thead>
                                <tbody id="subjectTbody" class="divide-y divide-brandNavy/4 dark:divide-slate-800"></tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t border-brandNavy/5 dark:border-slate-800 flex items-center justify-end">
                            <button id="submitIrregBtn" onclick="submitIrregular()"
                                class="inline-flex items-center gap-2 px-7 py-3 bg-brandGreen hover:bg-emerald-700 text-white text-xs font-black rounded-lg transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0">
                                <i class="fa-solid fa-paper-plane"></i>Submit Schedule
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<script>
// -- Data from server --------------------------------------------------------
const PASSED       = new Set({!! json_encode($passedCodes ?? []) !!});
const FAILED       = new Set({!! json_encode($failedCodes ?? []) !!});
const IS_IRREGULAR = {{ ($isIrregular ?? false) ? 'true' : 'false' }};
const STUDENT_YEAR = {{ $yearNum ?? 1 }};

// -- Curriculum --------------------------------------------------------------
const CATALOGUE = [
    // 1st Year
    { code: 'CC 101',   name: 'Introduction to Computing',        units: 3, year: 1, sem: 1, mode: 'F2F',    days: ['M','W','F'],        start: 8*60,     end: 9*60,     prereqs: [],          section: 'BSIT-1A', room: 'CCS-101'  },
    { code: 'CC 102',   name: 'Computer Programming 1',           units: 3, year: 1, sem: 1, mode: 'F2F',    days: ['T','Th'],            start: 8*60,     end: 9*60+30,  prereqs: [],          section: 'BSIT-1A', room: 'CCS-Lab1' },
    { code: 'GEC 1',    name: 'Understanding the Self',           units: 3, year: 1, sem: 1, mode: 'F2F',    days: ['M','W'],             start: 10*60,    end: 11*60+30, prereqs: [],          section: 'GEC-1A',  room: 'Rm 201'   },
    { code: 'GEC 2',    name: 'Readings in Philippine History',   units: 3, year: 1, sem: 1, mode: 'Online', days: ['Sat','Sun'],         start: 9*60,     end: 10*60+30, prereqs: [],          section: 'GEC-1B',  room: 'Online'   },
    { code: 'CC 103',   name: 'Computer Programming 2',           units: 3, year: 1, sem: 2, mode: 'F2F',    days: ['M','W','F'],         start: 8*60,     end: 9*60,     prereqs: ['CC 102'],  section: 'BSIT-1A', room: 'CCS-Lab1' },
    { code: 'CC 104',   name: 'Data Structures & Algorithms',     units: 3, year: 1, sem: 2, mode: 'F2F',    days: ['T','Th'],            start: 10*60,    end: 11*60+30, prereqs: ['CC 102'],  section: 'BSIT-1A', room: 'CCS-Lab2' },
    { code: 'GEC 3',    name: 'The Contemporary World',           units: 3, year: 1, sem: 2, mode: 'Online', days: ['Sat','Sun'],         start: 9*60,     end: 10*60+30, prereqs: [],          section: 'GEC-1C',  room: 'Online'   },
    { code: 'PATH 1',   name: 'Physical Activity 1 — Movement',   units: 2, year: 1, sem: 2, mode: 'F2F',    days: ['F'],                 start: 15*60,    end: 17*60,    prereqs: [],          section: 'PE-01',   room: 'Gym'      },
    // 2nd Year
    { code: 'CC 211',   name: 'Object-Oriented Programming',      units: 3, year: 2, sem: 1, mode: 'F2F',    days: ['M','W','F'],         start: 8*60,     end: 9*60,     prereqs: ['CC 104'],  section: 'BSIT-2A', room: 'CCS-Lab1' },
    { code: 'CC 212',   name: 'Discrete Mathematics',             units: 3, year: 2, sem: 1, mode: 'F2F',    days: ['T','Th'],            start: 8*60,     end: 9*60+30,  prereqs: [],          section: 'BSIT-2A', room: 'CCS-301'  },
    { code: 'GEC 5',    name: 'Purposive Communication',          units: 3, year: 2, sem: 1, mode: 'F2F',    days: ['M','W'],             start: 10*60,    end: 11*60+30, prereqs: [],          section: 'GEC-2A',  room: 'Rm 302'   },
    { code: 'GEC 6',    name: 'Art Appreciation',                 units: 3, year: 2, sem: 1, mode: 'Online', days: ['Sat','Sun'],         start: 9*60,     end: 10*60+30, prereqs: [],          section: 'GEC-2B',  room: 'Online'   },
    { code: 'CC 213',   name: 'Database Systems 1',               units: 3, year: 2, sem: 2, mode: 'F2F',    days: ['M','W','F'],         start: 8*60,     end: 9*60,     prereqs: [],          section: 'BSIT-2A', room: 'CCS-101'  },
    { code: 'CC 214',   name: 'Web Development Fundamentals',     units: 3, year: 2, sem: 2, mode: 'F2F',    days: ['T','Th'],            start: 8*60,     end: 9*60+30,  prereqs: ['CC 211'],  section: 'BSIT-2B', room: 'CCS-Lab1' },
    { code: 'CC 215',   name: 'Systems Analysis & Design',        units: 3, year: 2, sem: 2, mode: 'Hybrid', days: ['T','Th'],            start: 10*60+30, end: 12*60,    prereqs: ['CC 211'],  section: 'BSIT-2A', room: 'CCS-302'  },
    { code: 'CC 216',   name: 'Operating Systems',                units: 3, year: 2, sem: 2, mode: 'F2F',    days: ['M','W','F'],         start: 13*60,    end: 14*60,    prereqs: ['CC 212'],  section: 'BSIT-2A', room: 'CCS-Lab2' },
    // 3rd Year
    { code: 'CC 314',   name: 'Web Systems & Technologies',       units: 3, year: 3, sem: 1, mode: 'F2F',    days: ['T','Th'],            start: 8*60,     end: 9*60+30,  prereqs: ['CC 214'],  section: 'BSIT-3B', room: 'CCS-Lab1' },
    { code: 'CC 315',   name: 'Software Engineering',             units: 3, year: 3, sem: 1, mode: 'F2F',    days: ['T','Th'],            start: 10*60+30, end: 12*60,    prereqs: ['CC 215'],  section: 'BSIT-3A', room: 'CCS-301'  },
    { code: 'CC 311',   name: 'Database Systems 2',               units: 3, year: 3, sem: 1, mode: 'F2F',    days: ['M','W','F'],         start: 9*60,     end: 10*60,    prereqs: ['CC 213'],  section: 'BSIT-3A', room: 'CCS-201'  },
    { code: 'GEC 7',    name: 'Science, Technology & Society',    units: 3, year: 3, sem: 1, mode: 'Online', days: ['Sat','Sun'],         start: 15*60,    end: 16*60+30, prereqs: [],          section: 'GEC-01',  room: 'Online'   },
    { code: 'PATH FIT', name: 'Physical Activity — Team Sports',  units: 2, year: 3, sem: 1, mode: 'F2F',    days: ['F'],                 start: 15*60,    end: 17*60,    prereqs: [],          section: 'PE-01',   room: 'Gym'      },
    { code: 'CC 313',   name: 'Advanced Database Systems',        units: 3, year: 3, sem: 2, mode: 'F2F',    days: ['M','W','F'],         start: 8*60,     end: 9*60,     prereqs: ['CC 213'],  section: 'BSIT-3A', room: 'CCS-201'  },
    { code: 'CC 316',   name: 'Human-Computer Interaction',       units: 3, year: 3, sem: 2, mode: 'Hybrid', days: ['T','Th'],            start: 10*60,    end: 11*60+30, prereqs: ['CC 216'],  section: 'BSIT-3A', room: 'CCS-202'  },
    { code: 'CC 317',   name: 'Network Administration',           units: 3, year: 3, sem: 2, mode: 'F2F',    days: ['M','W','F'],         start: 13*60,    end: 14*60,    prereqs: [],          section: 'BSIT-3A', room: 'CCS-Lab2' },
    { code: 'CC 318',   name: 'Integrative Programming & Tech',   units: 3, year: 3, sem: 2, mode: 'Online', days: ['Sat','Sun'],         start: 13*60,    end: 14*60+30, prereqs: ['CC 212'],  section: 'BSIT-3A', room: 'Online'   },
    // 4th Year
    { code: 'CC 411',   name: 'Information Assurance & Security', units: 3, year: 4, sem: 1, mode: 'F2F',    days: ['M','W','F'],         start: 8*60,     end: 9*60,     prereqs: ['CC 317'],  section: 'BSIT-4A', room: 'CCS-401'  },
    { code: 'CC 412',   name: 'System Administration',            units: 3, year: 4, sem: 1, mode: 'F2F',    days: ['T','Th'],            start: 8*60,     end: 9*60+30,  prereqs: ['CC 317'],  section: 'BSIT-4A', room: 'CCS-Lab3' },
    { code: 'CC 413',   name: 'Advanced Web Development',         units: 3, year: 4, sem: 1, mode: 'F2F',    days: ['M','W','F'],         start: 10*60,    end: 11*60,    prereqs: ['CC 314'],  section: 'BSIT-4A', room: 'CCS-Lab1' },
    { code: 'CC CAP1',  name: 'Capstone Project 1',               units: 3, year: 4, sem: 1, mode: 'Online', days: ['Sat','Sun'],         start: 8*60,     end: 11*60,    prereqs: ['CC 315'],  section: 'BSIT-4A', room: 'Lab 203'  },
    { code: 'CC 414',   name: 'IT Project Management',            units: 3, year: 4, sem: 2, mode: 'F2F',    days: ['M','W','F'],         start: 8*60,     end: 9*60,     prereqs: ['CC 315'],  section: 'BSIT-4A', room: 'CCS-402'  },
    { code: 'CC 415',   name: 'Technopreneurship',                units: 3, year: 4, sem: 2, mode: 'Hybrid', days: ['T','Th'],            start: 10*60,    end: 11*60+30, prereqs: [],          section: 'BSIT-4A', room: 'CCS-403'  },
    { code: 'CC CAP2',  name: 'Capstone Project 2',               units: 3, year: 4, sem: 2, mode: 'Online', days: ['Sat','Sun'],         start: 8*60,     end: 11*60,    prereqs: ['CC CAP1'], section: 'BSIT-4A', room: 'Lab 203'  },
    { code: 'CC OJT',   name: 'On-the-Job Training (OJT)',        units: 6, year: 4, sem: 2, mode: 'F2F',    days: ['M','T','W','Th','F'],start: 8*60,     end: 17*60,    prereqs: ['CC 411'],  section: 'OJT-01',  room: 'Industry' },
];

let currentYear = STUDENT_YEAR;
let currentSem  = 1;
const selected  = new Set();

// -- Helpers -----------------------------------------------------------------
function fmtTime(mins) {
    const h = Math.floor(mins / 60), m = mins % 60, ap = h >= 12 ? 'PM' : 'AM';
    return (h > 12 ? h - 12 : h || 12) + (m ? ':' + String(m).padStart(2,'0') : '') + ap;
}

function prereqMet(subj) {
    return subj.prereqs.every(p => PASSED.has(p));
}

function conflictsWith(subj) {
    for (const code of selected) {
        const other = CATALOGUE.find(s => s.code === code);
        if (!other) continue;
        if (subj.days.some(d => other.days.includes(d)) && subj.start < other.end && subj.end > other.start) return other;
    }
    return null;
}

// -- Tab controls ------------------------------------------------------------
const TAB_ACTIVE   = 'border-brandNavy text-brandNavy dark:border-slate-200 dark:text-slate-100 font-bold';
const TAB_INACTIVE = 'border-transparent text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-slate-300 font-medium';
const TAB_DISABLED = 'border-transparent text-brandNavy/20 dark:text-slate-700 font-medium cursor-not-allowed';

function setYear(y) {
    if (y > STUDENT_YEAR) return;
    currentYear = y;
    [1,2,3,4].forEach(n => {
        const btn = document.getElementById('yearBtn' + n);
        if (!btn) return;
        if (n > STUDENT_YEAR) {
            btn.className = 'px-5 py-3.5 text-xs whitespace-nowrap border-b-2 transition-colors duration-150 ' + TAB_DISABLED;
            btn.disabled = true;
        } else if (n === y) {
            btn.className = 'px-5 py-3.5 text-xs whitespace-nowrap border-b-2 transition-colors duration-150 ' + TAB_ACTIVE;
        } else {
            btn.className = 'px-5 py-3.5 text-xs whitespace-nowrap border-b-2 transition-colors duration-150 ' + TAB_INACTIVE;
            btn.disabled = false;
        }
    });
    renderCatalogue();
}

function setSem(s) {
    currentSem = s;
    [1,2].forEach(n => {
        const btn = document.getElementById('semBtn' + n);
        if (!btn) return;
        btn.className = 'px-5 py-2.5 text-xs font-bold border-b-2 transition-colors duration-150 ' + (n === s ? TAB_ACTIVE : TAB_INACTIVE);
    });
    renderCatalogue();
}

// -- Irregular catalogue render ----------------------------------------------
function renderCatalogue() {
    const tbody    = document.getElementById('subjectTbody');
    const semLabel = document.getElementById('semLabel');
    const suffix   = ['','1st','2nd','3rd','4th'];

    if (semLabel) {
        semLabel.textContent = suffix[currentYear] + ' Year — ' + (currentSem === 1 ? '1st' : '2nd') + ' Semester — A.Y. 2025–2026';
    }

    const subjects = CATALOGUE.filter(s => s.year === currentYear && s.sem === currentSem);

    if (!subjects.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-12 text-center text-xs text-brandNavy/30 dark:text-slate-600">No subjects for this period.</td></tr>';
        updateCount();
        return;
    }

    tbody.innerHTML = subjects.map(subj => {
        const isLocked   = !prereqMet(subj);
        const isRetake   = FAILED.has(subj.code);
        const isEnrolled = selected.has(subj.code);
        const schedule   = subj.days.join('/') + ' · ' + fmtTime(subj.start) + '–' + fmtTime(subj.end);

        let statusHtml, actionHtml;

        if (isLocked) {
            statusHtml = `<span class="text-[11px] font-semibold text-red-500/70 flex items-center gap-1"><i class="fa-solid fa-lock text-[9px]"></i>Locked</span>`;
            actionHtml = `<span class="text-[10px] text-brandNavy/25 dark:text-slate-700 italic">${subj.prereqs.join(', ')}</span>`;
        } else if (isEnrolled) {
            statusHtml = `<span class="text-[11px] font-bold text-brandGreen flex items-center gap-1"><i class="fa-solid fa-check text-[9px]"></i>Enrolled</span>`;
            actionHtml = `<button onclick="toggleSubject('${subj.code}')" class="text-[11px] font-bold text-red-500 hover:text-red-700 px-3 py-1.5 rounded-md border border-red-200 dark:border-red-900/40 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">Remove</button>`;
        } else if (isRetake) {
            statusHtml = `<span class="text-[11px] font-bold text-amber-600 flex items-center gap-1"><i class="fa-solid fa-rotate-right text-[9px]"></i>Retake</span>`;
            actionHtml = `<button onclick="toggleSubject('${subj.code}')" class="text-[11px] font-bold text-brandGreen hover:text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-md border border-brandGreen/20 dark:border-brandGreen/30 hover:bg-brandGreen/5 dark:hover:bg-brandGreen/10 transition-colors">Enroll</button>`;
        } else {
            statusHtml = `<span class="text-[11px] text-brandNavy/30 dark:text-slate-600">Available</span>`;
            actionHtml = `<button onclick="toggleSubject('${subj.code}')" class="text-[11px] font-bold text-brandGreen hover:text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-md border border-brandGreen/20 dark:border-brandGreen/30 hover:bg-brandGreen/5 dark:hover:bg-brandGreen/10 transition-colors">Enroll</button>`;
        }

        const rowCls = isLocked
            ? 'opacity-50'
            : isEnrolled
                ? 'bg-brandGreen/[0.03] dark:bg-brandGreen/[0.07]'
                : '';

        return `
        <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors ${rowCls}">
            <td class="px-5 py-3 font-mono font-bold text-xs text-brandNavy dark:text-slate-200 whitespace-nowrap">${subj.code}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/80 dark:text-slate-300">${subj.name}</td>
            <td class="px-5 py-3 text-center text-xs text-brandNavy/50 dark:text-slate-400">${subj.units}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/50 dark:text-slate-400 whitespace-nowrap hidden md:table-cell">${schedule}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/40 dark:text-slate-500 whitespace-nowrap hidden lg:table-cell">${subj.room}</td>
            <td class="px-5 py-3">${statusHtml}</td>
            <td class="px-5 py-3 text-right">${actionHtml}</td>
        </tr>`;
    }).join('');

    updateCount();
}

function updateCount() {
    const enrolled = CATALOGUE.filter(s => selected.has(s.code));
    const units    = enrolled.reduce((sum, s) => sum + s.units, 0);
    const el = document.getElementById('selectionCount');
    if (el) el.textContent = selected.size + ' subject' + (selected.size !== 1 ? 's' : '') + ' · ' + units + ' units';
}

// -- Regular schedule render -------------------------------------------------
function renderRegularSchedule() {
    const subjects = CATALOGUE.filter(s => s.year === STUDENT_YEAR && s.sem === 1);
    const tbody    = document.getElementById('regularTbody');

    tbody.innerHTML = subjects.map(subj => `
        <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
            <td class="px-5 py-3 font-mono font-bold text-xs text-brandNavy dark:text-slate-200">${subj.code}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/80 dark:text-slate-300">${subj.name}</td>
            <td class="px-5 py-3 text-center text-xs text-brandNavy/50 dark:text-slate-400">${subj.units}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/50 dark:text-slate-400 hidden md:table-cell">${subj.days.join('/')} · ${fmtTime(subj.start)}–${fmtTime(subj.end)}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/40 dark:text-slate-500 hidden lg:table-cell">${subj.section}</td>
            <td class="px-5 py-3 text-xs text-brandNavy/40 dark:text-slate-500 hidden lg:table-cell">${subj.room}</td>
        </tr>`).join('');

    const totalUnits = subjects.reduce((sum, s) => sum + s.units, 0);
    const el = document.getElementById('regularUnitCount');
    if (el) el.textContent = subjects.length + ' subjects · ' + totalUnits + ' units · 1st Semester A.Y. 2025–2026';
}

// -- Actions -----------------------------------------------------------------
function toggleSubject(code) {
    const subj = CATALOGUE.find(s => s.code === code);
    if (!subj || !prereqMet(subj)) return;
    if (selected.has(code)) {
        selected.delete(code);
    } else {
        const conflict = conflictsWith(subj);
        if (conflict) {
            alert('Schedule conflict: "' + subj.name + '" overlaps with "' + conflict.name + '" (' + conflict.days.join('/') + ' ' + fmtTime(conflict.start) + '–' + fmtTime(conflict.end) + ').');
            return;
        }
        selected.add(code);
    }
    renderCatalogue();
}

function submitIrregular() {
    if (selected.size === 0) {
        alert('Please select at least one subject before submitting.');
        return;
    }
    const codes = CATALOGUE.filter(s => selected.has(s.code)).map(s => s.code).join(', ');
    alert('Enrollment submitted!\n\nSubjects: ' + codes);
}

function confirmRegular() {
    alert('Enrollment confirmed. Your fixed block schedule has been locked.');
}

// -- Boot --------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const badge    = document.getElementById('classificationBadge');
    const regular  = document.getElementById('regularWorkflowSection');
    const irregular = document.getElementById('irregularWorkflowSection');
    const yearBar  = document.getElementById('yearTabBar');
    const semBar   = document.getElementById('semTabBar');

    if (IS_IRREGULAR) {
        badge.textContent = 'Irregular';
        badge.className  += ' bg-amber-500/10 text-amber-600 border-amber-400/30';

        yearBar.classList.remove('hidden');
        semBar.classList.remove('hidden');
        irregular.classList.remove('hidden');

        // Disable year buttons above student's year
        [1,2,3,4].forEach(n => {
            const btn = document.getElementById('yearBtn' + n);
            if (!btn) return;
            if (n > STUDENT_YEAR) {
                btn.className = 'px-5 py-3.5 text-xs whitespace-nowrap border-b-2 transition-colors duration-150 ' + TAB_DISABLED;
                btn.disabled = true;
            }
        });

        setYear(STUDENT_YEAR);
        setSem(1);
    } else {
        badge.textContent = 'Regular';
        badge.className  += ' bg-blue-500/10 text-blue-600 border-blue-400/30';

        regular.classList.remove('hidden');
        renderRegularSchedule();
    }
});
</script>

@include('partials.notif-script')
</body>
</html>
