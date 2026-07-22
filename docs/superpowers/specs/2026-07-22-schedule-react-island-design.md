# Student Schedule (COR) → React Island — Design

**Date:** 2026-07-22
**Status:** Approved

## Overview

The paper's remaining gap is a full React 18 SPA; today three islands are mounted
(`enrollment.blade.php`, `admin/curriculum.blade.php`, `dashboard.blade.php`), everything
else — including `schedule.blade.php` (the student's Certificate of Registration /
weekly timetable page, served at `/cor`) — is plain Blade. This spec converts
`schedule.blade.php`'s content area to a fourth island, `schedule-app.jsx`.

Unlike the dashboard port, this page is **not purely static**: it's driven by real
per-student data (`$subjects`, computed server-side in `AuthController::showCor()` from
the student's enrolled sections) and has real interactive JS logic — a hand-rolled
weekly-timetable grid renderer (day/time parsing, absolute-positioned blocks), a QR
code widget, `window.print()`, and a dark-mode re-render hook. The port re-implements
this logic as React components rather than copying markup verbatim.

**Prerequisite fix (already done, `e4fd695`):** while scoping this work, a real bug was
found and fixed on `master`: `parseTimeRange()`'s split regex didn't match the en-dash
character `AuthController::showCor()` actually uses to join start/end times, so the
timetable silently rendered with zero blocks for every student regardless of enrollment
status. This spec is written against the fixed behavior.

## Decisions made during brainstorming

- **Faithful logic port, not a verbatim DOM-manipulation wrap.** The existing
  `parseDays`/`parseTimeRange`/`renderTimetable` algorithm is re-implemented as a React
  component driven by JSX + state, rather than calling the old imperative
  `renderTimetable()` function from inside a `useEffect` against a ref. Same visual
  output, no DOM-manipulation code living inside a React component.
- **Keep the existing global `qrcode.min.js`**, not a new npm QR package. It's already
  vetted in this codebase (`public/js/qrcode.min.js`, loaded via `<script src>` in
  `<head>`); the React component calls `new QRCode(ref.current, {...})` from a
  `useEffect`. No new dependency for a page that already has a working solution.
- **Dark-mode re-render via `MutationObserver`** on `document.documentElement`'s `class`
  attribute, not the current pattern of monkey-patching `window.toggleTheme`. Clean,
  self-contained in the component, doesn't touch shared `resources/js/app.js` at all
  (that function is used by every other page).
- **Three components**, mirroring the three visual sections — matches the dashboard
  island's "lightly componentized" convention:
  - `CorHeader.jsx` — title, AY/semester text, "Enrolled" badge, student-no/program block.
  - `WeeklyTimetable.jsx` — the grid itself.
  - `ScheduleQRCode.jsx` — QR card + its own "Print / Download COR" button.
- **Data handoff via `data-*` attributes** on the `#schedule-root` div (JSON-encoded),
  not a `window.__SCHEDULE_DATA__` global. Keeps data scoped to the mount point. No new
  API endpoint — same "purely mechanical, no new backend" rule the dashboard plan used.

## New files

- **`resources/js/schedule/CorHeader.jsx`** — props: `student` (name, login_id, major).
  Renders the "Certificate of Registration" title, AY/semester text, "Enrolled" badge,
  and the student-no/program block. Wrapped in its own `.print-area` div (preserves the
  original's separate print page-break boundary).
- **`resources/js/schedule/WeeklyTimetable.jsx`** — props: `subjects` (array). Ports
  `parseDays`, `parseTimePart`, `parseTimeRange`, and the grid-layout logic
  (`COLOR_MAP`, slot rows, subject blocks) from the current `<script>` block into JSX +
  `useState`/`useMemo`. Tracks `isDark` via a `MutationObserver` on
  `document.documentElement` (attached in `useEffect`, disconnected on unmount).
  Renders "No enrolled subjects yet — complete your enrollment first." when
  `subjects.length === 0` (this check moves here from Blade's `@if`). Own
  `.print-area` wrapper.
- **`resources/js/schedule/ScheduleQRCode.jsx`** — props: `subjects`, `studentName`,
  `studentId`, `studentProgram`. Builds the same QR text payload as today, calls
  `window.QRCode` against a `ref` in `useEffect`. Includes the "Print / Download COR"
  button (`onClick={() => window.print()}`). Own `.print-area` wrapper.
- **`resources/js/schedule-app.jsx`** — entry point. Reads `data-subjects` /
  `data-student-name` / `data-student-id` / `data-student-program` off `#schedule-root`
  via `JSON.parse` (wrapped so a parse failure falls back to `[]` instead of crashing),
  renders the three components, mounts via `createRoot(el).render(...)` — same
  mount-guard shape as the other three islands.

## Modified files

- **`vite.config.js`** — add `'resources/js/schedule-app.jsx'` to the `input` array.
- **`resources/views/schedule.blade.php`**:
  - Replace the page-title block, "Weekly Timetable" card, and QR card (the three
    `.print-area` sections) with:
    ```blade
    <div id="schedule-root"
         data-subjects="{{ json_encode($subjects) }}"
         data-student-name="{{ Auth::user()->name ?? 'Student' }}"
         data-student-id="{{ Auth::user()->login_id ?? 'N/A' }}"
         data-student-program="{{ Auth::user()->major ?? 'BSIT - Web Development' }}">
        <p class="text-sm text-slate-500">Loading…</p>
    </div>
    ```
  - Remove the entire `<script>` block containing `parseDays`/`parseTimeRange`/
    `renderTimetable`/`renderQRCode`/the `DOMContentLoaded` init — this logic moves into
    the React components.
  - Add near the bottom of `<body>`, after `@include('partials.notif-script')`:
    ```blade
    @viteReactRefresh
    @vite('resources/js/schedule-app.jsx')
    ```
  - **Untouched:** sidebar, `<header>` (including its own "Print COR" button — plain
    `onclick="window.print()"`, unaffected by React), the `<script src="qrcode.min.js">`
    global include (must stay loaded before the Vite entry so `window.QRCode` exists at
    mount time), the `@media print` `<style>` block, `partials.notif-script`.
- **No change to `AuthController::showCor()` or `routes/web.php`** — same `$subjects`
  shape, same route.

## Testing / Verification

- New `tests/Feature/ScheduleTest.php`: TDD red→green, same pattern as
  `DashboardTest.php`. Assert `id="schedule-root"` present; assert old static markers
  (`'Certificate of Registration'`, `'Weekly Timetable'`) are gone from server-rendered
  HTML; assert guest redirect.
- No JS unit-test runner in this repo (standing project fact) — verification is
  `npm run build` succeeding, the new Feature test, and an authenticated-curl
  HTTP-level check against `/cor` (same method used for the dashboard plan): confirm
  `id="schedule-root"`, the new Vite JS asset referenced, `data-subjects` containing the
  real enrolled-section data, and zero leftover static timetable/QR markup.
- Full suite run (`php artisan test`) at the end to confirm no regressions.
- Manual/visual browser verification (grid renders correctly, QR code scans, print
  layout, dark-mode re-render) is a known gap — no browser-automation tool is connected
  in this environment. Flagged explicitly, not silently skipped.

## Out of scope (explicitly)

- Any change to `AuthController::showCor()`, the `Enrollment`/`Section` models, or
  adding a `/api/schedule` endpoint.
- Any change to `qrcode.min.js` itself.
- The chair-approval status-visibility question raised during triage (irregular
  students' COR staying hidden until chair approval) — confirmed as intentional
  existing behavior, not part of this port.
- Converting any other Blade page to a React island — this spec is schedule/COR-only.
- Any change to `layouts/app.blade.php` (stays unused/dead).
