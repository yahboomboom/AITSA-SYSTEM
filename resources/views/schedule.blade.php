<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | My Schedule</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('js/qrcode.min.js') }}"></script>

    <style>
        @media print {
            body { background: white !important; }
            aside, header, .no-print { display: none !important; }
            main { overflow: visible !important; }
            .print-area { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="flex h-screen overflow-hidden">

    {{-- ------------------- SIDEBAR ------------------- --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300 no-print">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium"><span>Dashboard</span>
            </a>
            <a href="{{ route('clearance') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium"><span>Clearance Routing</span>
            </a>
            <a href="{{ route('enrollment') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium"><span>Enrollment System</span>
            </a>
            <a href="{{ route('ledger') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium"><span>Ledger & Payments</span>
            </a>
            <a href="{{ route('cor') }}" class="flex items-center space-x-3 px-4 py-3 bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 rounded-xl font-bold text-sm"><span>Schedule</span>
            </a>
        </nav>
    </aside>

    {{-- ------------------- MAIN ------------------- --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        {{-- HEADER --}}
        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300 flex-shrink-0 no-print">
            <div class="flex items-center gap-3">
                <button class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">My Schedule &amp; COR</h2>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()"
                    class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-print"></i>Print COR
                </button>
                <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT - Web Development'])
                </div>
            </div>
        </header>

        {{-- CONTENT --}}
        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            {{-- PAGE TITLE --}}
            <div class="print-area">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-1">
                    <div>
                        <h1 class="text-3xl font-black text-brandNavy dark:text-white">Certificate of Registration</h1>
                        <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                            Academic Year 2025–2026 &nbsp;—&nbsp; 1st Semester
                            &nbsp;<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brandGreen/10 text-brandGreen text-[10px] font-bold border border-brandGreen/20 uppercase tracking-wider">
                                <i class="fa-solid fa-circle-check text-[8px]"></i>Enrolled
                            </span>
                        </p>
                    </div>
                    <div class="text-left md:text-right text-xs text-brandNavy/50 dark:text-slate-500">
                        <p class="font-mono font-bold">Student No: {{ Auth::user()->login_id ?? 'N/A' }}</p>
                        <p>{{ Auth::user()->major ?? 'BSIT - Web Development' }}</p>
                    </div>
                </div>
            </div>

            {{-- --------------------------------
                 SECTION 2 — WEEKLY TIMETABLE
            -------------------------------- --}}
            <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print-area">
                <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i class="fa-solid fa-table-cells-large mr-2 text-brandGreen"></i>Weekly Timetable
                    </span>
                </div>
                <div class="p-4 lg:p-6 overflow-x-auto">
                    @if (count($subjects ?? []) === 0)
                        <p class="text-sm text-brandNavy/50 dark:text-slate-400 py-6 text-center">
                            No enrolled subjects yet — complete your enrollment first.
                        </p>
                    @else
                        <div id="timetable" class="min-w-[520px]"></div>
                    @endif
                </div>
            </div>

            {{-- --------------------------------
                 SECTION 3 — QR CODE
            -------------------------------- --}}
            <div class="flex justify-center print-area">
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm w-full max-w-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                        <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                            <i class="fa-solid fa-qrcode mr-2 text-brandGreen"></i>Schedule QR Code
                        </span>
                    </div>
                    <div class="p-6 flex flex-col items-center gap-4">
                        <div class="p-3 bg-white rounded-xl border border-brandNavy/10 shadow-sm inline-block">
                            <div id="qrcode"></div>
                        </div>
                        <div class="text-center space-y-1">
                            <p class="text-xs font-bold text-brandNavy dark:text-slate-200">{{ Auth::user()->name ?? 'Student' }}</p>
                            <p class="text-[10px] text-brandNavy/50 dark:text-slate-500">Scan to view schedule — AY 2025–2026 1st Sem</p>
                        </div>
                        <button onclick="window.print()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0">
                            <i class="fa-solid fa-print"></i>Print / Download COR
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- end content --}}
    </main>
</div>

{{-- ----------------------------------------
     TIMETABLE + QR RENDER SCRIPTS
---------------------------------------- --}}
<script>
const SUBJECTS = @json($subjects ?? []);
const STUDENT_NAME = "{{ Auth::user()->name ?? 'Student' }}";
const STUDENT_ID   = "{{ Auth::user()->login_id ?? 'N/A' }}";
const STUDENT_PROG = "{{ Auth::user()->major ?? 'BSIT - Web Development' }}";

// -- Color mapping from Tailwind class ? hex ---------------------------------
const COLOR_MAP = {
    'bg-blue-600':    '#2563EB',
    'bg-emerald-600': '#059669',
    'bg-violet-600':  '#7C3AED',
    'bg-orange-500':  '#F97316',
    'bg-cyan-600':    '#0891B2',
    'bg-teal-600':    '#0D9488',
    'bg-rose-500':    '#F43F5E',
    'bg-amber-500':   '#F59E0B',
    'bg-brandGreen':  '#1D7A46',
    'bg-indigo-600':  '#4F46E5',
    'bg-pink-500':    '#EC4899',
};

// -- Day parser ---------------------------------------------------------------
function parseDays(dayStr) {
    const map = { 'Sun': 6, 'Sat': 5, 'Th': 3, 'M': 0, 'T': 1, 'W': 2, 'F': 4 };
    const priority = ['Sun', 'Sat', 'Th', 'M', 'T', 'W', 'F'];
    const result = [];
    let s = dayStr;
    while (s.length > 0) {
        let matched = false;
        for (const key of priority) {
            if (s.startsWith(key)) {
                result.push(map[key]);
                s = s.slice(key.length);
                matched = true;
                break;
            }
        }
        if (!matched) s = s.slice(1);
    }
    return result;
}

// -- Time parser --------------------------------------------------------------
function parseTimePart(t) {
    // Accepts "8:00 AM" (12-hour) or "08:00" (24-hour, as stored on sections)
    const m = t.trim().match(/(\d+):(\d+)\s*(AM|PM)?/i);
    if (!m) return null;
    let h = parseInt(m[1]), min = parseInt(m[2]);
    const ap = m[3] ? m[3].toUpperCase() : null;
    if (ap === 'PM' && h !== 12) h += 12;
    if (ap === 'AM' && h === 12) h = 0;
    return h * 60 + min;
}

function parseTimeRange(range) {
    // Supports "7:00–8:00 AM" (en-dash) or "7:00-8:00 AM"
    const parts = range.split(/\s*[—\-]\s*/);
    if (parts.length < 2) return null;

    let startStr = parts[0].trim();
    let endStr   = parts[1].trim();

    // If start lacks AM/PM, inherit from end
    if (!/AM|PM/i.test(startStr)) {
        const m = endStr.match(/(AM|PM)/i);
        if (m) startStr += ' ' + m[0];
    }

    const start = parseTimePart(startStr);
    const end   = parseTimePart(endStr);
    if (start === null || end === null) return null;
    return { start, end };
}

// -- Render weekly timetable --------------------------------------------------
function renderTimetable() {
    const SLOT_H     = 34;
    const GRID_START = 6 * 60;   // 06:00
    const GRID_END   = 21 * 60;  // 21:00
    const NUM_SLOTS  = (GRID_END - GRID_START) / 30;
    const TOTAL_H    = NUM_SLOTS * SLOT_H;
    const DAY_NAMES  = ['MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY','SUNDAY'];
    const isDark     = document.documentElement.classList.contains('dark');

    const TIME_W    = 108;
    const border    = isDark ? '#2d3748' : '#e5e7eb';
    const timeClr   = isDark ? 'rgba(255,255,255,0.38)' : '#9ca3af';
    const headClr   = isDark ? 'rgba(255,255,255,0.85)' : '#111827';
    const cellBg    = isDark ? '#1e1e1e' : '#ffffff';

    const container = document.getElementById('timetable');
    if (!container) return;
    container.innerHTML = '';

    const wrap = document.createElement('div');
    wrap.style.cssText = `border:1px solid ${border};overflow:hidden;font-family:inherit;`;

    // -- Header -----------------------------------------------------------
    const hdr = document.createElement('div');
    hdr.style.cssText = `display:flex;border-bottom:1px solid ${border};background:${cellBg};`;

    const timeHdrCell = document.createElement('div');
    timeHdrCell.style.cssText = `width:${TIME_W}px;flex-shrink:0;padding:10px 10px;border-right:1px solid ${border};display:flex;align-items:center;justify-content:center;`;
    timeHdrCell.innerHTML = `<span style="font-size:9.5px;font-weight:900;letter-spacing:0.1em;color:${headClr};">TIME</span>`;
    hdr.appendChild(timeHdrCell);

    DAY_NAMES.forEach((day, i) => {
        const cell = document.createElement('div');
        cell.style.cssText = `flex:1;text-align:center;padding:10px 2px;${i > 0 ? `border-left:1px solid ${border};` : ''}display:flex;align-items:center;justify-content:center;`;
        cell.innerHTML = `<span style="font-size:9px;font-weight:900;letter-spacing:0.07em;color:${headClr};">${day}</span>`;
        hdr.appendChild(cell);
    });
    wrap.appendChild(hdr);

    // -- Body -------------------------------------------------------------
    const body = document.createElement('div');
    body.style.cssText = `display:flex;height:${TOTAL_H}px;background:${cellBg};`;

    // Time column
    const timeCol = document.createElement('div');
    timeCol.style.cssText = `width:${TIME_W}px;flex-shrink:0;position:relative;border-right:1px solid ${border};background:${cellBg};`;

    // Day columns
    const daysCont = document.createElement('div');
    daysCont.style.cssText = `flex:1;display:flex;`;

    const dayColEls = DAY_NAMES.map((_, i) => {
        const col = document.createElement('div');
        col.style.cssText = `flex:1;position:relative;${i > 0 ? `border-left:1px solid ${border};` : ''}`;
        daysCont.appendChild(col);
        return col;
    });

    // -- Slot rows ---------------------------------------------------------
    for (let slot = 0; slot < NUM_SLOTS; slot++) {
        const mins   = GRID_START + slot * 30;
        const yPx    = slot * SLOT_H;
        const endMins = mins + 30;

        const sH = String(Math.floor(mins / 60)).padStart(2, '0');
        const sM = String(mins % 60).padStart(2, '0');
        const eH = String(Math.floor(endMins / 60)).padStart(2, '0');
        const eM = String(endMins % 60).padStart(2, '0');

        // Time label
        const lbl = document.createElement('div');
        lbl.style.cssText = `position:absolute;top:${yPx}px;left:0;right:0;height:${SLOT_H}px;display:flex;align-items:center;justify-content:flex-end;padding-right:10px;${slot > 0 ? `border-top:1px solid ${border};` : ''}`;
        lbl.innerHTML = `<span style="font-size:8.5px;color:${timeClr};font-weight:600;white-space:nowrap;">${sH}:${sM} - ${eH}:${eM}</span>`;
        timeCol.appendChild(lbl);

        // Row divider across day columns
        if (slot > 0) {
            dayColEls.forEach(col => {
                const line = document.createElement('div');
                line.style.cssText = `position:absolute;top:${yPx}px;left:0;right:0;height:1px;background:${border};`;
                col.appendChild(line);
            });
        }
    }

    // -- Subject blocks ----------------------------------------------------
    SUBJECTS.forEach(subj => {
        const tr = parseTimeRange(subj.time);
        if (!tr || tr.start < GRID_START || tr.end > GRID_END) return;

        const days  = parseDays(subj.days);
        const color = COLOR_MAP[subj.color] || '#0B3C5D';
        const topPx = (tr.start - GRID_START) / 30 * SLOT_H;
        const htPx  = (tr.end   - tr.start)  / 30 * SLOT_H;

        days.forEach(dayIdx => {
            if (dayIdx >= dayColEls.length) return;
            const block = document.createElement('div');
            block.style.cssText = `
                position:absolute;
                top:${topPx}px;
                left:0;
                right:0;
                height:${htPx}px;
                background:${color};
                padding:6px 8px;
                overflow:hidden;
                cursor:default;
                box-sizing:border-box;
            `;
            block.innerHTML = `
                <div style="font-size:10px;font-weight:900;color:#fff;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${subj.code}</div>
                ${htPx >= 46 ? `<div style="font-size:8px;color:rgba(255,255,255,0.72);margin-top:3px;font-weight:500;">${subj.type || 'Lecture'}</div>` : ''}
                ${htPx >= 62 ? `<div style="font-size:8px;color:rgba(255,255,255,0.72);margin-top:1px;">${subj.room}</div>` : ''}
            `;
            dayColEls[dayIdx].appendChild(block);
        });
    });

    body.appendChild(timeCol);
    body.appendChild(daysCont);
    wrap.appendChild(body);
    container.appendChild(wrap);
}

// -- Render QR Code -----------------------------------------------------------
function renderQRCode() {
    let text = `AITSA SCHEDULE\n`;
    text += `Student: ${STUDENT_NAME}\n`;
    text += `ID: ${STUDENT_ID} | ${STUDENT_PROG}\n`;
    text += `AY 2025-2026 | 1st Semester\n\n`;
    SUBJECTS.forEach(s => {
        text += `${s.code} - ${s.desc}\n`;
        text += `${s.days} | ${s.time} | ${s.room} [${s.type}]\n\n`;
    });

    const isDark = document.documentElement.classList.contains('dark');

    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById('qrcode'), {
            text: text.trim(),
            width: 176,
            height: 176,
            colorDark: '#0B3C5D',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }
}

// -- Init on load -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
    renderTimetable();
    renderQRCode();
});

// Re-render timetable when theme is toggled (colors change)
const _origToggle = window.toggleTheme;
window.toggleTheme = function() {
    _origToggle();
    renderTimetable();
};
</script>

@include('partials.notif-script')
</body>
</html>
