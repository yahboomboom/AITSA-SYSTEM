# Student Dashboard → React Island — Design

**Date:** 2026-07-22
**Status:** Approved

## Overview

The paper's remaining gap is a full React 18 SPA; today only `enrollment.blade.php`
and `admin/curriculum.blade.php` mount React islands, everything else (including
`dashboard.blade.php`) is plain Blade. This spec converts `dashboard.blade.php`'s
content area to a third island, `dashboard-app.jsx`, following the exact pattern the
other two islands already established.

This is a **purely mechanical port** — no new backend, no new data. The dashboard's
only "content" today is a static welcome banner and a hardcoded "No announcements at
this time" placeholder; there is no `Announcement` model, migration, or route
anywhere in the codebase. A real announcements feature (admin posts announcements,
students see them here) is deliberately deferred until after the rest of the paper's
React-SPA gap is closed — this pass just gets the page onto the island architecture
so that future feature is a self-contained addition instead of a second migration.

## Decisions made during brainstorming

- **Convert now despite having no dynamic data**, rather than waiting for the real
  announcements feature. Rationale: doing the island conversion and the future
  feature together would be two migrations bundled into one; doing it now means the
  future feature only has to touch `AnnouncementsPanel.jsx`.
- **Sidebar, header, and nav stay Blade**, matching `enrollment.blade.php`'s existing
  split exactly — only the inner content area (welcome banner + announcements card)
  becomes the React mount point. `notif-bell`, `theme-toggle` (`onclick="toggleTheme()"`),
  and `profile-menu` partials are untouched.
- **Lightly componentized, not a single flat file** (Approach B over A/C considered):
  `WelcomeBanner.jsx` and `AnnouncementsPanel.jsx` as separate files under
  `resources/js/dashboard/`, mirroring the subdirectory convention `enrollment/` and
  `curriculum/` already use. `AnnouncementsPanel` renders the same static placeholder
  text for now — no API call.
- **No stub API endpoint.** A `/api/announcements` route that returns `[]` today was
  considered and rejected as premature scaffolding for a feature with no committed
  design yet — YAGNI.
- **`$clearance` stays unused, untouched.** The `/dashboard` route closure in
  `routes/web.php` already computes `Clearance::initializeFor(...)` and passes
  `$clearance` to the view, but the current Blade template never renders it. Out of
  scope for this port — not touching working code (with its own side effects via
  `initializeFor`) that isn't part of this migration.

## New files

- **`resources/js/dashboard/WelcomeBanner.jsx`** — renders the "Welcome to
  <span class="text-brandGreen">AITSA</span>" heading and the
  "Asian Institute of Technology, Science & Arts" subtitle. Static JSX, ported
  verbatim from the current Blade markup.
- **`resources/js/dashboard/AnnouncementsPanel.jsx`** — renders the announcements
  card: bullhorn-icon heading, and the "No announcements at this time" empty state.
  Static JSX, ported verbatim.
- **`resources/js/dashboard-app.jsx`** — entry point. Imports both components,
  mounts them into `#dashboard-root` via `createRoot(el).render(...)`, same shape as
  `enrollment-app.jsx`/`curriculum-app.jsx`.

## Modified files

- **`vite.config.js`** — add `'resources/js/dashboard-app.jsx'` to the `input` array
  alongside the existing two island entries.
- **`resources/views/dashboard.blade.php`** — replace the "WELCOME SECTION" and
  "ANNOUNCEMENTS SECTION" blocks (lines ~64-78) with:
  ```blade
  <div id="dashboard-root">
      <p class="text-sm text-slate-500">Loading…</p>
  </div>
  ```
  Add at the bottom of `<body>`, after `@include('partials.notif-script')`:
  ```blade
  @viteReactRefresh
  @vite('resources/js/dashboard-app.jsx')
  ```
  Everything else (sidebar, `<aside>` nav, `<header>` with notif-bell/theme-toggle/
  profile-menu) is untouched, matching `enrollment.blade.php`'s existing split.

## Testing / Verification

- No new PHPUnit tests — no server-side behavior changes (route closure, controller
  logic, and `$clearance` computation are all untouched). A quick Feature test
  asserting `GET /dashboard` still returns 200 for an authenticated student is
  reasonable low-cost insurance and will be added.
- `npm run build` must exit 0 with the new entry compiled.
- Manual HTTP-level verification (same method used for the Tailwind CDN removal):
  authenticated `curl` against `/dashboard`, confirm `id="dashboard-root"` present,
  the new Vite JS asset referenced, and zero leftover static welcome/announcements
  markup outside the React mount point.
- Full suite run (`php artisan test`) at the end to confirm no regressions.
- Real interactive/visual confirmation (does the island actually render the banner
  and card correctly in a browser) still requires a human — no browser-automation
  tool is connected in this environment.

## Out of scope (explicitly)

- Building the real announcements feature (model, migration, admin CRUD, API route).
  Tracked as a separate future spec, to start after the rest of the paper's
  React-SPA gap is closed, per user direction.
- Any change to `routes/web.php`'s `/dashboard` closure or the `$clearance`
  computation.
- Converting any other Blade page to a React island — this spec is dashboard-only.
- Any change to `layouts/app.blade.php` (stays unused/dead, same as before).
