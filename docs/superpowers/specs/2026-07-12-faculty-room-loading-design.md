# Faculty & Room Loading — Design

**Date:** 2026-07-12
**Status:** Approved (Approach A — normalize onto existing tables)

## Overview

Implements the capstone paper's "Faculty and Room Loading" module ("a management feature
allowing administrators to allocate faculty workloads and assign physical or virtual
classrooms efficiently"), objective 2.2's schedule-conflict prevention, and objective 2.3's
face-to-face vs online modes. Also closes the paper's promise of faculty as a role-based
system user: faculty get view-only login accounts.

Admins assign a professor and a room to each section from the existing curriculum editor.
The system rejects assignments that double-book a professor (always) or a physical room
(virtual rooms never conflict). Faculty log in to a read-only weekly teaching schedule.

## Decisions made during brainstorming

- Faculty are **login accounts** (`users.role = 'faculty'`), view-only. No faculty table.
- Rooms are **entities** in a new `rooms` table with `type` = `physical` | `virtual`.
  A section's delivery mode is inherited from its room's type (virtual room = online class).
- Loading is **admin-only**, done inside the existing curriculum React island.
- Conflict rules: same school year + overlapping days and time range →
  same faculty is always a conflict; same room is a conflict only when the room is physical.
- No teaching-load caps, no workload units, no room capacity (YAGNI — paper doesn't ask).

## Data model

Two migrations:

1. `rooms`: `id`, `name` (string 50, unique), `type` (string 10, `physical`|`virtual`),
   timestamps.
2. `sections` gains nullable `faculty_id` (FK → `users`, `nullOnDelete`) and nullable
   `room_id` (FK → `rooms`, `nullOnDelete`).

The legacy `sections.professor` / `sections.room` strings **remain** as fallback display
text. When an FK is set, API payloads serve the linked entity's name; when null, they fall
back to the string. No data is destroyed.

Models: new `Room` model (`$fillable = ['name', 'type']`, `sections()` hasMany, helper
`isPhysical(): bool`). `Section` gains `faculty()` (belongsTo User) and `roomEntity()`
(belongsTo Room) relations plus accessors `facultyName(): string` and `roomLabel(): string`
implementing the FK-or-string fallback. `User` gains `taughtSections()` (hasMany Section,
`faculty_id`).

Seeder (dev/local only for accounts, same production guard as existing demo accounts):
- Faculty users `faculty01` / `faculty02`, password `password123`, role `faculty`.
- Rooms created from the distinct legacy room strings on seeded sections (all `physical`),
  plus one virtual room `Google Meet A`.
- Seeded sections get `faculty_id` / `room_id` backfilled so the demo starts fully linked.

## Conflict validation

New `app/Services/SectionScheduleService.php`:

```
assertNoConflicts(array $attributes, ?Section $ignore = null): void
```

Called by `SectionController::store` and `update` before persisting (update passes the
section being edited so it doesn't conflict with itself). Scope: sections in the same
`school_year` whose `days` intersect and whose `[start_time, end_time)` ranges overlap —
same overlap semantics as `Section::overlaps()`.

- Overlap + same `faculty_id` (non-null) → `EnrollmentException` 409, message names the
  professor and the conflicting section (subject code, block, days, time).
- Overlap + same `room_id` (non-null) where the room's type is `physical` →
  `EnrollmentException` 409, message names the room and conflicting section.
- Virtual rooms never conflict. Null FKs never conflict (legacy string sections are exempt).

Errors surface through the existing JSON `{"message": ...}` shape the curriculum island
already renders as a red banner.

## API (all inside the existing `role:admin` group)

- `GET /api/admin/faculty` → `{faculty: [{id, name, login_id, sections_count}]}` (role
  `faculty` users, with count for the current school year). "Current school year"
  everywhere in this spec means `EnrollmentService::currentTerm()['school_year']`
  (i.e., `Setting::get('school_year', '2026-2027')`).
- `POST /api/admin/faculty` → create a faculty user: `name` required; `login_id` required
  unique; password defaults to `password123` (dev convention, hashed); role forced to
  `faculty` server-side. Returns 201 `{faculty: {...}}`.
- `GET /api/admin/faculty/{user}/schedule` → that professor's sections for the current
  school year (subject code/title, block, days, times, room label). 404-safe via route
  model binding; non-faculty target → 422.
- `GET /api/admin/rooms` → `{rooms: [{id, name, type}]}`.
- `POST /api/admin/rooms` → `name` required unique max 50, `type` required in
  `physical,virtual`. Returns 201.
- `SectionController::store/update` accept optional `faculty_id`
  (`exists:users,id` + must have role faculty) and `room_id` (`exists:rooms,id`), invoke
  `SectionScheduleService::assertNoConflicts`, and their JSON now includes `faculty_name`
  and `room_label`.

No delete endpoints in v1 (consistent with programs). Audit log on every create and every
section assignment: actions `Faculty Created`, `Room Created`, existing
`Curriculum Updated` for section changes.

## Admin UI (curriculum island, `resources/js/curriculum-app.jsx`)

- Faculty and room lists loaded once alongside programs.
- `SectionEditor.jsx`: professor and room free-text inputs become `<select>` dropdowns
  (options from the lists, empty option = "— unassigned —"). Next to each, a small
  "+ Add" toggle reveals an inline mini-form (faculty: name + login ID; room: name +
  physical/virtual) that POSTs and appends to the list without leaving the editor.
- Conflict responses render in the island's existing error banner.
- New "Faculty Loading" view (tab/toggle in the island header): table of faculty with
  weekly load (sections count, total hours) for the current school year; clicking a row
  expands that professor's schedule (subject, block, days, time, room). Data from
  `GET /api/admin/faculty` + `GET /api/admin/faculty/{id}/schedule`.

## Faculty dashboard

- Route `/faculty/schedule`, name `faculty.schedule`, middleware `role:faculty`, Blade view
  `resources/views/faculty/schedule.blade.php`.
- Read-only weekly grid of the authed professor's sections (subject code + title, block,
  time, room label with an "Online" badge when the room is virtual), reusing the student
  schedule grid's markup/time-parsing patterns, brand palette + dark mode.
- Empty state when no sections are loaded.
- `AuthController::handleRoleRedirection` gains `case 'faculty': 'faculty.schedule'`.
- Notif bell: new `faculty` branch — one summary notif "You are loaded with N section(s)
  this A.Y." (0 sections → "No teaching load assigned yet."). All new notif fields flow
  through the existing `escNotif()` escaping.

## Testing

Feature tests following existing patterns (PHPUnit, RefreshDatabase, SQLite :memory:):

- Rooms API: admin can create/list; duplicate name 422; bad type 422; student 403.
- Faculty API: admin can create/list (role forced to faculty); duplicate login_id 422;
  schedule endpoint returns only that professor's current-year sections; non-admin 403.
- Conflict matrix on section store/update: same-faculty overlap 409; physical-room overlap
  409; virtual-room overlap allowed; disjoint days allowed; disjoint times allowed;
  different school_year allowed; editing a section doesn't conflict with itself;
  null-FK legacy sections exempt.
- Section payloads: store/update with FKs returns `faculty_name`/`room_label`; legacy
  string-only payloads still work unchanged.
- Faculty page: faculty sees own sections only; student/chair get 403 on
  `/faculty/schedule`; faculty gets 403 on admin/chair/student-only routes.
- Seeder: demo faculty + rooms exist and seeded sections are linked (dev env).

## Out of scope (explicitly)

- Teaching-load caps, workload units, CHED load reports.
- Room capacity and capacity-vs-section checks.
- Faculty editing anything (view-only role).
- Delete endpoints for faculty/rooms.
- Migrating away the legacy `professor`/`room` strings (kept as fallback).
