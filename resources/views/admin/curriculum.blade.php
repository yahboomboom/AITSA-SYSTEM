<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA HQ | Curriculum Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
                    }
                }
            }
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
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">AITSA HQ</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Core Control</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.dashboard') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>System Overview</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50 rounded-xl font-medium text-sm transition-colors"><span>Manage Users</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50 rounded-xl font-medium text-sm transition-colors"><span>Clearance Settings</span>
            </a>
            <a href="{{ route('admin.curriculum') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.curriculum') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Curriculum</span>
            </a>
            <a href="{{ route('admin.audit') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.audit') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Audit Trail</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.reports') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Reports</span>
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden relative">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-8 z-10 flex-shrink-0 transition-colors duration-300">
            <div>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-200 tracking-wide">Curriculum Management</h2>
                <p class="text-[10px] text-brandNavy/50 dark:text-slate-500">Manage subject offerings per program and semester — A.Y. 2025–2026</p>
            </div>
            <div class="flex items-center gap-3">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', [
                    'roleLabel'     => 'Root Access Mode',
                    'roleClass'     => 'text-red-500 uppercase tracking-wider',
                    'avatarClass'   => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                    'avatarInitial' => 'A',
                ])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            {{-- Page title --}}
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Subject Catalogue</h1>
                <p class="text-xs text-brandNavy/60 dark:text-slate-400 mt-1">Select a program to view and manage its subject offerings. Toggle a subject's status to control student enrollment visibility.</p>
            </div>

            {{-- Category tabs --}}
            <div class="flex gap-2" id="catTabs">
                <button onclick="switchCat('tesda')"   data-cat="tesda"     class="cat-tab px-5 py-2 text-xs font-bold rounded-xl transition-all bg-amber-500 text-white shadow-sm">TESDA</button>
                <button onclick="switchCat('associate')" data-cat="associate" class="cat-tab px-5 py-2 text-xs font-bold rounded-xl transition-all bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 text-brandNavy/60 dark:text-slate-400">Associate</button>
                <button onclick="switchCat('bachelor')"  data-cat="bachelor"  class="cat-tab px-5 py-2 text-xs font-bold rounded-xl transition-all bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 text-brandNavy/60 dark:text-slate-400">Bachelor</button>
            </div>

            {{-- Program cards --}}
            <div id="programCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

            {{-- Subject panel (hidden until program selected) --}}
            <div id="subjectPanel" class="hidden space-y-4">

                {{-- Program header bar --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-brandNavy/10 dark:border-slate-800">
                    <div>
                        <p class="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest" id="progCatLabel"></p>
                        <h3 id="progNameLabel" class="text-base font-extrabold text-brandNavy dark:text-white mt-0.5"></h3>
                    </div>
                    <button onclick="openAddModal()"
                        class="flex-shrink-0 flex items-center gap-2 text-xs font-bold bg-brandGreen hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl transition-all shadow-md hover:-translate-y-0.5 active:translate-y-0">
                        <i class="fa-solid fa-plus"></i>Add Subject
                    </button>
                </div>

                {{-- Semester tabs --}}
                <div class="flex gap-2">
                    <button id="sem1Tab" onclick="switchSem(1)" class="px-5 py-2 text-xs font-bold rounded-xl transition-all bg-brandNavy text-white shadow-sm">1st Semester</button>
                    <button id="sem2Tab" onclick="switchSem(2)" class="px-5 py-2 text-xs font-bold rounded-xl transition-all bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 text-brandNavy/60 dark:text-slate-400">2nd Semester</button>
                </div>

                {{-- Table --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h4 id="semTitle" class="text-sm font-bold text-brandNavy dark:text-white">1st Semester Offerings</h4>
                        <div class="relative w-full sm:w-64">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 text-xs"></i>
                            <input id="searchInput" oninput="filterTable()" type="text" placeholder="Search subject code or name…"
                                class="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 pl-9 pr-4 py-2 rounded-xl focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-3.5 px-5">Code</th>
                                    <th class="py-3.5 px-5">Subject Name</th>
                                    <th class="py-3.5 px-5 text-center">Units</th>
                                    <th class="py-3.5 px-5 text-center">Mode</th>
                                    <th class="py-3.5 px-5">Prerequisites</th>
                                    <th class="py-3.5 px-5 text-center">Status</th>
                                    <th class="py-3.5 px-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="subjectTableBody" class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs"></tbody>
                        </table>
                    </div>

                    <div id="emptyState" class="hidden py-14 text-center text-brandNavy/40 dark:text-slate-500">
                        <i class="fa-solid fa-book-open text-3xl mb-3 opacity-30"></i>
                        <p class="font-semibold">No subjects found for this semester.</p>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

{{-- Add/Edit Modal --}}
<div id="subjectModal" class="hidden fixed inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-panelDark rounded-2xl shadow-2xl w-full max-w-lg border border-brandNavy/10 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-700 flex items-center justify-between bg-lightBg dark:bg-slate-800/60">
            <h3 id="modalTitle" class="text-sm font-bold text-brandNavy dark:text-white">Add Subject</h3>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full hover:bg-brandNavy/10 flex items-center justify-center text-brandNavy/50 dark:text-slate-400 transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Subject Code</label>
                    <input id="mCode" type="text" placeholder="e.g. OA 419" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-3 py-2.5 text-xs text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Units</label>
                    <input id="mUnits" type="number" min="1" max="6" placeholder="3" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-3 py-2.5 text-xs text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Subject Name</label>
                <input id="mName" type="text" placeholder="e.g. Business Communication" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-3 py-2.5 text-xs text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Semester</label>
                    <select id="mSem" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-3 py-2.5 text-xs text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Delivery Mode</label>
                    <select id="mMode" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-3 py-2.5 text-xs text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                        <option value="F2F">Face-to-Face</option>
                        <option value="Online">Online</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Prerequisites <span class="font-normal normal-case">(comma-separated codes, leave blank if none)</span></label>
                <input id="mPrereqs" type="text" placeholder="e.g. OA 101, OA 102" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-3 py-2.5 text-xs text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
            </div>
            <div class="flex items-center gap-3 pt-1">
                <input id="mActive" type="checkbox" checked class="w-4 h-4 rounded accent-brandGreen cursor-pointer">
                <label for="mActive" class="text-xs font-semibold text-brandNavy dark:text-slate-300 cursor-pointer">Active — visible to students for this semester's enrollment</label>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-brandNavy/10 dark:border-slate-700 flex justify-end gap-3">
            <button onclick="closeModal()" class="px-5 py-2.5 text-xs font-bold text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 rounded-xl transition-colors">Cancel</button>
            <button onclick="saveSubject()" class="px-5 py-2.5 text-xs font-bold text-white bg-brandGreen hover:bg-emerald-600 rounded-xl transition-all shadow-sm">Save Subject</button>
        </div>
    </div>
</div>

<script>
// ── Program catalogue ─────────────────────────────────────────────────────────
const CATEGORIES = {
    tesda: {
        label: 'TESDA',
        icon:  'fa-certificate',
        color: { bg: 'bg-amber-500/10 dark:bg-amber-500/10', text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-500/20', dot: 'bg-amber-500' },
        programs: [
            { id: 'bk3',  name: 'Bookkeeping NC III',        abbr: 'BK-NC3',  icon: 'fa-book-bookmark' },
            { id: 'em3',  name: 'Events Management NC III',   abbr: 'EM-NC3',  icon: 'fa-calendar-star' },
            { id: 'fb3',  name: 'Food & Beverages NC III',    abbr: 'FB-NC3',  icon: 'fa-utensils'      },
        ]
    },
    associate: {
        label: 'Associate',
        icon:  'fa-graduation-cap',
        color: { bg: 'bg-blue-500/10 dark:bg-blue-500/10', text: 'text-blue-600 dark:text-blue-400', border: 'border-blue-500/20', dot: 'bg-blue-500' },
        programs: [
            { id: 'bom',  name: 'Business Office Management', abbr: 'BoM',  icon: 'fa-briefcase'      },
            { id: 'fsm',  name: 'Food Service Management',    abbr: 'FSM',  icon: 'fa-bowl-food'      },
        ]
    },
    bachelor: {
        label: 'Bachelor',
        icon:  'fa-user-graduate',
        color: { bg: 'bg-brandNavy/8 dark:bg-brandNavy/20', text: 'text-brandNavy dark:text-slate-300', border: 'border-brandNavy/15', dot: 'bg-brandNavy' },
        programs: [
            { id: 'bsoa',   name: 'Bachelor in Science Office Administration',             abbr: 'BSOA',   icon: 'fa-landmark-flag'  },
            { id: 'btvted', name: 'Bachelor in Technical Vocational Teacher Education',    abbr: 'BTVTED', icon: 'fa-chalkboard-user' },
        ]
    },
};

// ── Subject data per program ──────────────────────────────────────────────────
const SUBJECTS = {
    bk3: [
        { code: 'BK 101', name: 'Basic Accounting Concepts',             units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'BK 102', name: 'Journalizing Business Transactions',    units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'BK 103', name: 'Posting to the General Ledger',         units: 3, sem: 1, mode: 'F2F',    prereqs: 'BK 102',   active: true  },
        { code: 'BK 104', name: 'Preparing the Trial Balance',           units: 3, sem: 1, mode: 'Hybrid', prereqs: 'BK 103',   active: true  },
        { code: 'BK 201', name: 'Financial Statements Preparation',      units: 3, sem: 2, mode: 'F2F',    prereqs: 'BK 104',   active: true  },
        { code: 'BK 202', name: 'Payroll Processing',                    units: 3, sem: 2, mode: 'F2F',    prereqs: 'BK 101',   active: true  },
        { code: 'BK 203', name: 'Tax Compliance Basics',                 units: 3, sem: 2, mode: 'Online', prereqs: 'BK 201',   active: false },
    ],
    em3: [
        { code: 'EM 101', name: 'Fundamentals of Events Management',     units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'EM 102', name: 'Events Planning and Coordination',      units: 3, sem: 1, mode: 'F2F',    prereqs: 'EM 101',   active: true  },
        { code: 'EM 103', name: 'Venue Selection and Setup',             units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'EM 104', name: 'Audiovisual and Technical Support',     units: 3, sem: 1, mode: 'Hybrid', prereqs: '',         active: true  },
        { code: 'EM 201', name: 'Food and Beverage for Events',          units: 3, sem: 2, mode: 'F2F',    prereqs: 'EM 102',   active: true  },
        { code: 'EM 202', name: 'Events Marketing and Promotion',        units: 3, sem: 2, mode: 'Online', prereqs: 'EM 101',   active: true  },
        { code: 'EM 203', name: 'Capstone Events Project',               units: 3, sem: 2, mode: 'F2F',    prereqs: 'EM 201',   active: false },
    ],
    fb3: [
        { code: 'FB 101', name: 'Food Safety and Sanitation',            units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'FB 102', name: 'Basic Food Preparation Techniques',     units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'FB 103', name: 'Beverage Service Fundamentals',         units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'FB 104', name: 'Barista and Café Operations',           units: 3, sem: 1, mode: 'F2F',    prereqs: 'FB 103',   active: true  },
        { code: 'FB 201', name: 'Restaurant Operations and Service',     units: 3, sem: 2, mode: 'F2F',    prereqs: 'FB 102',   active: true  },
        { code: 'FB 202', name: 'Menu Planning and Costing',             units: 3, sem: 2, mode: 'Hybrid', prereqs: 'FB 101',   active: true  },
        { code: 'FB 203', name: 'Catering and Banquet Service',          units: 3, sem: 2, mode: 'F2F',    prereqs: 'FB 201',   active: false },
    ],
    bom: [
        { code: 'BOM 101', name: 'Business Communication',               units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'BOM 102', name: 'Business Mathematics',                  units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'BOM 103', name: 'Principles of Management',             units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'GEC 001', name: 'English Communication Arts',           units: 3, sem: 1, mode: 'Online', prereqs: '',         active: true  },
        { code: 'BOM 201', name: 'Office Procedures and Systems',        units: 3, sem: 2, mode: 'F2F',    prereqs: 'BOM 101',  active: true  },
        { code: 'BOM 202', name: 'Records and Information Management',   units: 3, sem: 2, mode: 'Hybrid', prereqs: '',         active: true  },
        { code: 'BOM 203', name: 'Business Writing and Correspondence',  units: 3, sem: 2, mode: 'F2F',    prereqs: 'BOM 101',  active: true  },
        { code: 'BOM 204', name: 'Human Resource Management Basics',     units: 3, sem: 2, mode: 'F2F',    prereqs: 'BOM 103',  active: false },
    ],
    fsm: [
        { code: 'FSM 101', name: 'Food Science and Nutrition',           units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'FSM 102', name: 'Institutional Food Service Operations',units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'FSM 103', name: 'Food Safety and Hygiene Standards',    units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'GEC 001', name: 'English Communication Arts',           units: 3, sem: 1, mode: 'Online', prereqs: '',         active: true  },
        { code: 'FSM 201', name: 'Menu Planning and Development',        units: 3, sem: 2, mode: 'F2F',    prereqs: 'FSM 101',  active: true  },
        { code: 'FSM 202', name: 'Food Purchasing and Cost Control',     units: 3, sem: 2, mode: 'Hybrid', prereqs: 'FSM 102',  active: true  },
        { code: 'FSM 203', name: 'Quantity Food Production',             units: 3, sem: 2, mode: 'F2F',    prereqs: 'FSM 101',  active: true  },
        { code: 'FSM 204', name: 'Catering and Special Event Catering',  units: 3, sem: 2, mode: 'F2F',    prereqs: 'FSM 203',  active: false },
    ],
    bsoa: [
        { code: 'OA 101',  name: 'Organizational Communication',         units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'OA 102',  name: 'Business Correspondence and Reports',  units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'OA 103',  name: 'Information Management Systems',       units: 3, sem: 1, mode: 'Hybrid', prereqs: '',         active: true  },
        { code: 'GEC 101', name: 'Ethics and Social Responsibility',     units: 3, sem: 1, mode: 'Online', prereqs: '',         active: true  },
        { code: 'OA 201',  name: 'Administrative Office Management',     units: 3, sem: 2, mode: 'F2F',    prereqs: 'OA 101',   active: true  },
        { code: 'OA 202',  name: 'Business Law and Contracts',           units: 3, sem: 2, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'OA 203',  name: 'Records and Archives Management',      units: 3, sem: 2, mode: 'Hybrid', prereqs: 'OA 103',   active: true  },
        { code: 'OA 204',  name: 'Public Relations and Office Protocol', units: 3, sem: 2, mode: 'F2F',    prereqs: 'OA 101',   active: true  },
        { code: 'OA 205',  name: 'Financial Records Management',         units: 3, sem: 2, mode: 'F2F',    prereqs: 'OA 202',   active: false },
    ],
    btvted: [
        { code: 'TVE 101', name: 'Foundation of Technical-Vocational Education', units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'TVE 102', name: 'Curriculum Development in TLE',                units: 3, sem: 1, mode: 'F2F',    prereqs: '',         active: true  },
        { code: 'TVE 103', name: 'Principles of Teaching Technology',            units: 3, sem: 1, mode: 'Hybrid', prereqs: '',         active: true  },
        { code: 'GEC 101', name: 'Ethics and Social Responsibility',             units: 3, sem: 1, mode: 'Online', prereqs: '',         active: true  },
        { code: 'TVE 201', name: 'Assessment in TLE and TVE',                    units: 3, sem: 2, mode: 'F2F',    prereqs: 'TVE 102',  active: true  },
        { code: 'TVE 202', name: 'Technology Integration in Teaching',           units: 3, sem: 2, mode: 'Hybrid', prereqs: 'TVE 103',  active: true  },
        { code: 'TVE 203', name: 'Industrial Arts and Home Economics',           units: 3, sem: 2, mode: 'F2F',    prereqs: 'TVE 101',  active: true  },
        { code: 'TVE 204', name: 'Practicum in Technical Education',             units: 3, sem: 2, mode: 'F2F',    prereqs: 'TVE 201',  active: false },
        { code: 'TVE 205', name: 'Special Topics in TVE Research',               units: 3, sem: 2, mode: 'F2F',    prereqs: 'TVE 202',  active: false },
    ],
};

// ── State ─────────────────────────────────────────────────────────────────────
let activeCat  = 'tesda';
let activeProg = null;
let activeSem  = 1;
let editingIdx = null;

const MODEBADGE = {
    'F2F':    'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
    'Online': 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    'Hybrid': 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
};

// ── Category switcher ─────────────────────────────────────────────────────────
function switchCat(cat) {
    activeCat  = cat;
    activeProg = null;
    document.getElementById('subjectPanel').classList.add('hidden');
    document.querySelectorAll('.cat-tab').forEach(btn => {
        const isSel = btn.dataset.cat === cat;
        const catColor = {
            tesda:     'bg-amber-500 text-white shadow-sm border-transparent',
            associate: 'bg-blue-500 text-white shadow-sm border-transparent',
            bachelor:  'bg-brandNavy text-white shadow-sm border-transparent',
        };
        btn.className = 'cat-tab px-5 py-2 text-xs font-bold rounded-xl transition-all ' + (
            isSel
                ? (catColor[cat] || 'bg-brandNavy text-white shadow-sm')
                : 'bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 text-brandNavy/60 dark:text-slate-400'
        );
    });
    renderProgramCards();
}

// ── Program cards ─────────────────────────────────────────────────────────────
function renderProgramCards() {
    const cat = CATEGORIES[activeCat];
    const wrap = document.getElementById('programCards');
    wrap.innerHTML = cat.programs.map(p => {
        const subjectCount = (SUBJECTS[p.id] || []).length;
        const isActive = activeProg === p.id;
        return `
        <button onclick="selectProgram('${p.id}')"
            class="text-left p-5 rounded-2xl border transition-all ${
                isActive
                    ? `${cat.color.bg} ${cat.color.border} border ring-2 ring-offset-1 ring-${cat.color.dot.replace('bg-', '')}/30`
                    : 'bg-white dark:bg-panelDark border-brandNavy/10 dark:border-slate-800 hover:border-brandNavy/25 dark:hover:border-slate-600 hover:shadow-sm'
            }">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${cat.color.bg} border ${cat.color.border}">
                    <i class="fa-solid ${p.icon} text-sm ${cat.color.text}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[9px] font-black ${cat.color.text} uppercase tracking-wider mb-0.5">${p.abbr}</p>
                    <h4 class="text-xs font-bold text-brandNavy dark:text-white leading-snug">${p.name}</h4>
                    <p class="text-[10px] text-brandNavy/40 dark:text-slate-500 mt-1.5">${subjectCount} subjects defined</p>
                </div>
                ${isActive ? `<i class="fa-solid fa-circle-check ${cat.color.text} text-sm flex-shrink-0 mt-0.5"></i>` : ''}
            </div>
        </button>`;
    }).join('');
}

// ── Program selection ─────────────────────────────────────────────────────────
function selectProgram(progId) {
    activeProg = progId;
    activeSem  = 1;
    renderProgramCards();

    const cat  = CATEGORIES[activeCat];
    const prog = cat.programs.find(p => p.id === progId);
    document.getElementById('progCatLabel').textContent  = cat.label + ' Program';
    document.getElementById('progNameLabel').textContent = prog.name;
    document.getElementById('subjectPanel').classList.remove('hidden');
    document.getElementById('searchInput').value = '';

    switchSem(1);
}

// ── Semester switcher ─────────────────────────────────────────────────────────
function switchSem(sem) {
    activeSem = sem;
    const s1 = document.getElementById('sem1Tab');
    const s2 = document.getElementById('sem2Tab');
    const active  = 'px-5 py-2 text-xs font-bold rounded-xl transition-all bg-brandNavy text-white shadow-sm';
    const inactive = 'px-5 py-2 text-xs font-bold rounded-xl transition-all bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 text-brandNavy/60 dark:text-slate-400';
    s1.className = sem === 1 ? active : inactive;
    s2.className = sem === 2 ? active : inactive;
    document.getElementById('semTitle').textContent = (sem === 1 ? '1st' : '2nd') + ' Semester Offerings';
    document.getElementById('searchInput').value = '';
    renderTable();
}

// ── Table render ─────────────────────────────────────────────────────────────
function filterTable() { renderTable(document.getElementById('searchInput').value.toLowerCase()); }

function renderTable(q = '') {
    if (!activeProg) return;
    const all  = SUBJECTS[activeProg] || [];
    const rows = all.filter(s => s.sem === activeSem && (!q || s.code.toLowerCase().includes(q) || s.name.toLowerCase().includes(q)));
    const tbody = document.getElementById('subjectTableBody');
    const empty = document.getElementById('emptyState');

    if (rows.length === 0) { tbody.innerHTML = ''; empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');

    tbody.innerHTML = rows.map(s => {
        const idx = SUBJECTS[activeProg].indexOf(s);
        const modeCls = MODEBADGE[s.mode] || MODEBADGE['F2F'];
        return `
        <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
            <td class="py-4 px-5 font-black text-brandNavy dark:text-white font-mono text-[11px]">${s.code}</td>
            <td class="py-4 px-5 text-brandNavy/80 dark:text-slate-300">${s.name}</td>
            <td class="py-4 px-5 text-center font-bold text-brandNavy/70 dark:text-slate-400">${s.units}</td>
            <td class="py-4 px-5 text-center">
                <span class="inline-block px-2 py-0.5 text-[9px] font-black rounded-full border uppercase tracking-wide ${modeCls}">${s.mode}</span>
            </td>
            <td class="py-4 px-5 text-brandNavy/50 dark:text-slate-500 font-mono text-[10px]">
                ${s.prereqs || '<span class="text-brandNavy/25 dark:text-slate-700">—</span>'}
            </td>
            <td class="py-4 px-5 text-center">
                <button onclick="toggleActive('${activeProg}',${idx})"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[9px] font-black rounded-full border uppercase tracking-wide transition-all
                    ${s.active
                        ? 'bg-brandGreen/10 text-brandGreen border-brandGreen/20 hover:bg-brandGreen/20'
                        : 'bg-slate-100 dark:bg-slate-800 text-brandNavy/40 dark:text-slate-500 border-brandNavy/10 dark:border-slate-700'}">
                    <i class="fa-solid ${s.active ? 'fa-circle-check' : 'fa-circle-xmark'}" style="font-size:9px"></i>
                    ${s.active ? 'Active' : 'Inactive'}
                </button>
            </td>
            <td class="py-4 px-5 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button onclick="openEditModal('${activeProg}',${idx})" class="w-8 h-8 rounded-lg hover:bg-brandNavy/8 dark:hover:bg-slate-700 flex items-center justify-center text-brandNavy/50 dark:text-slate-400 transition-colors" title="Edit">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                    <button onclick="deleteSubject('${activeProg}',${idx})" class="w-8 h-8 rounded-lg hover:bg-red-500/10 flex items-center justify-center text-red-400/60 hover:text-red-500 transition-colors" title="Delete">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function toggleActive(progId, idx) {
    SUBJECTS[progId][idx].active = !SUBJECTS[progId][idx].active;
    renderTable(document.getElementById('searchInput').value.toLowerCase());
}

// ── Modal ─────────────────────────────────────────────────────────────────────
function openAddModal() {
    editingIdx = null;
    document.getElementById('modalTitle').textContent = 'Add Subject';
    document.getElementById('mCode').value    = '';
    document.getElementById('mName').value    = '';
    document.getElementById('mUnits').value   = '3';
    document.getElementById('mSem').value     = String(activeSem);
    document.getElementById('mMode').value    = 'F2F';
    document.getElementById('mPrereqs').value = '';
    document.getElementById('mActive').checked = true;
    document.getElementById('subjectModal').classList.remove('hidden');
}

function openEditModal(progId, idx) {
    editingIdx = { progId, idx };
    const s = SUBJECTS[progId][idx];
    document.getElementById('modalTitle').textContent = 'Edit Subject';
    document.getElementById('mCode').value    = s.code;
    document.getElementById('mName').value    = s.name;
    document.getElementById('mUnits').value   = s.units;
    document.getElementById('mSem').value     = String(s.sem);
    document.getElementById('mMode').value    = s.mode;
    document.getElementById('mPrereqs').value = s.prereqs;
    document.getElementById('mActive').checked = s.active;
    document.getElementById('subjectModal').classList.remove('hidden');
}

function closeModal() { document.getElementById('subjectModal').classList.add('hidden'); }

function saveSubject() {
    const code = document.getElementById('mCode').value.trim();
    const name = document.getElementById('mName').value.trim();
    if (!code || !name) { alert('Subject code and name are required.'); return; }

    const subj = {
        code,
        name,
        units:   parseInt(document.getElementById('mUnits').value) || 3,
        sem:     parseInt(document.getElementById('mSem').value),
        mode:    document.getElementById('mMode').value,
        prereqs: document.getElementById('mPrereqs').value.trim(),
        active:  document.getElementById('mActive').checked,
    };

    if (editingIdx !== null) {
        SUBJECTS[editingIdx.progId][editingIdx.idx] = subj;
    } else {
        if (!SUBJECTS[activeProg]) SUBJECTS[activeProg] = [];
        SUBJECTS[activeProg].push(subj);
        activeSem = subj.sem;
    }
    closeModal();
    switchSem(subj.sem);
    renderProgramCards();
}

function deleteSubject(progId, idx) {
    if (!confirm('Remove "' + SUBJECTS[progId][idx].code + '" from this program?')) return;
    SUBJECTS[progId].splice(idx, 1);
    renderTable(document.getElementById('searchInput').value.toLowerCase());
    renderProgramCards();
}

document.getElementById('subjectModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// ── Boot ──────────────────────────────────────────────────────────────────────
switchCat('tesda');
</script>

@include('partials.notif-script')
</body>
</html>
