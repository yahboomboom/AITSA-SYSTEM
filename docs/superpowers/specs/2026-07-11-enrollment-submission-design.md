# Real Enrollment Submission — Design

**Date:** 2026-07-11
**Status:** Approved by user (brainstorming session)
**Context:** The capstone paper ("Enrollment with Clearance System of AITSA") promises a working enrollment workflow, but the current system has only a read-only enrollment page with a hardcoded subject catalogue and no way to commit an enrollment. This design closes that gap. The paper's tech stack (React + Laravel + cloud DB) is locked in by the adviser/panel, so new UI is built in React.

## Decisions made during brainstorming

| Question | Decision |
|---|---|
| First gap to close | Real enrollment submission (DB-backed) |
| Workflow | Hybrid: regular students auto-commit their block schedule; irregular students pick sections, then Dept. Chair approves |
| Data model | Subjects with multiple sections, each with schedule/room/professor/capacity |
| Programs in catalogue | Live AITSA offerings only: TESDA NC III (Bookkeeping, Events Management, Food & Beverages), Associate (Business Office Management, Food Service Management), Bachelor (BS Office Administration, Bachelor in Technical-Vocational Teacher Education) |
| SHS | Excluded. Existing SHS screens/tables and paper references need updating later (follow-up) |
| TESDA enrollment | Out of scope — admin registers TESDA students manually; `is_enrollable = false` |
| Curriculum Editor | Rebuilt as real CRUD over the new tables |
| Regular block assignment | Automatic by program + year: first block label with open seats in every section |
| Frontend approach | "B+": React 18 islands on the existing Vite setup + Laravel JSON endpoints with Sanctum SPA cookie auth (React is locked in by the paper) |

## Architecture

Single Laravel 12 application; no separate frontend repo.

1. **React 18 via existing Vite.** Add `react`, `react-dom`, `@vitejs/plugin-react` next to the Tailwind plugin in `vite.config.js`. New features are React islands: the Blade page keeps the shared layout (sidebar, notif bell, theme toggle) and exposes a mount div (e.g., `<div id="enrollment-app">`). This phase converts two pages to React: the student enrollment page and the admin Curriculum Editor. All other pages remain Blade and migrate later, one at a time.
2. **JSON endpoints + Sanctum SPA mode.** Install `laravel/sanctum`; same-origin cookie/CSRF auth (no tokens). React calls endpoints via Axios (already a dependency). Existing `EnsureUserHasRole` middleware guards them exactly like the Blade routes.

Untouched this phase: auth pages, staff dashboards (except the chair's approval queue addition), clearance, audit, payments.

## Data model

New migrations:

- **`programs`** — `code`, `name`, `level` (`bachelor`|`associate`|`tesda`), `years` (4/2/null), `is_enrollable` (false for TESDA).
- **`subjects`** — `program_id`, `code`, `title`, `units`, `year_level`, `semester` (1|2), `mode` (F2F|online).
- **`subject_prerequisites`** — pivot `subject_id` → `prerequisite_id`. Checked against existing `student_grades`.
- **`sections`** — `subject_id`, `block_label`, `days`, `start_time`, `end_time`, `room`, `professor`, `capacity`, `school_year`. A "block schedule" = all sections sharing program + year + semester + `block_label`; no separate blocks table.
- **`enrollments`** — `user_id`, `school_year`, `semester`, `type` (regular|irregular), `status` (`pending` → `enrolled`|`rejected`), `block_label` (regular only), `remarks` (chair rejection reason), timestamps. Unique index: one active enrollment per student per term.
- **`enrollment_subjects`** — `enrollment_id` → `section_id`. Seat usage per section = count of these rows on non-rejected enrollments (no counter column).
- **`settings`** — key-value store; registrar controls current `school_year` and `semester` (the paper's "academic term configuration").

Student's existing `year_level`, `major`, and regular/irregular flag on `users` select the visible catalogue.

## Enrollment flow

**Gate (both types):** API refuses unless clearance is fully approved (same check the notif bell uses) and no active enrollment exists for the current term.

**Regular:** `GET /api/enrollment/block` computes the student's block (program + year + current semester, first block label with open seats in every section) and returns the schedule for review. **Confirm Enrollment** → `POST /api/enrollment` → `status = enrolled` immediately, sections pinned. (Paper Figure 27.)

**Irregular:** React picker shows the program catalogue with each subject marked eligible / prereq-missing / already-passed (from `student_grades` + `subject_prerequisites`). Student picks one section per subject; live conflict flagging (port of existing `conflictsWith` logic). Submit → `POST /api/enrollment` with `status = pending`. Dept. Chair (approver role) gets a queue on their dashboard: view proposed schedule → Approve (→ `enrolled`) or Reject with remarks (→ student sees remarks, can resubmit). (Paper Figures 16/28.)

**Server-side revalidation:** inside one DB transaction with row locks (`lockForUpdate`), re-check clearance, prerequisites, conflicts, and seat capacity. A seat race yields a clean "section just filled up" 409 for the loser — never an oversold section. Every commit/approval writes an `AuditLog` entry (existing pattern).

**Downstream:** `schedule.blade.php` (QR schedule) and the COR read the student's real enrolled sections instead of placeholder data. Notification bell gains "Enrollment submitted / approved / rejected" states.

## Curriculum Editor (admin)

React island backed by `GET/POST/PUT/DELETE /api/admin/programs|subjects|sections`, admin-role guarded. Admin picks a program → edits subjects per year/semester → expands a subject to manage sections (schedule, room, professor, capacity, block label) and prerequisites (dropdown of the program's other subjects). Save & Publish performs real writes; every change is audit-logged. Guard: subjects/sections with existing enrollments cannot be deleted, only edited (API returns a clear error).

## Error handling

- Validation failures: structured 422 JSON `{message, errors}`, rendered inline by React forms.
- Business-rule failures (clearance incomplete, section full, prereq missing, duplicate enrollment): 409 with a human-readable reason shown to the student.
- Unexpected errors land in Laravel's log; nothing fails silently.

## Testing

PHPUnit feature tests for: enrollment blocked without clearance; regular auto-block commit; irregular prereq rejection; schedule-conflict rejection; seat-capacity race (two simultaneous submissions, one seat); chair approve/reject; curriculum CRUD permissions. Seeders create the 4 enrollable programs with draft curricula (subject lists to be corrected by the team) so `php artisan migrate:fresh --seed` yields a fully demoable system.

## Non-goals (this phase)

- TESDA self-service enrollment (admin-registered instead)
- Anything SHS (screens/tables/paper references need separate cleanup)
- Payments / DocuSign, change of matriculation, email sending
- Migrating existing Blade pages to React beyond the two named above

## Follow-ups (agreed, separate projects)

1. **UI redesign** — current UI reads as generic/AI-made. Restyle pages as they migrate to React, using a palette calibrated to the school logo (`public/assets/aitsa_banner.jpg`): deep teal-blue (~#1D4E63), seal green (~#2E7D43), white. Existing `brandNavy`/`brandGreen` tokens are in the right family but should be matched to the logo.
2. Expand clearance to all departments with remarks; sandbox payment gateway + DocuSign; DB-backed notifications + email; change of matriculation; documents module; report export — per the earlier gap analysis.
3. Paper cleanup: missing references [28]/[31]/[37]/[38]/[42], contradictory respondent counts (80 vs 10 students, 102 vs 118 total), duplicate figure numbers, SHS/program list mismatch with the live offering, encoding artifacts.
