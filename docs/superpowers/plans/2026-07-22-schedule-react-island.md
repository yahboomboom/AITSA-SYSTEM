# Schedule (COR) React Island Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert `resources/views/schedule.blade.php`'s content area (COR header, weekly timetable grid, QR code card) into a React island (`schedule-app.jsx`), re-implementing the existing vanilla-JS timetable/QR logic as React components with no change to server-side data or behavior.

**Architecture:** Three presentational/logic components under `resources/js/schedule/` (`CorHeader`, `WeeklyTimetable`, `ScheduleQRCode`), mounted by a `schedule-app.jsx` entry into a `#schedule-root` div that replaces the current inline markup + `<script>` block in the Blade view. Sidebar, header, the `qrcode.min.js` global script include, and the `@media print` styles are untouched.

**Tech Stack:** React 18, Vite (`laravel-vite-plugin`, `@vitejs/plugin-react`), Blade, PHPUnit, the existing global `qrcode.min.js` (`public/js/qrcode.min.js`).

## Global Constraints

- Re-implement the timetable-grid algorithm (`parseDays`, `parseTimePart`, `parseTimeRange`, slot/block layout) as React JSX + state — do not call the old imperative `renderTimetable()` from a `useEffect`/ref.
- Reuse the existing global `window.QRCode` (from `public/js/qrcode.min.js`, already included in `<head>`) — do not add an npm QR package.
- Detect dark-mode changes via a `MutationObserver` on `document.documentElement`'s `class` attribute — do not override `window.toggleTheme`.
- Data reaches the island via `data-*` attributes (JSON-encoded) on `#schedule-root` — no new API endpoint, no `window.__SCHEDULE_DATA__` global.
- No change to `AuthController::showCor()` or `routes/web.php`.
- Sidebar, `<header>` (including its own "Print COR" button), the `<script src="{{ asset('js/qrcode.min.js') }}">` include, the `@media print` `<style>` block, and `@include('partials.notif-script')` must NOT be touched.
- No JS test runner in this repo — verification is `npm run build` succeeding, a PHPUnit Feature test, and an authenticated-curl HTTP-level check. Manual/visual browser verification is a known, explicitly-flagged gap (no browser-automation tool connected).
- The outer content div (`class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6"`, stays in Blade) currently relies on Tailwind's `space-y-6` for spacing between its three section children. Once those three sections become a single `#schedule-root` child, that spacing rule no longer applies — the React root component must wrap its three sections in its own `space-y-6` div to preserve the original vertical spacing (unlike the dashboard island's Fragment, which didn't need this because `WelcomeBanner` already carried its own `mb-6`).

---

### Task 1: Create `WeeklyTimetable.jsx`

**Files:**
- Create: `resources/js/schedule/WeeklyTimetable.jsx`

**Interfaces:**
- Produces: `WeeklyTimetable` (default export), props: `{ subjects: Array<{code, desc, units, days, time, room, type, color}> }`. Consumed by `schedule-app.jsx` (Task 3).

This is the highest-risk file — it re-implements the current vanilla-JS grid algorithm (`resources/views/schedule.blade.php`'s `parseDays`/`parseTimePart`/`parseTimeRange`/`renderTimetable`, lines 150-342 of the current file) as JSX. No automated test is possible (no JS test runner in this repo) — verification for this task is a careful line-by-line comparison against the original algorithm during review; build verification happens in Task 2 once this file is actually imported.

Reference — the original algorithm being ported (for comparison during review):
- `COLOR_MAP`: maps Tailwind bg-class strings to hex colors.
- `parseDays(dayStr)`: walks the string left to right, matching day-prefixes `['Sun','Sat','Th','M','T','W','F']` in that priority order (so `'Th'` matches before the bare `'T'` check), skipping one character on no match. Returns array of day indices (`M=0, T=1, W=2, Th=3, F=4, Sat=5, Sun=6`).
- `parseTimePart(t)`: regex `/(\d+):(\d+)\s*(AM|PM)?/i`, converts to minutes-since-midnight, applying 12-hour → 24-hour conversion when AM/PM is present.
- `parseTimeRange(range)`: splits on `/\s*[—–\-]\s*/` (em-dash, en-dash, or hyphen — the en-dash is the character `AuthController::showCor()` actually emits, fixed in commit `e4fd695`), infers AM/PM on the start time from the end time when missing, returns `{ start, end }` in minutes.
- `renderTimetable()`: builds a grid from `GRID_START=6*60` to `GRID_END=21*60` in 30-minute slots (`SLOT_H=34px` each), a `TIME` column (`TIME_W=108px`) plus 7 day columns, and absolutely-positions one block per (subject × day) pair using the parsed start/end times, skipping any subject whose range falls outside the grid or fails to parse.

- [ ] **Step 1: Create `resources/js/schedule/WeeklyTimetable.jsx`**

```jsx
import { useEffect, useMemo, useState } from 'react';

const SLOT_H = 34;
const GRID_START = 6 * 60;
const GRID_END = 21 * 60;
const NUM_SLOTS = (GRID_END - GRID_START) / 30;
const TOTAL_H = NUM_SLOTS * SLOT_H;
const TIME_W = 108;
const DAY_NAMES = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

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

function parseDays(dayStr) {
    const map = { Sun: 6, Sat: 5, Th: 3, M: 0, T: 1, W: 2, F: 4 };
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

function parseTimePart(t) {
    const m = t.trim().match(/(\d+):(\d+)\s*(AM|PM)?/i);
    if (!m) return null;
    let h = parseInt(m[1], 10);
    const min = parseInt(m[2], 10);
    const ap = m[3] ? m[3].toUpperCase() : null;
    if (ap === 'PM' && h !== 12) h += 12;
    if (ap === 'AM' && h === 12) h = 0;
    return h * 60 + min;
}

function parseTimeRange(range) {
    const parts = range.split(/\s*[—–\-]\s*/);
    if (parts.length < 2) return null;

    let startStr = parts[0].trim();
    const endStr = parts[1].trim();

    if (!/AM|PM/i.test(startStr)) {
        const m = endStr.match(/(AM|PM)/i);
        if (m) startStr += ' ' + m[0];
    }

    const start = parseTimePart(startStr);
    const end = parseTimePart(endStr);
    if (start === null || end === null) return null;
    return { start, end };
}

function useIsDarkMode() {
    const [isDark, setIsDark] = useState(
        () => typeof document !== 'undefined' && document.documentElement.classList.contains('dark')
    );

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setIsDark(document.documentElement.classList.contains('dark'));
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);

    return isDark;
}

export default function WeeklyTimetable({ subjects }) {
    const isDark = useIsDarkMode();

    const colors = useMemo(() => ({
        border: isDark ? '#2d3748' : '#e5e7eb',
        timeClr: isDark ? 'rgba(255,255,255,0.38)' : '#9ca3af',
        headClr: isDark ? 'rgba(255,255,255,0.85)' : '#111827',
        cellBg: isDark ? '#1e1e1e' : '#ffffff',
    }), [isDark]);

    const blocks = useMemo(() => {
        const result = [];
        subjects.forEach((subj) => {
            const tr = parseTimeRange(subj.time);
            if (!tr || tr.start < GRID_START || tr.end > GRID_END) return;

            const days = parseDays(subj.days);
            const color = COLOR_MAP[subj.color] || '#0B3C5D';
            const topPx = ((tr.start - GRID_START) / 30) * SLOT_H;
            const htPx = ((tr.end - tr.start) / 30) * SLOT_H;

            days.forEach((dayIdx) => {
                if (dayIdx >= DAY_NAMES.length) return;
                result.push({ key: `${subj.code}-${dayIdx}`, dayIdx, topPx, htPx, color, subj });
            });
        });
        return result;
    }, [subjects]);

    if (subjects.length === 0) {
        return (
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print-area">
                <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i className="fa-solid fa-table-cells-large mr-2 text-brandGreen" />Weekly Timetable
                    </span>
                </div>
                <div className="p-4 lg:p-6 overflow-x-auto">
                    <p className="text-sm text-brandNavy/50 dark:text-slate-400 py-6 text-center">
                        No enrolled subjects yet — complete your enrollment first.
                    </p>
                </div>
            </div>
        );
    }

    const slots = Array.from({ length: NUM_SLOTS }, (_, slot) => {
        const mins = GRID_START + slot * 30;
        const endMins = mins + 30;
        const sH = String(Math.floor(mins / 60)).padStart(2, '0');
        const sM = String(mins % 60).padStart(2, '0');
        const eH = String(Math.floor(endMins / 60)).padStart(2, '0');
        const eM = String(endMins % 60).padStart(2, '0');
        return { slot, yPx: slot * SLOT_H, label: `${sH}:${sM} - ${eH}:${eM}` };
    });

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print-area">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-table-cells-large mr-2 text-brandGreen" />Weekly Timetable
                </span>
            </div>
            <div className="p-4 lg:p-6 overflow-x-auto">
                <div className="min-w-[520px]">
                    <div style={{ border: `1px solid ${colors.border}`, overflow: 'hidden', fontFamily: 'inherit' }}>
                        <div style={{ display: 'flex', borderBottom: `1px solid ${colors.border}`, background: colors.cellBg }}>
                            <div style={{ width: TIME_W, flexShrink: 0, padding: '10px 10px', borderRight: `1px solid ${colors.border}`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                <span style={{ fontSize: 9.5, fontWeight: 900, letterSpacing: '0.1em', color: colors.headClr }}>TIME</span>
                            </div>
                            {DAY_NAMES.map((day, i) => (
                                <div key={day} style={{ flex: 1, textAlign: 'center', padding: '10px 2px', borderLeft: i > 0 ? `1px solid ${colors.border}` : undefined, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                    <span style={{ fontSize: 9, fontWeight: 900, letterSpacing: '0.07em', color: colors.headClr }}>{day}</span>
                                </div>
                            ))}
                        </div>

                        <div style={{ display: 'flex', height: TOTAL_H, background: colors.cellBg }}>
                            <div style={{ width: TIME_W, flexShrink: 0, position: 'relative', borderRight: `1px solid ${colors.border}`, background: colors.cellBg }}>
                                {slots.map(({ slot, yPx, label }) => (
                                    <div key={slot} style={{ position: 'absolute', top: yPx, left: 0, right: 0, height: SLOT_H, display: 'flex', alignItems: 'center', justifyContent: 'flex-end', paddingRight: 10, borderTop: slot > 0 ? `1px solid ${colors.border}` : undefined }}>
                                        <span style={{ fontSize: 8.5, color: colors.timeClr, fontWeight: 600, whiteSpace: 'nowrap' }}>{label}</span>
                                    </div>
                                ))}
                            </div>

                            <div style={{ flex: 1, display: 'flex' }}>
                                {DAY_NAMES.map((day, dayIdx) => (
                                    <div key={day} style={{ flex: 1, position: 'relative', borderLeft: dayIdx > 0 ? `1px solid ${colors.border}` : undefined }}>
                                        {slots.filter(({ slot }) => slot > 0).map(({ slot, yPx }) => (
                                            <div key={slot} style={{ position: 'absolute', top: yPx, left: 0, right: 0, height: 1, background: colors.border }} />
                                        ))}
                                        {blocks.filter((b) => b.dayIdx === dayIdx).map((b) => (
                                            <div
                                                key={b.key}
                                                style={{
                                                    position: 'absolute',
                                                    top: b.topPx,
                                                    left: 0,
                                                    right: 0,
                                                    height: b.htPx,
                                                    background: b.color,
                                                    padding: '6px 8px',
                                                    overflow: 'hidden',
                                                    cursor: 'default',
                                                    boxSizing: 'border-box',
                                                }}
                                            >
                                                <div style={{ fontSize: 10, fontWeight: 900, color: '#fff', lineHeight: 1.25, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                    {b.subj.code}
                                                </div>
                                                {b.htPx >= 46 && (
                                                    <div style={{ fontSize: 8, color: 'rgba(255,255,255,0.72)', marginTop: 3, fontWeight: 500 }}>
                                                        {b.subj.type || 'Lecture'}
                                                    </div>
                                                )}
                                                {b.htPx >= 62 && (
                                                    <div style={{ fontSize: 8, color: 'rgba(255,255,255,0.72)', marginTop: 1 }}>
                                                        {b.subj.room}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/schedule/WeeklyTimetable.jsx
git commit -m "feat: add WeeklyTimetable React component for schedule island"
```

---

### Task 2: Create `CorHeader.jsx`, `ScheduleQRCode.jsx`, the Vite entry, and wire `vite.config.js`

**Files:**
- Create: `resources/js/schedule/CorHeader.jsx`
- Create: `resources/js/schedule/ScheduleQRCode.jsx`
- Create: `resources/js/schedule-app.jsx`
- Modify: `vite.config.js`

**Interfaces:**
- Consumes: `WeeklyTimetable` (Task 1, default export, props `{ subjects }`).
- Produces: `CorHeader` (default export, props `{ studentId, studentProgram }`), `ScheduleQRCode` (default export, props `{ subjects, studentName, studentId, studentProgram }`), all three mounted by `schedule-app.jsx`.
- Produces: a `#schedule-root` mount point contract with `data-subjects` / `data-student-name` / `data-student-id` / `data-student-program` attributes — Task 3's Blade edit must provide this element.

This task's build step is the first point where `WeeklyTimetable.jsx` (Task 1) actually gets compiled (nothing imports it until `schedule-app.jsx` exists), so a successful build here verifies both tasks' files.

- [ ] **Step 1: Create `resources/js/schedule/CorHeader.jsx`**

Ported verbatim from the current Blade markup (`resources/views/schedule.blade.php:73-89`). The hardcoded "Academic Year 2025–2026 — 1st Semester" text is preserved exactly as-is (it does not derive from the enrollment's actual term) — fixing that mismatch is out of scope for this port.

```jsx
export default function CorHeader({ studentId, studentProgram }) {
    return (
        <div className="print-area">
            <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-1">
                <div>
                    <h1 className="text-3xl font-black text-brandNavy dark:text-white">Certificate of Registration</h1>
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                        Academic Year 2025–2026 &nbsp;—&nbsp; 1st Semester
                        &nbsp;<span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brandGreen/10 text-brandGreen text-[10px] font-bold border border-brandGreen/20 uppercase tracking-wider">
                            <i className="fa-solid fa-circle-check text-[8px]" />Enrolled
                        </span>
                    </p>
                </div>
                <div className="text-left md:text-right text-xs text-brandNavy/50 dark:text-slate-500">
                    <p className="font-mono font-bold">Student No: {studentId}</p>
                    <p>{studentProgram}</p>
                </div>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Create `resources/js/schedule/ScheduleQRCode.jsx`**

Ported from the current Blade markup + inline script (`resources/views/schedule.blade.php:111-135` for markup, `344-367` for the `renderQRCode()` logic being ported). Uses the existing global `window.QRCode` from `public/js/qrcode.min.js` (already loaded via `<head>`, untouched by this plan):

```jsx
import { useEffect, useRef } from 'react';

export default function ScheduleQRCode({ subjects, studentName, studentId, studentProgram }) {
    const qrRef = useRef(null);

    useEffect(() => {
        if (!qrRef.current || typeof window.QRCode === 'undefined') return;

        let text = `AITSA SCHEDULE\n`;
        text += `Student: ${studentName}\n`;
        text += `ID: ${studentId} | ${studentProgram}\n`;
        text += `AY 2025-2026 | 1st Semester\n\n`;
        subjects.forEach((s) => {
            text += `${s.code} - ${s.desc}\n`;
            text += `${s.days} | ${s.time} | ${s.room} [${s.type}]\n\n`;
        });

        qrRef.current.innerHTML = '';
        new window.QRCode(qrRef.current, {
            text: text.trim(),
            width: 176,
            height: 176,
            colorDark: '#0B3C5D',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.M,
        });
    }, [subjects, studentName, studentId, studentProgram]);

    return (
        <div className="flex justify-center print-area">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm w-full max-w-sm">
                <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i className="fa-solid fa-qrcode mr-2 text-brandGreen" />Schedule QR Code
                    </span>
                </div>
                <div className="p-6 flex flex-col items-center gap-4">
                    <div className="p-3 bg-white rounded-xl border border-brandNavy/10 shadow-sm inline-block">
                        <div ref={qrRef} />
                    </div>
                    <div className="text-center space-y-1">
                        <p className="text-xs font-bold text-brandNavy dark:text-slate-200">{studentName}</p>
                        <p className="text-[10px] text-brandNavy/50 dark:text-slate-500">Scan to view schedule — AY 2025–2026 1st Sem</p>
                    </div>
                    <button
                        onClick={() => window.print()}
                        className="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <i className="fa-solid fa-print" />Print / Download COR
                    </button>
                </div>
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Create `resources/js/schedule-app.jsx`**

Reads the server-rendered data off `#schedule-root`'s `data-*` attributes (JSON-encoded by Blade in Task 3), falling back to an empty subjects array if parsing fails. Wraps the three sections in a `space-y-6` div — see the Global Constraints note on why this replaces the outer Blade div's `space-y-6` behavior:

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import CorHeader from './schedule/CorHeader';
import WeeklyTimetable from './schedule/WeeklyTimetable';
import ScheduleQRCode from './schedule/ScheduleQRCode';

function parseSubjects(raw) {
    try {
        const parsed = JSON.parse(raw ?? '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function ScheduleApp({ subjects, studentName, studentId, studentProgram }) {
    return (
        <div className="space-y-6">
            <CorHeader studentId={studentId} studentProgram={studentProgram} />
            <WeeklyTimetable subjects={subjects} />
            <ScheduleQRCode
                subjects={subjects}
                studentName={studentName}
                studentId={studentId}
                studentProgram={studentProgram}
            />
        </div>
    );
}

const el = document.getElementById('schedule-root');
if (el) {
    const subjects = parseSubjects(el.dataset.subjects);
    const studentName = el.dataset.studentName ?? 'Student';
    const studentId = el.dataset.studentId ?? 'N/A';
    const studentProgram = el.dataset.studentProgram ?? 'BSIT - Web Development';
    createRoot(el).render(
        <ScheduleApp
            subjects={subjects}
            studentName={studentName}
            studentId={studentId}
            studentProgram={studentProgram}
        />
    );
}
```

- [ ] **Step 4: Register the new entry in `vite.config.js`**

Current `input` array (after the dashboard island was added):
```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/enrollment-app.jsx',
    'resources/js/curriculum-app.jsx',
    'resources/js/dashboard-app.jsx',
],
```

Change to:
```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/enrollment-app.jsx',
    'resources/js/curriculum-app.jsx',
    'resources/js/dashboard-app.jsx',
    'resources/js/schedule-app.jsx',
],
```

- [ ] **Step 5: Verify the build**

Run: `npm run build`
Expected: exits 0. Check the output listing includes a `schedule-app` chunk (e.g. `schedule-app-XXXXXXXX.js` among the built assets), confirming Vite compiled the new entry and all three components without syntax errors.

- [ ] **Step 6: Commit**

```bash
git add resources/js/schedule/CorHeader.jsx resources/js/schedule/ScheduleQRCode.jsx resources/js/schedule-app.jsx vite.config.js
git commit -m "feat: add CorHeader/ScheduleQRCode components and schedule-app Vite entry"
```

---

### Task 3: Mount the island in `schedule.blade.php`, with a regression test

**Files:**
- Modify: `resources/views/schedule.blade.php`
- Test: `tests/Feature/ScheduleTest.php`

**Interfaces:**
- Consumes: `resources/js/schedule-app.jsx` (Task 2) via `@vite('resources/js/schedule-app.jsx')`, and the `#schedule-root` mount contract from Task 2.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ScheduleTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_schedule_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/cor');

        $response->assertOk();
        $response->assertSee('id="schedule-root"', false);
        $response->assertDontSee('Certificate of Registration');
        $response->assertDontSee('Weekly Timetable');
        $response->assertDontSee('Schedule QR Code');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/cor')->assertRedirect();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ScheduleTest`
Expected: FAIL on `assertSee('id="schedule-root"', false)` — the current Blade template has no such element yet.

- [ ] **Step 3: Replace the three content sections in `schedule.blade.php`**

Current content (`resources/views/schedule.blade.php:90-136`, the PAGE TITLE block through the SECTION 3 — QR CODE block):
```blade
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
```

Replace with:
```blade
            <div id="schedule-root"
                 data-subjects="{{ json_encode($subjects ?? []) }}"
                 data-student-name="{{ Auth::user()->name ?? 'Student' }}"
                 data-student-id="{{ Auth::user()->login_id ?? 'N/A' }}"
                 data-student-program="{{ Auth::user()->major ?? 'BSIT - Web Development' }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>
```

- [ ] **Step 4: Remove the old timetable/QR `<script>` block**

Delete the entire `<script>...</script>` block (`resources/views/schedule.blade.php:144-381` in the pre-edit file — starts at `<script>\nconst SUBJECTS = @json($subjects ?? []);` and ends at the closing `</script>` right before `@include('partials.notif-script')`). This logic now lives in `WeeklyTimetable.jsx` and `ScheduleQRCode.jsx` (Tasks 1-2).

Do NOT remove: the `<script src="{{ asset('js/qrcode.min.js') }}"></script>` line in `<head>` (line 10), the `@media print { ... }` `<style>` block in `<head>` (lines 12-19), or `@include('partials.notif-script')` after the deleted block.

- [ ] **Step 5: Add the Vite entry for the island**

Current tail of the file (after the deleted script block):
```blade
@include('partials.notif-script')
</body>
</html>
```

Replace with:
```blade
@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/schedule-app.jsx')
</body>
</html>
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --filter=ScheduleTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Run the full suite to confirm no regressions**

Run: `php artisan test`
Expected: all tests pass (186 existing + 2 new = 188).

- [ ] **Step 8: Commit**

```bash
git add resources/views/schedule.blade.php tests/Feature/ScheduleTest.php
git commit -m "feat: mount schedule React island in schedule.blade.php"
```

---

### Task 4: Build and verify

**Files:** none (verification only).

- [ ] **Step 1: Production build**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 2: Start the app**

Run `php artisan serve` (or use XAMPP/Apache as usual) — no `npm run dev` needed since Task 2's build already compiled static assets into `public/build`.

- [ ] **Step 3: HTTP-level verification**

Using the same authenticated-curl approach used for the dashboard island plan (extract CSRF token from `/login`, POST credentials with a cookie jar, GET protected pages with the same jar):

1. Authenticate as the demo regular student `2300410` / `password` (has 4 real enrolled sections per the `e4fd695` bug-fix investigation: `BSOA111`-`BSOA114`, days `M/W` and `T/Th`, times like `08:00–09:30`).
2. `GET /cor`: confirm HTTP 200, `id="schedule-root"` present, a `schedule-app-*.js` Vite asset referenced, `data-subjects` contains the 4 real subject codes, and neither "Certificate of Registration" nor "Weekly Timetable" nor "Schedule QR Code" present as static server-rendered text (confirming they now come from the React island).

- [ ] **Step 4: Manual browser verification (if a browser tool is available)**

Check: the COR header, weekly timetable grid (blocks positioned on the correct days/times, correct colors), and QR code render identically to how they looked before this change, in both light and dark mode (grid re-renders colors on toggle), with no console errors, and that Print/Download COR still produces a correct print layout. If no browser-automation tool is available in this environment, explicitly flag this step as unperformed rather than claiming it passed — consistent with how the dashboard island plan handled the same gap.

- [ ] **Step 5: Run the full test suite one more time**

Run: `php artisan test`
Expected: all tests pass (same count as after Task 3).

- [ ] **Step 6: Final commit (if manual verification surfaced any fixes)**

If Step 3 or Step 4 required any fix-up edits, commit them separately with a descriptive message before considering this plan complete.
