# Change of Matriculation — Design Spec

**Date:** 2026-07-12
**Status:** Approved by user (design dialogue), pending spec review
**Builds on:** `docs/superpowers/specs/2026-07-11-enrollment-submission-design.md` (implemented and merged)

## Goal

Let an officially enrolled student request add / drop / swap changes to their committed
subject load during an admin-controlled window. The Department Chair reviews each request
and approves (changes applied atomically) or rejects with remarks. This closes the
"change of matriculation" deliverable named in the capstone paper.

## Decisions (from design dialogue)

- **Operations:** add subject, drop subject, swap to another section of the same subject —
  combined freely in one request.
- **Eligibility:** any student (regular or irregular) whose current-term enrollment status
  is `enrolled`, only while the change window is open.
- **Approval:** Department Chair, via the existing approver dashboard pattern.
- **UI surface:** inside the existing enrollment React island — no new page.
- **Data model:** explicit delta request (header + items), not enrollment revisions and
  not final-state lists.

## Data model

New tables (Laravel migrations, following the 2026_07_11 series conventions):

```
matriculation_changes
  id
  enrollment_id   FK enrollments, cascade
  user_id         FK users, cascade      (denormalized for cheap queries/notifs)
  status          string: pending | approved | rejected   (default pending)
  remarks         string nullable        (chair's note on reject)
  timestamps

matriculation_change_items
  id
  matriculation_change_id  FK matriculation_changes, cascade
  action                   string: add | drop | swap
  section_id               FK sections          (target: added section, swap-to section,
                                                 or for drop, the section being dropped)
  replaced_section_id      FK sections nullable (swap only: the section being replaced)
  timestamps
```

Item semantics:
- `add`:  `section_id` = new section to join; `replaced_section_id` null.
- `drop`: `section_id` = section currently enrolled that will be removed; `replaced_section_id` null.
- `swap`: `section_id` = new section; `replaced_section_id` = currently enrolled section of the same subject.

Constraint enforced in service code (not DB): **at most one `pending` request per enrollment**.

New models: `MatriculationChange` (hasMany items, belongsTo enrollment/user),
`MatriculationChangeItem` (belongsTo section, replacedSection).

## Window gate

`settings` key `change_matriculation_open`, values `'1'` / `'0'`, default `'0'` (closed).
Seeded closed. Read via existing `Setting::get`.

- Students can **submit** only while open (closed → 409 "The change of matriculation window is closed.").
- The chair can approve/reject already-pending requests even after the window closes.

## Business rules

Validated on submit; **re-validated inside the approval transaction** (state may have
drifted). Failures return HTTP 409 `{"message": "..."}`; malformed payloads return
Laravel-standard 422. Rules evaluate against the **resulting schedule** — current
enrollment sections, minus drops and swap sources, plus adds and swap targets — so items
within one request are checked against each other too.

1. Student must have an `enrolled` enrollment for the current term (`Setting` school_year + semester).
2. Request must contain 1–10 items; no two items may reference the same current section,
   and no duplicate target sections.
3. **Add:** subject not already in the resulting schedule; subject not already passed
   (`student_grades.status = 'Passed'`); all prerequisites passed; target section has
   seats (`seatsLeft > 0` — counted with the same non-rejected-enrollment rule as
   enrollment); no time overlap with the resulting schedule.
4. **Drop:** `section_id` must be one of the enrollment's current sections; the resulting
   schedule must keep **at least 1 subject**.
5. **Swap:** `replaced_section_id` must be currently enrolled; `section_id` must belong to
   the **same subject**; target section has seats; no time overlap with the resulting
   schedule (the replaced section excluded).
6. One pending request per enrollment — a second submit while one is pending → 409.
   After a rejection, the student may submit a new request (rejected requests are kept
   as history, not reused).

## Service

`App\Services\MatriculationChangeService` — same architectural shape as `EnrollmentService`:

- `submit(User $student, array $items): MatriculationChange` — runs all rules, creates
  header + items, audits **"Matriculation Change Submitted"**.
- `approve(MatriculationChange $change): void` — in a DB transaction:
  `lockForUpdate` the target sections, re-run rules 3–5 against live state, then apply
  the deltas to `enrollment_subjects` (attach adds/swap targets, detach drops/swap
  sources), set status `approved`, audit **"Matriculation Change Approved"**. Rule
  failure aborts with the 409-style `EnrollmentException` message so the chair sees why.
- `reject(MatriculationChange $change, string $remarks): void` — status `rejected`,
  store remarks, audit **"Matriculation Change Rejected"**.

The enrollment row itself is untouched (stays `enrolled`); the COR (`showCor`) and the
enrollment context automatically reflect the new sections because they read
`enrollment->sections` live.

## API

Same Sanctum stateful-cookie conventions as the enrollment API (`routes/api.php`):

- `GET /api/matriculation/context` — `auth:sanctum`, `role:student`. Returns:
  `window_open` (bool), `enrollment` (id, status, current sections in the enrollment
  payload shape), `request` (latest matriculation change with items + status + remarks,
  or null), and `catalogue` (the same eligibility-annotated structure the irregular
  picker consumes, for building adds/swaps; null when no enrolled enrollment).
- `POST /api/matriculation` — `role:student`. Body
  `{ "items": [ { "action": "add|drop|swap", "section_id": int, "replaced_section_id": int|null } ] }`
  → 201 with the created request | 409 rule failure | 422 validation.

Chair actions are **web routes** (Blade dashboard, like enrollment approval), `role:chair`:

- `POST /approver/matriculation/{change}/approve`
- `POST /approver/matriculation/{change}/reject` — `remarks` required.

Admin window toggle, `role:admin`:

- `POST /api/admin/settings/change-matriculation` — `{ "open": bool }`, flips the setting,
  audits **"Change Matriculation Window Opened/Closed"**.
- `GET /api/admin/programs` response gains `change_matriculation_open` (bool) at the top
  level so the curriculum editor can render the toggle without a new GET endpoint.

## Student UI (existing enrollment island)

In `resources/js/enrollment-app.jsx` and new components under `resources/js/matriculation/`:

- When context shows status `enrolled` **and** `window_open`, the StatusCard shows a
  **"Request Change of Matriculation"** button.
- Clicking it opens `ChangeBuilder` (new component) in place of the status card:
  - Lists current sections, each with **Drop** and **Swap** controls (swap expands the
    subject's other sections as selectable buttons, styled like `IrregularPicker`).
  - An **Add subject** area reusing the catalogue rendering (eligible subjects with
    section buttons; ineligible shown locked with reason).
  - A running summary of requested items with **live client-side conflict detection**
    (same `overlaps` logic as `IrregularPicker`) against the resulting schedule.
  - Submit posts to `POST /api/matriculation`; 409 messages render inline.
- If a request exists: pending → read-only summary card ("Awaiting Department Chair
  Approval"); rejected → remarks shown + "File New Request" button (window permitting);
  approved → normal enrolled view (updated sections already visible).

The matriculation context is fetched from the enrollment island alongside the existing
enrollment context call.

## Chair UI

On the existing approver dashboard Blade (`resources/views/approver/dashboard.blade.php`):
a second queue card, "Change of Matriculation Requests", listing pending requests. Each
shows student name/ID/program and one line per item —
`ADD BSOA112 (Block A, M/W 10:00–11:30)`, `DROP …`, `SWAP BSOA113 Block A → Block B (…)` —
with Approve and Reject-with-remarks forms identical in style to the enrollment queue.
Approval failures (drifted state) flash the 409 message.

## Admin UI

In the React curriculum editor header: a toggle button showing current window state
("Change of Matriculation: OPEN/CLOSED — click to toggle") wired to the new admin
endpoint.

## Notifications (notif bell)

- Student branch: latest matriculation change → pending ("Change request under review"),
  approved ("Change of matriculation approved — schedule updated"), rejected (remarks
  excerpt, "Action needed").
- Chair branch: pending matriculation request count, like the enrollment pending count.

## Testing

PHPUnit, SQLite `:memory:`, `RefreshDatabase`, TDD per task:

- **Service tests:** window closed blocks submit; add with unmet prereq / passed subject /
  full section / conflict rejected; drop of non-enrolled section rejected; drop below one
  subject rejected; swap to different subject rejected; combined request validated as a
  whole (add conflicting with swap target rejected); duplicate pending request rejected;
  approve applies deltas and audits; approve re-validation fails when a seat disappeared;
  reject stores remarks.
- **API tests:** context shape for enrolled student with/without request; POST happy path
  201; role guards (student-only, admin-only); 409 message pass-through.
- **Web tests:** chair approve/reject routes (role:chair), reject requires remarks.
- **Existing suites must stay green** (41 tests).

## Out of scope

- Fee/refund computation for dropped or added subjects.
- Printable change-of-matriculation form (PDF).
- Email notifications.
- Registrar involvement in the approval chain.
- Editing a pending request (student cancels nothing; chair rejects, student re-files).
