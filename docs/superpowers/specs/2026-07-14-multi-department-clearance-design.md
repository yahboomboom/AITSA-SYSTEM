# Multi-Department Clearance — Design

**Date:** 2026-07-14
**Status:** Approved (Approach A — hybrid: keep existing chair/cashier/registrar, add dynamic departments alongside)

## Overview

Closes the capstone paper's promise that clearance routes to "all concerned departments,
including but not limited to the Finance Office, Registrar's Office, Library, and Academic
Departments" — today the schema only has Chair/Cashier/Registrar wired up; `library_status`
and `clinic_status` are dead columns, always `Approved`, never read by any business logic
(the student page shows a hardcoded "Library — No pending borrowed items on record" line
unconditionally). This closes that gap by letting the admin add arbitrary departments at
runtime, each with its own officer account and per-student approve/hold-with-remarks
workflow that gates enrollment exactly like the existing three offices do.

## Decisions made during brainstorming

- **Department list is admin-managed**, not hardcoded — a `departments` table the admin adds
  rows to, no code change needed to add an office. Matches the paper's "including but not
  limited to" wording and the client not having settled which offices (no Library/Clinic at
  this client) — the client will supply real names later.
- **Ship empty.** No departments are seeded. The dynamic mechanism is demoed by adding a
  department live.
- **Approach A (hybrid), not a full rewrite.** `chair_status`/`cashier_status`/
  `registrar_status` stay as columns on `clearances` with their existing routes/views/tests
  untouched in shape. New departments live in new tables (`departments`, `clearance_items`).
  Lower risk than normalizing everything into one signoffs table, and the original three
  offices have enough special-cased behavior (admission → registrar handoff, cashier
  auto-clearance from payments) that folding them in isn't worth the churn right now.
- **New departments block enrollment**, same as chair/cashier/registrar — the paper explicitly
  promises restricting enrollment for "pending or unresolved clearance obligations," full
  stop, not just the original three.
- **New departments start Pending for students created after the department exists** (lazy
  row creation — see Data model). Nothing retroactively created for existing students when a
  department is added mid-term.
- **Remarks everywhere.** `clearances` gains a `remarks` column so chair/cashier/registrar can
  also leave a note on hold, matching the paper's "approve or disapprove ... with remarks" for
  all offices, not just the new dynamic ones. This means `chair_status`/`cashier_status`/
  `registrar_status` gain a third possible value, `Hold`, alongside their existing
  `Pending`/`Approved` (the enrollment gate's `=== 'Approved'` checks are unaffected — `Hold`
  fails the check exactly like `Pending` does).
- The two dead columns `library_status` / `clinic_status` are **dropped** — nothing reads them
  meaningfully (seeders/tests set them to a constant `'Approved'`; the student page's "Library"
  line is static text that never checks them).

## Data model

Two new migrations, one alteration:

1. **`departments`**: `id`, `name` (string 100, unique), `code` (string 50, unique slug,
   auto-generated from `name`), `is_active` (boolean, default true), timestamps.
2. **`clearance_items`**: `id`, `clearance_id` (FK → `clearances`, cascade delete),
   `department_id` (FK → `departments`, cascade delete), `status` (string, default
   `Pending`, values `Pending`/`Approved`/`Hold`), `remarks` (nullable string 500),
   `signed_by` (nullable FK → `users`, `nullOnDelete`), `signed_at` (nullable timestamp),
   timestamps. Unique on `(clearance_id, department_id)`.
3. **Alter `clearances`**: add nullable `remarks` (string 500). Drop `library_status` and
   `clinic_status`.

**Users**: add nullable `department_id` (FK → `departments`, `nullOnDelete`) to `users`. An
officer account (`role = 'department_officer'`) belongs to exactly one department — same
one-role-one-office shape as chair/cashier/registrar, just data-driven instead of a fixed
role name.

**Lazy row creation.** `clearance_items` rows are not created when a department is added.
They're created the same places `clearances` rows themselves are created/seeded (admission
approval, admin-created student account, enrollment seeding path) by inserting one row per
currently-`is_active` department. A student whose `clearances` row predates a department
never gets a `clearance_items` row for it and is unaffected by that department's gate —
consistent with "ship empty, add live" and "no retroactive rows."

**Models:**
- `Department` (new): `$fillable = ['name', 'code', 'is_active']`, `officers()` hasMany
  User, `clearanceItems()` hasMany ClearanceItem.
- `ClearanceItem` (new): `$fillable = ['clearance_id', 'department_id', 'status', 'remarks',
  'signed_by', 'signed_at']`, `department()` belongsTo, `clearance()` belongsTo,
  `signedBy()` belongsTo User.
- `Clearance`: add `'remarks'` to `$fillable`, remove `'library_status'`/`'clinic_status'`,
  add `items()` hasMany ClearanceItem, helper `allItemsApproved(): bool`.
- `User`: add `department()` belongsTo Department (nullable).

## Officer & admin UX

**Admin — Departments panel** (new tab in the existing admin curriculum/settings area):
- List departments (name, active toggle, officer count).
- Add department: name only (code auto-slugged, checked unique).
- Create officer account for a department: reuses the existing admin "create staff account"
  flow (name, login ID, password defaults to the existing `password123` dev convention,
  role forced to `department_officer` server-side, `department_id` required and validated
  active).
- No delete endpoint in v1 (consistent with programs/faculty/rooms) — deactivate via
  `is_active` instead; inactive departments are excluded from lazy row creation for newly
  created clearances but existing `clearance_items` rows for them are untouched (so an
  in-flight approval isn't silently erased).

**Officer dashboard** (new): route `/department/dashboard`, name `department.dashboard`,
middleware `role:department_officer`, Blade view `resources/views/department/dashboard.blade.php`.
Lists students with a `clearance_items` row for the officer's own department
(`auth()->user()->department_id`), each with Approve / Hold-with-remarks actions —
same interaction pattern as the chair's `/approver/sign/{id}`. Hold requires a `remarks`
string (mirrors the existing `approver.matriculation.reject` / document-reject validation:
`required|string|max:500`). Approve clears any prior remarks.

`AuthController::handleRoleRedirection` gains `case 'department_officer':
'department.dashboard'`.

**Hold on the existing three offices.** Today `approver.sign` / `cashier.approve` /
`registrar.sign` only support Approve — there is no reject/hold route for a clearance status
(unlike enrollments, matriculation changes, and documents, which already have a
reject-with-remarks route). This design adds one new POST route per office, following the
existing `{resource}.hold` naming already used for holds elsewhere in the app where
applicable: `approver.hold`, `cashier.hold`, `registrar.hold` — each validates
`remarks: required|string|max:500`, sets that office's status column to `Hold`, writes
`clearances.remarks`, audits `'Clearance Held'`, and notifies the student. Their three
existing dashboards (`approver/dashboard.blade.php`, `cashier/dashboard.blade.php` or
`accounts.blade.php`, `registrar/dashboard.blade.php`) each get a small "Hold" button next to
the existing Approve button, opening a one-field remarks prompt (same pattern as the
document-reject remarks modal already on the registrar dashboard).

## Student clearance page

`resources/views/clearance.blade.php` today hardcodes four rows (Accounting/Library/
Registrar/Department Head) and computes `$isCleared` from the three status columns only.
Changes:

- Keep the three existing cards (Cashier/Registrar/Chair) with their current icons and
  copy — no change to their look.
- Remove the static "Library — No pending borrowed items on record" row.
- Add a loop over the student's `clearance_items` (eager-loaded with `department`),
  rendering one card per row: department name, status icon (Approved = green check,
  Pending = gold x, Hold = red x), and the remark text when status is `Hold`.
- `$isCleared` becomes `$clearance->admission_status === 'Approved' && $clearance->
  chair_status === 'Approved' && $clearance->cashier_status === 'Approved' && $clearance->
  registrar_status === 'Approved' && $clearance->allItemsApproved()`.
- Cashier/Registrar/Chair cards also show `$clearance->remarks` when present (single shared
  field is fine — only one of the three offices holds at a time in practice, and this is a
  read-only display, not a per-office remarks store; if that becomes a real limitation later
  it can be split, but YAGNI for now given the offices already run sequentially via the
  admission→registrar→chair/cashier flow).

## Enrollment gate

`EnrollmentService::clearanceComplete(User $user): bool` extends its existing
chair/cashier/registrar check with `&& $clearance->allItemsApproved()`
(`allItemsApproved()` returns true — vacuously — when the student has zero `clearance_items`
rows, so students whose clearance predates any department are unaffected). No other change
to the enrollment flow; the existing block/restriction UI already reads this one boolean.

## Notifications & audit

Officer Approve/Hold actions call `AuditLog::record('Clearance Signed', ...)` (Approve) or a
new `'Clearance Held'` action (Hold, message includes the remark), matching the existing
chair/registrar audit pattern, and push a notif-bell entry to the student. Held remarks are
free text and go through the existing `escNotif()` escaping used for chair remarks
(stored-XSS prevention already established in the change-of-matriculation work).

`approver.hold`/`cashier.hold`/`registrar.hold` (see Officer & admin UX) follow the same
pattern: audit `'Clearance Held'` with the remark, notify the student, write
`clearances.remarks`. Approve on any of the three clears `clearances.remarks`.

## Testing

Feature tests following existing patterns (PHPUnit, RefreshDatabase, SQLite `:memory:`):

- Admin can create a department (name required, unique); duplicate name 422; non-admin 403.
- Admin creates a department-officer account scoped to a department; login_id uniqueness;
  role forced server-side; department must be active.
- Officer dashboard shows only `clearance_items` rows for their own department; other
  officers' students are invisible (403/filtered).
- Officer Approve sets status Approved, clears remarks, writes audit log, notifies student.
- Officer Hold requires remarks (422 without), sets status Hold, writes audit log, notifies
  student.
- Student clearance page renders one card per active-at-creation-time department with
  correct status/remarks; a student with zero `clearance_items` rows sees no dynamic cards
  and isn't blocked by them.
- `EnrollmentService::clearanceComplete()`: blocked while any `clearance_items` row is
  Pending/Hold; unblocked once all rows Approved; unaffected (true) when zero rows exist;
  existing chair/cashier/registrar-only cases still pass.
- Department added mid-term does not retroactively create rows for existing students'
  clearances; new clearances created afterward get a row for every active department.
- `approver.hold`/`cashier.hold`/`registrar.hold`: requires remarks (422 without), sets that
  office's status to `Hold`, persists `clearances.remarks`, visible on student page, blocks
  enrollment (status isn't `Approved`); the office's Approve route clears `remarks`.
- Migration: `library_status`/`clinic_status` columns dropped without breaking existing
  seeders/tests that reference them (those seeder/test lines are updated as part of this
  work, not left dangling).

## Out of scope (explicitly)

- Delete endpoint for departments (deactivate only).
- Splitting `clearances.remarks` into a per-office remarks store.
- Any change to the admission→registrar handoff or cashier auto-clearance-from-payment logic.
- Multi-officer routing / escalation within a single department.
- Retroactively backfilling `clearance_items` for students whose clearance predates a
  department.
