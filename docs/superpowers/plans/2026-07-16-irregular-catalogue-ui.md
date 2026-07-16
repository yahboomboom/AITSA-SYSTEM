# Irregular Enrollment: Year-Level Scoping + Collapsible Course Rows Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Irregular students only see subjects for their current year level and below in the enrollment catalogue, and the course list becomes collapsible per-subject instead of always showing every section.

**Architecture:** One additive `where()` clause in the existing `EnrollmentService::catalogueFor()` query (backend), and a local-state (React `useState`) collapse/expand rewrite of `IrregularPicker.jsx` (frontend). No schema change, no API response shape change.

**Tech Stack:** Laravel 12 (PHPUnit feature tests, SQLite `:memory:`), React 18 (`resources/js/enrollment/IrregularPicker.jsx`, Vite build).

## Global Constraints

- No API/schema change — `/api/enrollment/context` response shape is unchanged (spec: "No API/schema change").
- Catalogue keeps `subject.semester === current term semester` filter as-is; only adds a year-level cap (spec: "Year-level cap, not exact match").
- Ineligible subjects (already-passed / missing-prerequisite) keep today's non-interactive presentation — no expand affordance added to them (spec: "Ineligible subjects unchanged").
- Collapsed-by-default; independent multi-expand (not single-open accordion) (spec: "Independent multi-expand", "Collapsed by default").
- Auto-collapse to a plain one-line summary on pick — subject code, block label, time range only, **no icons/checkmarks/extra wording** (spec: "Auto-collapse on pick" — explicit user instruction).
- Regular-student view (`RegularView.jsx`) is out of scope — do not touch it.

---

### Task 1: Restrict catalogue to the student's current year level and below

**Files:**
- Modify: `app/Services/EnrollmentService.php:136-140` (the `Subject::where(...)` chain inside `catalogueFor()`)
- Test: `tests/Feature/Api/EnrollmentApiTest.php`

**Interfaces:**
- Consumes: existing `User::yearNumber(): int` (`app/Models/User.php:118`, maps `'1st Year'`→`1` etc., defaults to `1` if unmapped).
- Produces: no change to `catalogueFor(User $user): \Illuminate\Support\Collection` signature or the shape of each returned array (`id`, `code`, `title`, `units`, `year_level`, `semester`, `mode`, `eligible`, `reason`, `sections`) — only which rows are included changes. Later tasks (frontend) are unaffected by this task's internals, only by which subjects appear.

- [ ] **Step 1: Write the failing test**

Add this test method to `tests/Feature/Api/EnrollmentApiTest.php` (inside the `EnrollmentApiTest` class, alongside the existing `test_context_for_irregular_student_includes_catalogue`):

```php
    public function test_catalogue_excludes_subjects_above_students_year_level(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent(['year_level' => '2nd Year']);
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'ZZ999', 'status' => 'Failed']);

        $program = Program::where('code', 'BSOA')->first();
        Subject::factory()->for($program)->create(['code' => 'BSOA111', 'year_level' => 1, 'semester' => 1]);
        Subject::factory()->for($program)->create(['code' => 'BSOA211', 'year_level' => 2, 'semester' => 1]);
        Subject::factory()->for($program)->create(['code' => 'BSOA311', 'year_level' => 3, 'semester' => 1]);

        $response = $this->actingAs($user)->getJson('/api/enrollment/context')
            ->assertOk()
            ->assertJsonPath('student.type', 'irregular');

        $codes = collect($response->json('catalogue'))->pluck('code');

        $this->assertTrue($codes->contains('BSOA111'), 'lower-year subject (retake case) should be visible');
        $this->assertTrue($codes->contains('BSOA211'), "student's own year level should be visible");
        $this->assertFalse($codes->contains('BSOA311'), 'higher-year subject should not be visible');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_catalogue_excludes_subjects_above_students_year_level`
Expected: FAIL — `Failed asserting that Illuminate\Support\Collection Object (...) does not contain 'BSOA311'` (the Year 3 subject is currently included).

- [ ] **Step 3: Implement the year-level filter**

In `app/Services/EnrollmentService.php`, the current `catalogueFor()` query is:

```php
        return Subject::where('program_id', $program->id)
            ->where('semester', $term['semester'])
            ->with(['prerequisites', 'sections' => fn ($q) => $q->where('school_year', $term['school_year'])])
            ->orderBy('year_level')->orderBy('code')
            ->get()
```

Change it to:

```php
        return Subject::where('program_id', $program->id)
            ->where('semester', $term['semester'])
            ->where('year_level', '<=', $user->yearNumber())
            ->with(['prerequisites', 'sections' => fn ($q) => $q->where('school_year', $term['school_year'])])
            ->orderBy('year_level')->orderBy('code')
            ->get()
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_catalogue_excludes_subjects_above_students_year_level`
Expected: PASS

- [ ] **Step 5: Run the full existing enrollment/catalogue test suite to confirm no regressions**

Run: `php artisan test --filter=EnrollmentApiTest`
Run: `php artisan test tests/Feature/EnrollIrregularTest.php`
Expected: All PASS (the existing `test_context_for_irregular_student_includes_catalogue` test uses a 1st-year student with `makeBlockSection()` creating a Year 1 subject, so it stays within the new cap and is unaffected).

- [ ] **Step 6: Commit**

```bash
git add app/Services/EnrollmentService.php tests/Feature/Api/EnrollmentApiTest.php
git commit -m "$(cat <<'EOF'
fix: cap irregular enrollment catalogue at student's current year level

Subjects above a student's year level were showing up in the irregular
enrollment catalogue (e.g. a 1st year student seeing Year 2-4 subjects).
Adds a year_level <= user.yearNumber() filter alongside the existing
semester filter, so lower-year subjects (retakes) remain visible while
subjects the student hasn't reached yet are hidden.
EOF
)"
```

---

### Task 2: Collapsible course rows in the irregular course picker

**Files:**
- Modify: `resources/js/enrollment/IrregularPicker.jsx` (full rewrite of the render body; `overlaps()`, the `conflict` memo, and the submit button are unchanged)

**Interfaces:**
- Consumes: the `catalogue` prop shape unchanged by Task 1 (`{id, code, title, units, year_level, semester, mode, eligible, reason, sections: [{id, block_label, days, start_time, end_time, room, professor, seats_left}]}`), and the existing `picks` state shape (`{[subjectId]: {...section, code}}`).
- Produces: no change to the component's external props (`catalogue`, `submitting`, `error`, `onSubmit`) or to what `onSubmit` receives (`Object.values(picks).map((s) => s.id)`) — this task only changes internal rendering/interaction state. Nothing downstream depends on the new `open` state.

- [ ] **Step 1: Replace the full contents of `resources/js/enrollment/IrregularPicker.jsx`**

```jsx
import React, { useMemo, useState } from 'react';

function overlaps(a, b) {
    if (!a.days.some((d) => b.days.includes(d))) return false;
    return a.start_time < b.end_time && b.start_time < a.end_time;
}

export default function IrregularPicker({ catalogue, submitting, error, onSubmit }) {
    // subjectId -> section object
    const [picks, setPicks] = useState({});
    // subjectIds currently expanded
    const [open, setOpen] = useState(() => new Set());

    const conflict = useMemo(() => {
        const chosen = Object.values(picks);
        for (let i = 0; i < chosen.length; i++) {
            for (let j = i + 1; j < chosen.length; j++) {
                if (overlaps(chosen[i], chosen[j])) return [chosen[i], chosen[j]];
            }
        }
        return null;
    }, [picks]);

    const toggleOpen = (subjectId) => {
        setOpen((prev) => {
            const next = new Set(prev);
            if (next.has(subjectId)) next.delete(subjectId);
            else next.add(subjectId);
            return next;
        });
    };

    const toggle = (subject, section) => {
        const wasSelected = picks[subject.id]?.id === section.id;

        setPicks((prev) => {
            const next = { ...prev };
            if (wasSelected) delete next[subject.id];
            else next[subject.id] = { ...section, code: subject.code };
            return next;
        });

        if (!wasSelected) {
            // auto-collapse once a section is picked
            setOpen((prev) => {
                const next = new Set(prev);
                next.delete(subject.id);
                return next;
            });
        }
    };

    const years = useMemo(
        () => [...new Set(catalogue.map((s) => s.year_level))].sort(),
        [catalogue]
    );

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">Build Your Schedule</h2>
            <p className="text-xs text-slate-500 mb-4">
                Pick one section per subject. Your selection is submitted to the Department Chair for approval.
            </p>

            {years.map((year) => (
                <div key={year} className="mb-6">
                    <h3 className="text-sm font-bold text-slate-500 uppercase mb-2">Year {year}</h3>
                    {catalogue.filter((s) => s.year_level === year).map((subject) => {
                        const picked = picks[subject.id];
                        const isOpen = open.has(subject.id);
                        const showSummary = subject.eligible && picked && !isOpen;

                        return (
                            <div key={subject.id}
                                className={`border rounded-xl p-4 mb-3 ${subject.eligible ? 'border-slate-200 dark:border-slate-700' : 'border-slate-100 dark:border-slate-800 opacity-60'}`}>
                                {showSummary ? (
                                    <button type="button" onClick={() => toggleOpen(subject.id)}
                                        className="w-full text-left flex items-center justify-between flex-wrap gap-2">
                                        <span className="font-semibold text-brandNavy dark:text-slate-100">
                                            <span className="font-mono">{subject.code}</span>
                                            {' — Block '}{picked.block_label}{' · '}{picked.days.join('/')} {picked.start_time}–{picked.end_time}
                                        </span>
                                    </button>
                                ) : (
                                    <>
                                        <button type="button" disabled={!subject.eligible}
                                            onClick={() => subject.eligible && toggleOpen(subject.id)}
                                            className="w-full text-left flex items-center justify-between flex-wrap gap-2 disabled:cursor-default">
                                            <span className="font-semibold text-brandNavy dark:text-slate-100">
                                                <span className="font-mono">{subject.code}</span> — {subject.title}
                                                <span className="ml-2 text-xs text-slate-400">{subject.units} units · {subject.mode}</span>
                                            </span>
                                            {!subject.eligible && (
                                                <span className="text-xs font-semibold text-amber-600">
                                                    <i className="fa-solid fa-lock mr-1" />{subject.reason}
                                                </span>
                                            )}
                                        </button>
                                        {subject.eligible && isOpen && (
                                            <div className="flex flex-wrap gap-2 mt-3">
                                                {subject.sections.map((section) => {
                                                    const selected = picks[subject.id]?.id === section.id;
                                                    const full = section.seats_left <= 0;
                                                    return (
                                                        <button key={section.id} disabled={full && !selected}
                                                            onClick={() => toggle(subject, section)}
                                                            className={`px-3 py-2 rounded-lg border text-xs text-left
                                                                ${selected ? 'border-brandGreen bg-brandGreen/10 text-brandGreen font-semibold'
                                                                    : full ? 'border-slate-200 text-slate-400 cursor-not-allowed'
                                                                    : 'border-slate-300 dark:border-slate-600 hover:border-brandNavy'}`}>
                                                            <span className="font-semibold">Block {section.block_label}</span>{' '}
                                                            {section.days.join('/')} {section.start_time}–{section.end_time} · {section.room}
                                                            <span className="block text-[10px] opacity-70">
                                                                {full ? 'Section full' : `${section.seats_left} seats left`} · {section.professor}
                                                            </span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        );
                    })}
                </div>
            ))}

            {conflict && (
                <p className="text-sm text-red-600 mb-2">
                    <i className="fa-solid fa-triangle-exclamation mr-1" />
                    Schedule conflict: {conflict[0].code} overlaps with {conflict[1].code}.
                </p>
            )}
            {error && <p className="text-sm text-red-600 mb-2">{error}</p>}

            <button
                disabled={submitting || conflict !== null || Object.keys(picks).length === 0}
                onClick={() => onSubmit(Object.values(picks).map((s) => s.id))}
                className="px-5 py-2.5 rounded-lg bg-brandNavy text-white font-semibold text-sm hover:opacity-90 disabled:opacity-50">
                {submitting ? 'Submitting…' : `Submit ${Object.keys(picks).length} Subject(s) for Approval`}
            </button>
        </div>
    );
}
```

- [ ] **Step 2: Rebuild frontend assets**

Run: `npm run build`
Expected: Vite build completes without errors; `public/build/manifest.json` gets a new hash for `resources/js/enrollment-app.jsx` (it bundles `IrregularPicker.jsx`) and a new file appears under `public/build/assets/`.

- [ ] **Step 3: Manually verify in the running app**

This component has no existing JS test harness in this repo (React islands here are only exercised indirectly via the PHP feature tests hitting `/api/enrollment/context`, per the design spec). Verify by hand:

1. If a `php artisan serve` instance isn't already running on port 8000, start one: `php artisan serve --port=8000` (port 8000 is required — it's in Sanctum's default stateful-domain whitelist, `config/sanctum.php`; other ports will 401 on the `/api/*` calls).
2. Log in as the seeded irregular demo student: `login_id=2300411`, `password=password`.
3. Go to `/enrollment`.
4. Confirm: every eligible subject row is collapsed by default (no section buttons visible until clicked).
5. Click a subject's header row — its section buttons appear. Click another subject's header — the first subject's buttons stay visible (independent multi-expand, not single-accordion).
6. Click a section button to pick it — that subject's row immediately collapses to a plain one-line summary (subject code, block label, day/time only — confirm no icon or checkmark is present).
7. Click the collapsed summary line — it re-expands showing the section buttons again, with the previously-picked section still highlighted green.
8. Confirm ineligible subjects (already-passed / missing-prerequisite) are unchanged: still show the lock reason text, no click/expand behavior.

- [ ] **Step 4: Commit**

```bash
git add resources/js/enrollment/IrregularPicker.jsx public/build
git commit -m "$(cat <<'EOF'
feat: collapsible course rows in irregular enrollment picker

Subjects in the irregular enrollment catalogue now default to
collapsed and expand independently on click, instead of always
showing every section for every subject. Picking a section
auto-collapses that subject to a plain one-line summary; clicking
the summary re-expands it to change the pick.
EOF
)"
```

---

## Self-Review Notes

- **Spec coverage:** Backend year-level cap → Task 1. Independent multi-expand, collapsed-by-default, auto-collapse-on-pick with plain summary text, ineligible subjects unchanged → Task 2. No API/schema change → confirmed, neither task touches the API response shape. `RegularView.jsx` out of scope → confirmed untouched.
- **Type/shape consistency:** `catalogueFor()` return shape referenced in Task 1 matches the shape `IrregularPicker.jsx` already consumes in Task 2 (`code`, `year_level`, `eligible`, `reason`, `sections[].block_label/days/start_time/end_time/room/professor/seats_left`) — no field renamed or added.
- **No placeholders:** both tasks contain complete, runnable code (full test method, full replacement file) rather than descriptions.
