# Student Document Submissions — Design

**Date:** 2026-07-12
**Status:** Approved (Approach A — Blade + private storage)

## Overview

Makes the clearance page's document-upload modal real and gives the Registrar's Office a
review queue. Today the modal fakes its upload with a `setTimeout` and the
`clearance.submitRequirement` route stores nothing. After this feature, students upload
actual files (proof documents such as Form 137), the registrar views and accepts/rejects
them with remarks, and students see the outcome on their clearance page.

## Decisions made during brainstorming

- **Review workflow**, not view-only: registrar marks each submission Accepted or
  Rejected with remarks; the student sees the status.
- Document review is **independent** of clearance signing. Accepting a document does NOT
  change `clearances.registrar_status`; the registrar still signs clearance via the
  existing action. The document queue is evidence, not a trigger.
- File rules: `pdf, jpg, jpeg, png`, max **5120 KB** (5 MB) — safe for XAMPP defaults.
- Files live on the **private local disk**, never `public/`. Access only through an
  authorized route.
- Each upload is a new row — resubmissions preserve history; old rows are never edited
  by students.

## Data model

One migration: `document_submissions`

| column | type | notes |
|---|---|---|
| `id` | id | |
| `user_id` | FK → users | cascadeOnDelete |
| `document_type` | string 20 | `form137`, `form138`, `birth_cert`, `good_moral`, `other` (same keys as the modal select) |
| `notes` | string 1000 nullable | student's note to registrar |
| `file_path` | string | hashed path on the private disk, e.g. `documents/abc123.pdf` |
| `original_name` | string | the uploaded file's client name (display only) |
| `mime_type` | string 100 | |
| `size` | unsignedInteger | bytes |
| `status` | string 10 | `pending` (default), `accepted`, `rejected` |
| `remarks` | string 500 nullable | registrar's note, required on reject |
| `reviewed_by` | FK → users nullable | nullOnDelete |
| `reviewed_at` | timestamp nullable | |
| timestamps | | |

Model `DocumentSubmission`: `$fillable` all of the above except id/timestamps;
`user(): BelongsTo`, `reviewer(): BelongsTo (reviewed_by)`;
`typeLabel(): string` mapping keys → display names ("Form 137 — Permanent Record /
Senior HS Report Card", etc.); `casts` `reviewed_at => datetime`.
`User` gains `documentSubmissions(): HasMany`.

Storage: `$request->file('document')->store('documents', 'local')` — Laravel generates a
hashed filename, preventing collisions and path tricks. `original_name` is metadata only.

## Student flow (clearance page)

- The existing modal keeps its markup/design. The `<form>` gains
  `enctype="multipart/form-data"`; `handleSubmit()` performs a real `form.submit()` after
  its existing client-side file check (remove the `setTimeout` fake and the in-modal
  success state; the redirect flash message is the success feedback).
- `POST /clearance/submit-requirement` (existing route, now does work) validates:
  `document` `required|file|mimes:pdf,jpg,jpeg,png|max:5120`;
  `document_type` `required|in:form137,form138,birth_cert,good_moral,other`;
  `notes` `nullable|string|max:1000`.
  Stores the file, creates the row, audit-logs `Document Submitted`, redirects with the
  existing success flash. Validation errors redirect back and render near the drop zone
  via `@error`.
- New "My Submitted Documents" list on the clearance page (below the upload card):
  type label, original filename, submitted date, status badge (amber Pending / green
  Accepted / red Rejected), registrar remarks shown when rejected, and a link to
  view/download their own file.

## Registrar flow (registrar dashboard, role `registrar,admission`)

- New "Student Document Submissions" panel, styled like the dashboard's existing tables,
  newest first: student name + login ID, document type label, original filename + size,
  submitted date, status badge, student notes.
- Pending rows offer: **View** (opens file in new tab), **Accept**, **Reject** with an
  inline required remarks input (max 500) — same inline-form pattern as the chair's
  enrollment reject.
- `POST /registrar/documents/{submission}/accept` and `/reject`: guard that status is
  `pending` (otherwise redirect back with an error flash), set status /
  `reviewed_by` / `reviewed_at` (+ `remarks`), audit-log `Document Reviewed`, redirect
  with a success flash. Route names `registrar.documents.accept` / `.reject`.
- Reviewed rows remain listed with their badge and reviewer remarks.

## Download/view route

`GET /documents/{submission}`, name `documents.show`, middleware `auth`.
Authorization inside the closure/controller: allow when
`auth()->id() === $submission->user_id` OR `auth()->user()->role` is `registrar` or
`admission`; else 403. Streams from the private disk via
`Storage::response($submission->file_path, $submission->original_name)` (inline
disposition so PDFs/images open in the browser). If the file is missing on disk,
return 404.

## Notifications (notif bell, all fields through `escNotif()`)

- **Student branch**: latest reviewed submission (if any) — accepted: green
  "Document Accepted / Your {type} was accepted by the Registrar."; rejected: red
  "Document Rejected / Your {type} was rejected: {remarks}". Plus an amber entry when a
  submission is still pending review.
- **Registrar branch**: pending count — "N document submission(s) awaiting review"
  (only when N > 0).

## Error handling

- Validation failures (type/size/unknown document_type) → redirect back with errors.
- Reviewing a non-pending submission → error flash, no change.
- Download of a missing file → 404; other student's file → 403; guest → login redirect.

## Testing

PHPUnit, RefreshDatabase, `Storage::fake('local')`:

- Upload: student submit creates row + stored file + `Document Submitted` audit log;
  rejects oversized (>5120 KB), wrong mime (e.g. .exe), invalid document_type; guests
  redirected.
- Listing: clearance page shows only the student's own submissions with status; registrar
  dashboard viewData includes submissions from all students.
- Review: registrar accept works (status, reviewed_by, reviewed_at, audit log); reject
  requires remarks; re-reviewing a reviewed row is blocked; students get 403 on the
  review routes.
- Download: owner 200; registrar 200; another student 403; missing file 404.

## Out of scope (explicitly)

- Auto-approving `registrar_status` from document review.
- A required-documents checklist per student.
- File preview thumbnails, virus scanning, or e-signatures (DocuSign remains a separate
  open paper gap).
- Email notifications (separate open gap).
