# Student Clearance → React Island — Design

**Date:** 2026-07-22
**Status:** Approved

## Overview

The paper's remaining gap is a full React 18 SPA; four islands are mounted so far
(enrollment, curriculum, dashboard, schedule). This spec converts
`resources/views/clearance.blade.php` — the student's "Enrollment Clearance" page — to
a fifth island, `clearance-app.jsx`.

This is the largest and most stateful port yet. Unlike the dashboard's static markup or
the schedule's read-only grid, this page has: real per-student clearance/document data,
a genuine file-upload modal with drag-and-drop that submits through an existing route
(`clearance.submitRequirement`), and several independently-conditional display states
(cleared vs. on-hold, submission pending vs. not yet submitted, etc.).

**Prerequisite fix (already done, `126da72`):** while scoping this work, a real bug was
found and fixed on `master`: accepting a student's uploaded document
(`registrar.documents.accept`) never touched the student's `Clearance` record — the only
path to `registrar_status = 'Approved'` was a separate, disconnected "Sign Clearance"
button. Accepting a document now also approves the clearance (mirroring
`registrar.sign`'s own update) and clears remarks. The "Sign Clearance" button is
untouched and still works standalone, preserving registrar discretion for
documentless approvals (e.g. first-year students whose prior-school records haven't
been released yet). This spec is written against the fixed behavior.

## Decisions made during brainstorming

- **The hardcoded fake fee-items list is preserved verbatim.** `clearance.blade.php:213-224`
  has a literal PHP array of fake action items (Reservation Fee, Tuition Fee
  payments, etc.) that was never wired to the real ledger system
  (`TransactionLedger`, `FeeAssessmentService::breakdownFor`). Wiring it to real data is
  separate, bigger feature work — deliberately out of scope, same discipline as
  deferring the dashboard's real announcements feature.
- **`#enrollmentShortcut` (the "click here to proceed to enrollment" banner) stays dead
  code**, ported verbatim as permanently hidden. Nothing in the current JS ever
  un-hides it; fixing/wiring it up is separate, out-of-scope work.
- **The document-upload modal keeps its native browser form POST.** React renders a
  real `<form action="{data-submit-url}" method="POST" enctype="multipart/form-data">`
  and submits it the same way the current vanilla JS does (full page reload, server
  redirects back with a flash message) — zero backend changes. Converting to
  fetch/AJAX would require the existing `clearance.submitRequirement` route to also
  support a JSON response, which is real backend work beyond a mechanical port.
- **Five components**, one per visual section: `MasterStatusBadge`, `AccountingCard`,
  `RegistrarCard`, `SubmittedDocumentsList`, `SubmitRequirementModal`.
- **All "business logic" stays server-side.** The existing `@php` block computing
  `$isCleared`/`$registrarCleared`/`$cashierCleared`/`$chairCleared`/`$hasSubmission`/
  `$submissionPending` is unchanged — its output is serialized into JSON instead of
  driving Blade conditionals directly. Date formatting (`->format('M d, Y g:i A')`) and
  `documents.show` route URLs are also computed server-side into that JSON, rather than
  re-implemented in JS — the same principle that would have prevented the schedule
  island's en-dash bug if it had been followed there from the start.
- **Flash messages (`session('success')`, `$errors->any()`) stay in Blade**, as siblings
  before `#clearance-root` in the outer content div — they're pure server-side
  conditionals tied to the full-page-reload upload flow, not React's concern.

## New files

- **`resources/js/clearance/MasterStatusBadge.jsx`** — props: `{ isCleared,
  cashierCleared, registrarCleared, chairCleared, items }`. The overall Cleared/Pending
  badge plus the per-office (Accounting/Registrar/Chair) and per-department checklist
  rows (`items: [{ departmentName, status, remarks }]`).
- **`resources/js/clearance/AccountingCard.jsx`** — props: `{ cashierCleared }`. Balance
  display + the hardcoded action-items list (ported verbatim, static array inside the
  component) + conditional "Go to Payment" link.
- **`resources/js/clearance/RegistrarCard.jsx`** — props: `{ registrarCleared,
  submission, onOpenModal }`, where `submission = { hasSubmission, submissionPending,
  createdAt, originalName }`. Renders the cleared state, or the hold detail plus either
  the submission-pending banner (with a "Resubmit" trigger) or the "Submit Documents"
  CTA. `onOpenModal(isResubmit: boolean)` is a callback into `clearance-app.jsx`'s modal
  state.
- **`resources/js/clearance/SubmittedDocumentsList.jsx`** — props: `{ submissions }`
  (array of `{ typeLabel, documentsShowUrl, originalName, createdAtFormatted, status,
  remarks }`). Renders nothing if `submissions.length === 0` (mirrors Blade's
  `@if(isset($submissions) && $submissions->isNotEmpty())`).
- **`resources/js/clearance/SubmitRequirementModal.jsx`** — props: `{ open, isResubmit,
  onClose, csrfToken, submitUrl }`. Ports the drag-and-drop/file-preview/submit-button
  loading-state logic to `useState` (selected `File` object, submitting flag). Renders a
  real `<form action={submitUrl} method="POST" encType="multipart/form-data">` with a
  hidden `_token` input; drag-and-drop still uses the `DataTransfer` trick to populate
  the real file input, since the actual submission relies on the browser's native
  multipart encoding of that input, not any state React holds directly.
- **`resources/js/clearance-app.jsx`** — entry point. Reads `data-context` (JSON),
  `data-csrf-token`, `data-submit-url` off `#clearance-root`. Renders the page
  title + the permanently-hidden `#enrollmentShortcut` banner + the remarks banner
  (`$clearance->remarks`, when present) inline (not worth their own component files),
  then the five components, wrapped in its own `space-y-6` div (same reasoning as the
  schedule island: the outer Blade content div's `space-y-6` spacing rule stops
  applying once these sections collapse into a single `#clearance-root` child). Owns
  `useState` for modal open/isResubmit, passed down to `RegistrarCard` (trigger) and
  `SubmitRequirementModal` (consumer).

## Modified files

- **`vite.config.js`** — add `'resources/js/clearance-app.jsx'` to the `input` array.
- **`resources/views/clearance.blade.php`**:
  - Replace the page-title-through-submitted-documents block (everything from "PAGE
    TITLE + ENROLLMENT SHORTCUT" through the closing of the "MY SUBMITTED DOCUMENTS"
    `@if`, plus the entire `DOCUMENT SUBMISSION MODAL` block and its `<script>`) with a
    single `<div id="clearance-root" data-context="{{ json_encode([...]) }}"
    data-csrf-token="{{ csrf_token() }}" data-submit-url="{{
    route('clearance.submitRequirement') }}"><p class="text-sm
    text-slate-500">Loading…</p></div>`.
  - The `data-context` JSON is built from a small `@php` block replacing the existing
    one, mapping `$clearance`/`$submission`/`$submissions` into the exact shape listed
    under New Files → `clearance-app.jsx`, reusing the existing boolean computations.
  - Add near the bottom of `<body>`, after `@include('partials.notif-script')`:
    `@viteReactRefresh` and `@vite('resources/js/clearance-app.jsx')`.
  - **Untouched:** sidebar, header (notif-bell/theme-toggle/profile-menu), the flash
    message blocks (`session('success')`, `$errors->any()`), the `drop-zone`/`modal-enter`
    `<style>` block in `<head>`, `partials.notif-script`.
- **No change to the `/clearance` route closure, `Clearance::initializeFor()`,
  `clearance.submitRequirement`, or any registrar/chair/cashier hold/sign/approve
  route.**

## Existing-test impact

Three pre-existing tests assert against this page's server-rendered text; once the
submitted-documents list and remarks banner move into React, that text either
disappears from server HTML entirely or only survives incidentally inside the raw
`data-context` JSON attribute (an unreliable thing to assert against). These need real
updates, not a cosmetic 1-line swap:

- **`tests/Feature/ClearancePageDocumentsTest.php`** — both tests
  (`test_student_sees_own_submissions_with_status_and_remarks`,
  `test_page_hides_history_section_when_no_submissions`) currently assert visible
  headings/filenames/remarks text. Update to assert against the `data-context`
  attribute's JSON content instead (e.g. `assertSee('"original_name":"my-form137.pdf"',
  false)`), preserving the same intent (own submissions with correct status/remarks
  appear; other students' don't; the identifying data is present/absent as expected).
- **`tests/Feature/ClearanceHoldTest.php::test_remarks_shown_on_student_clearance_page`**
  — same treatment: assert the remarks text appears inside `data-context`'s JSON rather
  than as visible page text.
- **`tests/Feature/DocumentUploadTest.php`** — untouched. It tests the
  `clearance.submitRequirement` POST endpoint directly, which this migration does not
  change.

## Testing / Verification

- New `tests/Feature/ClearanceIslandTest.php` (mirrors `ScheduleTest`/`DashboardTest`):
  assert `id="clearance-root"` present, old static section headers gone from
  server-rendered HTML, guest redirect.
- The three existing-test updates above (TDD: confirm they fail against the old
  assertions once the markup changes, then update and confirm green).
- `npm run build` must exit 0 with the new entry compiled.
- Authenticated-curl HTTP-level check (same method as prior islands): confirm
  `id="clearance-root"`, the new Vite JS asset referenced, `data-context` containing
  real clearance/submission data, and zero leftover static section-header markup.
- Full suite run (`php artisan test`) at the end to confirm no regressions.
- Manual/visual browser verification (modal drag-and-drop, file preview, print none —
  N/A here — dark mode, and confirming the real upload still round-trips end to end)
  remains a known, explicitly-flagged gap — no browser-automation tool connected in
  this environment.

## Out of scope (explicitly)

- Wiring the fake fee-items list to real ledger data (`FeeAssessmentService`).
- Un-hiding/wiring up `#enrollmentShortcut`.
- Converting the upload modal to fetch/AJAX.
- Any change to `clearance.submitRequirement`, `/clearance`'s route closure, or any
  registrar/chair/cashier hold/sign/approve route (the `126da72` fix is already merged
  to `master`, prior to this island work, and is not part of this diff).
- Converting any other Blade page to a React island — this spec is clearance-only.
- Any change to `layouts/app.blade.php` (stays unused/dead).
