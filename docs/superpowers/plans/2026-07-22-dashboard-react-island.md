# Dashboard React Island Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert `resources/views/dashboard.blade.php`'s content area (welcome banner + announcements placeholder) into a React island (`dashboard-app.jsx`), matching the existing `enrollment-app.jsx`/`curriculum-app.jsx` pattern, with no change to server-side data or behavior.

**Architecture:** Two static presentational components (`WelcomeBanner`, `AnnouncementsPanel`) under `resources/js/dashboard/`, mounted by a `dashboard-app.jsx` entry into a `#dashboard-root` div that replaces the current inline welcome/announcements markup in the Blade view. Sidebar, header, and all Blade-side nav logic are untouched.

**Tech Stack:** React 18, Vite (`laravel-vite-plugin`, `@vitejs/plugin-react`), Blade, PHPUnit.

## Global Constraints

- This is a **purely mechanical port** — no new API endpoints, no new data. `AnnouncementsPanel` renders the same static "No announcements at this time." text; no fetch, no loading/error states beyond the initial React-mount placeholder.
- Sidebar, `<header>` (notif-bell, theme-toggle, profile-menu), and all Blade-side `Route::is(...)` nav-highlighting logic in `dashboard.blade.php` must NOT be touched.
- The `/dashboard` route closure in `routes/web.php:52-67` (including its `Clearance::initializeFor(...)` call) must NOT be modified.
- No stub `/api/announcements` endpoint — do not add one.
- Do not touch `layouts/app.blade.php` (stays unused/dead, unrelated to this work).
- Follow the exact island-mounting convention already used by `resources/js/enrollment-app.jsx` (see Task 1 for the reference pattern): `const el = document.getElementById('<id>-root'); if (el) createRoot(el).render(<App />);`

---

### Task 1: Create the React components and wire the Vite entry

**Files:**
- Create: `resources/js/dashboard/WelcomeBanner.jsx`
- Create: `resources/js/dashboard/AnnouncementsPanel.jsx`
- Create: `resources/js/dashboard-app.jsx`
- Modify: `vite.config.js`

**Interfaces:**
- Produces: `WelcomeBanner` (default export, no props, renders the welcome heading/subtitle), `AnnouncementsPanel` (default export, no props, renders the announcements card), both consumed by `dashboard-app.jsx`.
- Produces: a `#dashboard-root` mount point contract — Task 2's Blade edit must provide a `<div id="dashboard-root">` element for this entry to attach to.

There is no JS test runner in this repo (confirmed during the prior Tailwind CDN removal work — Blade-rendered pages have no JS test infra), so verification for this task is `npm run build` succeeding and producing a `dashboard-app` entry in the Vite manifest. Functional verification happens in Task 2's PHPUnit test and Task 3's manual check.

- [ ] **Step 1: Create `resources/js/dashboard/WelcomeBanner.jsx`**

Ported verbatim from the current Blade markup (`resources/views/dashboard.blade.php:65-68`):

```jsx
export default function WelcomeBanner() {
    return (
        <div className="mb-6">
            <h1 className="text-2xl font-black text-brandNavy dark:text-white mb-1">
                Welcome to <span className="text-brandGreen">AITSA</span>
            </h1>
            <p className="text-sm text-brandNavy/50 dark:text-slate-400">
                Asian Institute of Technology, Science &amp; Arts
            </p>
        </div>
    );
}
```

- [ ] **Step 2: Create `resources/js/dashboard/AnnouncementsPanel.jsx`**

Ported verbatim from the current Blade markup (`resources/views/dashboard.blade.php:71-78`):

```jsx
export default function AnnouncementsPanel() {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
            <h2 className="text-sm font-bold text-brandNavy dark:text-white mb-4">
                <i className="fa-solid fa-bullhorn text-brandGreen mr-2" />Announcements
            </h2>
            <div className="text-center text-brandNavy/40 dark:text-slate-500 py-4">
                <p className="text-sm">No announcements at this time.</p>
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Create `resources/js/dashboard-app.jsx`**

Follows the exact mount pattern used by `resources/js/enrollment-app.jsx:126-127` (`const el = document.getElementById('enrollment-root'); if (el) createRoot(el).render(<EnrollmentApp />);`). A Fragment (not an extra wrapper `<div>`) is used so `WelcomeBanner`'s own `mb-6` spacing is preserved exactly as it was in the original Blade markup, with no new wrapper element the original didn't have:

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import WelcomeBanner from './dashboard/WelcomeBanner';
import AnnouncementsPanel from './dashboard/AnnouncementsPanel';

function DashboardApp() {
    return (
        <>
            <WelcomeBanner />
            <AnnouncementsPanel />
        </>
    );
}

const el = document.getElementById('dashboard-root');
if (el) createRoot(el).render(<DashboardApp />);
```

- [ ] **Step 4: Register the new entry in `vite.config.js`**

Current `input` array (`vite.config.js:8-13`):
```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/enrollment-app.jsx',
    'resources/js/curriculum-app.jsx',
],
```

Change to:
```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/enrollment-app.jsx',
    'resources/js/curriculum-app.jsx',
    'resources/js/dashboard-app.jsx',
],
```

- [ ] **Step 5: Verify the build**

Run: `npm run build`
Expected: exits 0. Check the output listing includes a `dashboard-app` chunk (e.g. `dashboard-app-XXXXXXXX.js` among the built assets), confirming Vite picked up the new entry.

- [ ] **Step 6: Commit**

```bash
git add resources/js/dashboard/WelcomeBanner.jsx resources/js/dashboard/AnnouncementsPanel.jsx resources/js/dashboard-app.jsx vite.config.js
git commit -m "feat: add dashboard React island components and Vite entry"
```

---

### Task 2: Mount the island in `dashboard.blade.php`, with a regression test

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: `resources/js/dashboard-app.jsx` (Task 1) via `@vite('resources/js/dashboard-app.jsx')`, and the `#dashboard-root` mount contract from Task 1.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="dashboard-root"', false);
        $response->assertDontSee('Welcome to');
        $response->assertDontSee('No announcements at this time.');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect();
    }
}
```

(`assertDontSee` confirms the static welcome/announcements text moved out of the server-rendered HTML and into the React island, not that it's gone from the page entirely — the island still renders it client-side after JS runs, which this HTTP-level test cannot execute.)

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=DashboardTest`
Expected: FAIL on `assertSee('id="dashboard-root"', false)` — the current Blade template has no such element yet.

- [ ] **Step 3: Replace the welcome/announcements block in `dashboard.blade.php`**

Current content (`resources/views/dashboard.blade.php:62-80`):
```blade
            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

                {{-- WELCOME SECTION --}}
                <div class="mb-6">
                    <h1 class="text-2xl font-black text-brandNavy dark:text-white mb-1">Welcome to <span class="text-brandGreen">AITSA</span></h1>
                    <p class="text-sm text-brandNavy/50 dark:text-slate-400">Asian Institute of Technology, Science &amp; Arts</p>
                </div>

                {{-- ANNOUNCEMENTS SECTION --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
                    <h2 class="text-sm font-bold text-brandNavy dark:text-white mb-4">
                        <i class="fa-solid fa-bullhorn text-brandGreen mr-2"></i>Announcements
                    </h2>
                    <div class="text-center text-brandNavy/40 dark:text-slate-500 py-4">
                        <p class="text-sm">No announcements at this time.</p>
                    </div>
                </div>

            </div>
```

Replace with:
```blade
            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

                <div id="dashboard-root">
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
```

- [ ] **Step 4: Add the Vite entry for the island**

Current tail of the file (`resources/views/dashboard.blade.php:84-87`):
```blade

@include('partials.notif-script')
</body>
</html>
```

Replace with:
```blade

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/dashboard-app.jsx')
</body>
</html>
```

(Matches `resources/views/enrollment.blade.php:59-61`'s tail exactly, substituting the entry filename.)

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=DashboardTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Run the full suite to confirm no regressions**

Run: `php artisan test`
Expected: all tests pass (184 existing + 2 new = 186).

- [ ] **Step 7: Commit**

```bash
git add resources/views/dashboard.blade.php tests/Feature/DashboardTest.php
git commit -m "feat: mount dashboard React island in dashboard.blade.php"
```

---

### Task 3: Build and manually verify

**Files:** none (verification only).

- [ ] **Step 1: Production build**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 2: Start the app**

Run `php artisan serve` (or use XAMPP/Apache as usual) — no `npm run dev` needed since Task 1's build already compiled static assets into `public/build`.

- [ ] **Step 3: HTTP-level verification**

Using the same authenticated-curl approach used for the Tailwind CDN removal plan's Task 7 (extract CSRF token from `/login`, POST credentials with a cookie jar, GET protected pages with the same jar):

1. Authenticate as student `2300410` / `password`.
2. `GET /dashboard`: confirm HTTP 200, `id="dashboard-root"` present, a `dashboard-app-*.js` Vite asset referenced, and neither "Welcome to" nor "No announcements at this time." present in the raw HTML (confirming they now come from the React island, not server-rendered markup).

- [ ] **Step 4: Manual browser verification (if a browser tool is available)**

Check: the welcome banner and announcements card render identically to how they looked before this change (same fonts/colors/spacing), in both light and dark mode, with no console errors. If no browser-automation tool is available in this environment, explicitly flag this step as unperformed rather than claiming it passed — consistent with how the Tailwind CDN removal plan handled the same gap.

- [ ] **Step 5: Run the full test suite one more time**

Run: `php artisan test`
Expected: all tests pass (same count as after Task 2).

- [ ] **Step 6: Final commit (if manual verification surfaced any fixes)**

If Step 3 or Step 4 required any fix-up edits, commit them separately with a descriptive message before considering this plan complete.
