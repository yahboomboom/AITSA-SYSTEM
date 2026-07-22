# Student Registry (Admin) — Design

**Date:** 2026-07-23
**Status:** Approved, ready for implementation plan
**Closes gap:** Finding #2 in `docs/superpowers/specs/2026-07-23-capstone-paper-gap-analysis.md`
— paper's Fig 62 describes a searchable list of all students (ID, name, program, year
level, education level) with a delete action. No such admin page currently exists.

## Scope

This is a standalone, self-contained gap-close: a new admin page to list, search, filter,
and (soft-)delete student accounts. It does **not** include:
- Editing an existing student's details (out of scope, not in Fig 62)
- A "view deleted / restore" UI (soft-deleted students just disappear from the list;
  restoring one is a rare manual DB action, not a feature)
- Any staff/admin account management or permissions (that's a separate, later spec —
  see "Related work" below)

Implementation style: plain server-rendered Blade, matching the sibling admin pages
(`admin.students.create`, `admin.departments`, `admin.audit`, `admin.reports`) rather than
a React island. User has flagged this page for a future React-island migration once the
rest of the admin panel gets there — not in this pass.

## Data model

- Add a nullable `deleted_at` timestamp column to `users` via a new migration.
- Add the `Illuminate\Database\Eloquent\SoftDeletes` trait to `App\Models\User`.
- No new tables.

**Why soft delete, not hard delete:** all of `clearances`, `enrollments`,
`matriculation_changes`, `document_submissions`, `transaction_ledgers`, and
`student_grades` already `cascadeOnDelete()` on `users.id`. A hard delete would
permanently and irreversibly wipe a student's entire clearance/enrollment/payment/grade
history with no undo — too risky for a single misclick, especially during a live demo.
Soft delete keeps all history intact in the DB; the student just disappears from the
registry and can no longer log in.

**Login is blocked automatically:** `AuthController::login()` looks up users via
`User::where('login_id', $login)->orWhere('email', $login)->first()` — an Eloquent query.
Once `User` uses `SoftDeletes`, Eloquent's global scope excludes soft-deleted rows from
this query automatically. No extra code needed to block login on delete.

## Routes / Controller

Both routes live inside the existing `Route::middleware('role:admin')->group(...)` block in
`routes/web.php`, next to the current `admin.students.create` / `admin.students.store`
routes.

- `GET /admin/students` → `admin.students.index`
  - Query scoped to `role = 'student'` only.
  - Query-string params (all optional, all preserved across pagination links since it's a
    GET form submitting to the same URL):
    - `q` — matches `name LIKE %...%` OR `login_id LIKE %...%`
    - `program` — exact match against `major`
    - `year_level` — exact match against `year_level`
  - Paginated at 20 per page (Laravel's default paginator).
- `DELETE /admin/students/{user}` → `admin.students.destroy`
  - `abort_unless($user->role === 'student', 404)` — guards against this route being
    repurposed to soft-delete a staff/admin account.
  - `$user->delete()` (soft delete via the trait).
  - Redirect back with a flash success message.

## UI

- New view: `resources/views/admin/students/index.blade.php`.
- New sidebar nav entry "Student Registry", inserted between "Create Student Account" and
  "Curriculum" in every admin page's sidebar (same sidebar markup is currently duplicated
  per-page — this spec doesn't refactor that, just adds the one `<a>` link consistently).
- Table columns, matching Fig 62: Student ID (`login_id`), Name, Program (`major`), Year
  Level, Education Level (`program_level`), Actions.
- Search box (`q`) + two filter `<select>`s (Program, sourced from the same `Program`
  list used on `admin.students.create`; Year Level, same four options as that page) — a GET
  form submitting to `admin.students.index`.
- Delete button per row: a small inline POST form (`@method('DELETE')`) with an
  `onsubmit="return confirm(...)"` guard — same pattern as `admin.departments.toggle`'s
  existing per-row action forms. No separate confirmation page.
- Pagination links styled consistently with the rest of the admin panel (Tailwind classes
  matching existing tables/cards, brandGreen/brandNavy tokens from `resources/css/app.css`).
- Empty state: if no students match the filters, show a simple "No students found" message
  in place of the table body — not a blocking edge case, just a one-line fallback.

## Testing

- Feature test: `admin.students.index` returns only `role = 'student'` accounts, excludes
  soft-deleted ones, and respects `q`/`program`/`year_level` filters.
- Feature test: `admin.students.destroy` soft-deletes the target student, the student
  disappears from the index, and the student can no longer log in (`AuthController::login`
  returns invalid-credentials for their `login_id`).
- Feature test: `admin.students.destroy` returns 404 (not a soft delete) when targeting a
  non-student account (e.g. a chair or cashier user).
- Feature test: non-admin roles get 403 on both routes (existing `role:admin` middleware
  pattern, same as every other admin route).

## Related work (not in this spec)

Once this merges, the next spec is **Admin Account Management with granular permissions**
(multiple admin accounts, each restricted to specific admin-panel capabilities — e.g. one
admin limited to Curriculum + Student Registry, another to Departments + Audit Trail). That
work needs its own data model (a permissions table/column), a privilege-escalation
safeguard (a limited admin must not be able to grant themselves more access), and changes
to route middleware — deliberately scoped out of this pass, to be brainstormed fresh as its
own spec.
