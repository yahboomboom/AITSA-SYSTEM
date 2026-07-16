# Irregular Enrollment: Year-Level Scoping + Collapsible Course Rows — Design

**Date:** 2026-07-16
**Status:** Approved

## Overview

Two related fixes to the irregular-student enrollment catalogue:

1. `EnrollmentService::catalogueFor()` currently filters subjects by the current term's
   semester only, not by year level, so an irregular 1st year student sees Year 1–4 subjects
   for that semester — including years they haven't reached yet. This restricts the catalogue
   to the student's current year level and below.
2. `IrregularPicker.jsx` renders every eligible subject's sections as an always-visible row of
   buttons. With multiple years' worth of subjects now legitimately visible (for retakes),
   this list gets long. Subjects become collapsible: click to reveal sections, pick a section
   to auto-collapse to a one-line summary.

## Decisions made during brainstorming

- **Year-level cap, not exact match.** Catalogue shows `subject.year_level <= user.yearNumber()`
  (existing `User::yearNumber()` helper), keeping the existing `semester === current term
  semester` filter unchanged. This allows retaking a lower-year subject (e.g. a 2nd year
  student retaking a failed 1st-year/1st-sem subject once that semester slot comes around
  again) while hiding anything from a year the student hasn't reached — confirmed as the
  intended behavior during brainstorming.
- **No change to eligibility logic.** `eligible`/`reason` (already-passed / missing
  prerequisite) computation is untouched — only which subjects enter the collection changes.
- **Independent multi-expand**, not a single-open accordion. Each subject's expand/collapse
  state is tracked independently (`Set` of open subject IDs in local component state) so a
  student can compare multiple subjects' schedules side by side.
- **Collapsed by default.** Every eligible subject starts collapsed; clicking its header row
  toggles its sections open/closed.
- **Auto-collapse on pick.** Once a section is picked for a subject, that subject's row
  collapses automatically to a plain one-line summary: subject code, block label, and time
  range only — no icons, checkmarks, or extra wording (explicit user instruction: keep it
  plain text). Clicking the summary line re-expands the row so the pick can be changed.
- **Ineligible subjects unchanged.** Already-passed / missing-prerequisite subjects keep
  today's non-interactive presentation (reason text, no sections shown) — nothing to expand,
  so no toggle affordance is added to them.
- **No API/schema change.** The `/api/enrollment/context` response shape is unchanged; this is
  a query filter added server-side and a client-side interaction change only.

## Backend change

`app/Services/EnrollmentService.php`, `catalogueFor()`: add
`->where('year_level', '<=', $user->yearNumber())` to the existing `Subject::where('program_id', ...)
->where('semester', $term['semester'])` query chain, before `->with([...])->orderBy(...)->get()`.

## Frontend change

`resources/js/enrollment/IrregularPicker.jsx`:

- Add `const [open, setOpen] = useState(new Set())` (subject IDs currently expanded) and a
  `toggleOpen(subjectId)` helper that adds/removes from the set (new `Set` each time, since
  React state must change identity to re-render).
- For each eligible subject: if `picks[subject.id]` is set (a section has been chosen) **and**
  the subject is not in `open`, render the collapsed one-line summary (clickable, calls
  `toggleOpen`) instead of the section button row. Otherwise render the header (clickable,
  toggles `open`) followed by the section buttons only when the subject's ID is in `open`.
- Picking a section (`toggle()`) additionally removes the subject from `open` so it collapses
  immediately on pick, matching "auto-collapse to summary."
- Re-clicking the collapsed summary adds the subject back to `open`, revealing the section
  buttons again (with the current pick still highlighted, same as today) so the choice can be
  changed.
- Ineligible subjects: no change — still always render the reason text, never added to `open`.
- Conflict detection, submit button, and the by-year grouping headers (`Year 1`, `Year 2`, ...)
  are unchanged.

## Testing

- Feature test: `EnrollmentService::catalogueFor()` for a 2nd-year irregular student does not
  include any Year 3/4 subjects for the current semester, and does include a Year 1 subject
  scheduled for the current semester (retake case).
- Existing catalogue tests (eligibility / prerequisite / seats-left) continue to pass
  unchanged — only the year-level filter is additive.
- No new JS test infra exists in this repo for the enrollment island (React components are
  currently only exercised via the PHP feature tests hitting `/api/enrollment/context`); the
  collapse/expand interaction itself is manual-verified via `/run`, consistent with how
  `RegularView`/`StatusCard` have been handled so far.

## Out of scope (explicitly)

- Any change to `RegularView.jsx` (regular-student block view) — not mentioned as a concern.
- Any change to eligibility/prerequisite computation.
- Persisting expand/collapse UI state across page reloads.
- A "compare schedules" view beyond what independent multi-expand already provides.
