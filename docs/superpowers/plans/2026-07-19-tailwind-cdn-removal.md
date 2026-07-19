# Tailwind CDN Removal + Theme Centralization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Tailwind CDN `<script>` + duplicated inline `tailwind.config`/dark-mode-toggle scripts on every Blade page with the already-existing compiled Tailwind v4 + Vite pipeline (used today by the two React islands), centralizing theme tokens and dark-mode logic in one place.

**Architecture:** `resources/css/app.css` becomes the single theme source (`@theme` colors/fonts/animations + `@custom-variant dark`). A new `partials/theme-init.blade.php` (blocking inline script) prevents flash-of-wrong-theme on every page. `resources/js/app.js` gets the shared `toggleTheme`/`updateThemeIcon` functions, exposed on `window` so existing `onclick="toggleTheme()"` markup keeps working. Each of 24 Blade views swaps its CDN block for `@vite(...)` + the new partial. `layouts/app.blade.php` is explicitly untouched (dead file, out of scope per the design spec).

**Tech Stack:** Laravel 12 Blade, Tailwind CSS v4 (`@tailwindcss/vite`), Vite 7, vanilla JS (no new dependencies).

## Global Constraints

- Canonical theme values (colors, fonts, animation timings) must match `login.blade.php`'s existing inline `tailwind.config` verbatim — see `docs/superpowers/specs/2026-07-19-tailwind-cdn-removal-design.md`.
- Dark mode must stay class-based (`.dark` on `<html>`), not switch to OS-preference media query — requires `@custom-variant dark (&:where(.dark, .dark *));` in `app.css`.
- `layouts/app.blade.php` must not be modified.
- Font Awesome and Google Fonts `<link>` tags must not be touched on any page.
- No PHPUnit test currently couples to CDN markup (verified during brainstorming) — the existing suite must still pass unmodified after this change.

---

### Task 1: Centralize theme tokens in `resources/css/app.css`

**Files:**
- Modify: `resources/css/app.css`

**Interfaces:**
- Produces: Tailwind utility classes `text-brandNavy`, `bg-darkBg`, etc. (via `--color-*` tokens), `font-display`/`font-body` (via `--font-*` tokens), `animate-fade-in-up`/`animate-fade-in` (via `--animate-*` tokens), a working `dark:` variant driven by a `.dark` class (not OS preference), and a `.prog-card`/`.form-slide` component class pair — all consumed by Task 4's page conversions.

- [ ] **Step 1: Read the current file**

Current content of `resources/css/app.css`:
```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}
```

- [ ] **Step 2: Replace it with the centralized theme**

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
    --font-display: 'Cormorant Garamond', Georgia, serif;
    --font-body: 'DM Sans', sans-serif;

    --color-brandNavy: #0B3C5D;
    --color-brandGreen: #1D7A46;
    --color-brandGold: #E2A700;
    --color-darkBg: #07101C;
    --color-lightBg: #EFF3F7;
    --color-panelDark: #0D1B2A;
    --color-surfaceDark: #111E2E;

    --animate-fade-in-up: fadeInUp 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    --animate-fade-in: fadeIn 0.55s ease-out forwards;
}

@custom-variant dark (&:where(.dark, .dark *));

@keyframes fadeInUp {
    0%   { opacity: 0; transform: translateY(22px); }
    100% { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    0%   { opacity: 0; }
    100% { opacity: 1; }
}

@layer components {
    .prog-card { cursor: pointer; transition: all .18s; }
    .prog-card:hover { transform: translateY(-2px); }
    .prog-card.selected { outline: 2px solid #1D7A46; outline-offset: 2px; }
    .form-slide { transition: max-height .4s ease, opacity .3s ease; overflow: hidden; }
    .form-slide.hidden-anim { max-height: 0; opacity: 0; pointer-events: none; }
    .form-slide.visible-anim { max-height: 4000px; opacity: 1; }
}
```

- [ ] **Step 3: Verify the build compiles**

Run: `npm run build`
Expected: exits 0, no CSS syntax errors reported for `app.css`.

- [ ] **Step 4: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: centralize Tailwind theme tokens and dark-mode variant in app.css"
```

---

### Task 2: Add shared dark-mode toggle logic to `resources/js/app.js`

**Files:**
- Modify: `resources/js/app.js`

**Interfaces:**
- Consumes: nothing new (still imports `./bootstrap` as before).
- Produces: `window.toggleTheme()` (global function, callable from inline `onclick="toggleTheme()"` HTML attributes) and a `DOMContentLoaded` listener that syncs the `#theme-icon` element's icon class to the current `.dark` state on every page load. Both are relied on by every page converted in Task 4.

- [ ] **Step 1: Read the current file**

Current content of `resources/js/app.js`:
```js
import './bootstrap';
```

- [ ] **Step 2: Replace it with the shared toggle logic**

```js
import './bootstrap';

function updateThemeIcon() {
    var icon = document.getElementById('theme-icon');
    if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
}

function toggleTheme() {
    var isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeIcon();
}

window.toggleTheme = toggleTheme;
document.addEventListener('DOMContentLoaded', updateThemeIcon);
```

- [ ] **Step 3: Verify the build compiles**

Run: `npm run build`
Expected: exits 0, `public/build/assets/app-*.js` regenerated with no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/app.js
git commit -m "feat: move dark-mode toggle logic into shared app.js entry"
```

---

### Task 3: Create the shared pre-paint theme-init partial

**Files:**
- Create: `resources/views/partials/theme-init.blade.php`

**Interfaces:**
- Produces: a `<script>` block, included via `@include('partials.theme-init')` in every page's `<head>` (Task 4), that applies the `.dark` class before first paint using the exact same default-to-light behavior every page currently has (no OS-preference fallback — that would be a behavior change beyond this task's scope).

- [ ] **Step 1: Create the file**

```html
<script>
    (function () {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
    })();
</script>
```

- [ ] **Step 2: Verify it renders**

Run: `php artisan tinker --execute="echo view('partials.theme-init')->render();"`
Expected: prints the `<script>...</script>` block above with no Blade compile errors.

- [ ] **Step 3: Commit**

```bash
git add resources/views/partials/theme-init.blade.php
git commit -m "feat: add shared pre-paint dark-mode init partial"
```

---

### Task 4: Convert all 24 Blade pages to the compiled Tailwind pipeline

**Files:**
- Modify: `resources/views/admin/audit.blade.php`, `resources/views/schedule.blade.php`, `resources/views/auth/apply.blade.php`, `resources/views/login.blade.php` (worked examples below), then apply the identical pattern to: `resources/views/dashboard.blade.php`, `resources/views/enrollment.blade.php`, `resources/views/clearance.blade.php`, `resources/views/payment.blade.php`, `resources/views/admin/curriculum.blade.php`, `resources/views/admin/reports.blade.php`, `resources/views/admin/departments.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/admin/create-student.blade.php`, `resources/views/registrar/dashboard.blade.php`, `resources/views/registrar/reports.blade.php`, `resources/views/registrar/students.blade.php`, `resources/views/registrar/student-grades.blade.php`, `resources/views/cashier/dashboard.blade.php`, `resources/views/cashier/transactions.blade.php`, `resources/views/cashier/billing.blade.php`, `resources/views/cashier/accounts.blade.php`, `resources/views/approver/dashboard.blade.php`, `resources/views/department/dashboard.blade.php`, `resources/views/faculty/schedule.blade.php`.

**Do NOT modify:** `resources/views/layouts/app.blade.php` (explicitly out of scope — see Global Constraints).

**Interfaces:**
- Consumes: `@vite(['resources/css/app.css', 'resources/js/app.js'])` (Laravel's built-in Vite Blade directive — no new interface, standard framework feature), `@include('partials.theme-init')` (Task 3).

**The pattern** (identical for every file, only the exact removed text differs slightly per file):
1. Delete the `<script src="https://cdn.tailwindcss.com"></script>` line.
2. Delete the immediately-following `<script>...tailwind.config = {...}...</script>` block.
3. If a dark-mode init/toggle/icon `<script>...</script>` block immediately follows that config block, delete it too (every file has this except `auth/apply.blade.php`, which has no toggle script at all — its `darkMode` config is dead, confirmed unused during brainstorming).
4. Insert in its place:
   ```blade
   @vite(['resources/css/app.css', 'resources/js/app.js'])
   @include('partials.theme-init')
   ```
5. Leave every other line (Font Awesome link, Google Fonts links, page title, body/nav/sidebar markup) untouched.

- [ ] **Step 1: Convert `resources/views/admin/audit.blade.php` (worked example — "bare-if" variant)**

Before:
```blade
    <title>AITSA Admin | Audit Trail</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
            }}}
        }
    </script>
    <script>
        if ((localStorage.getItem('theme') || 'light') === 'dark') document.documentElement.classList.add('dark');
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

After:
```blade
    <title>AITSA Admin | Audit Trail</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

- [ ] **Step 2: Convert `resources/views/schedule.blade.php` (worked example — "named function" variant)**

Before (lines 7–37):
```blade
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
                    }
                }
            }
        }
    </script>
    <script>
        function updateThemeIcon() {
            var icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        initializeTheme();
    </script>
```

After:
```blade
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
```

(Leave line 38's Font Awesome link and line 39's `qrcode.min.js` script untouched. **Do not stop here for this file** — Task 5 below has a second, required edit further down this same file.)

- [ ] **Step 3: Convert `resources/views/auth/apply.blade.php` (worked example — dead-config, no-toggle, custom `<style>` variant)**

Before:
```blade
    <title>Apply Now | Asian Institute of Technology, Science and Arts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .prog-card { cursor: pointer; transition: all .18s; }
        .prog-card:hover { transform: translateY(-2px); }
        .prog-card.selected { outline: 2px solid #1D7A46; outline-offset: 2px; }
        .form-slide { transition: max-height .4s ease, opacity .3s ease; overflow: hidden; }
        .form-slide.hidden-anim { max-height: 0; opacity: 0; pointer-events: none; }
        .form-slide.visible-anim { max-height: 4000px; opacity: 1; }
    </style>
</head>
```

After (the `<style>` block is deleted — it now lives in `app.css`'s `@layer components`, added in Task 1):
```blade
    <title>Apply Now | Asian Institute of Technology, Science and Arts</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
```

- [ ] **Step 4: Convert `resources/views/login.blade.php` (worked example — fonts-before-CDN, init-only/no-toggle variant)**

Before:
```blade
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy:  '#0B3C5D',
                        brandGreen: '#1D7A46',
                        brandGold:  '#E2A700',
                        darkBg:     '#07101C',
                        lightBg:    '#EFF3F7',
                        panelDark:  '#0D1B2A',
                        surfaceDark:'#111E2E',
                    },
                    fontFamily: {
                        display: ['Cormorant Garamond', 'Georgia', 'serif'],
                        body:    ['DM Sans', 'sans-serif'],
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'fade-in':    'fadeIn 0.55s ease-out forwards',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%':   { opacity: '0', transform: 'translateY(22px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeIn: {
                            '0%':   { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                    }
                }
            }
        }
    </script>
    <script>
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        initializeTheme();
    </script>

    <style>
```

After (the trailing `<style>` block — login-page-specific floating-label/decorative CSS, not mentioned by the design spec — stays exactly as-is, untouched):
```blade
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')

    <style>
```

- [ ] **Step 5: Apply the identical pattern (steps 1–4's rule) to the remaining 20 files**

For each of: `resources/views/dashboard.blade.php`, `resources/views/enrollment.blade.php`, `resources/views/clearance.blade.php`, `resources/views/payment.blade.php`, `resources/views/admin/curriculum.blade.php`, `resources/views/admin/reports.blade.php`, `resources/views/admin/departments.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/admin/create-student.blade.php`, `resources/views/registrar/dashboard.blade.php`, `resources/views/registrar/reports.blade.php`, `resources/views/registrar/students.blade.php`, `resources/views/registrar/student-grades.blade.php`, `resources/views/cashier/dashboard.blade.php`, `resources/views/cashier/transactions.blade.php`, `resources/views/cashier/billing.blade.php`, `resources/views/cashier/accounts.blade.php`, `resources/views/approver/dashboard.blade.php`, `resources/views/department/dashboard.blade.php`, `resources/views/faculty/schedule.blade.php`:

Open the file, locate the `<script src="https://cdn.tailwindcss.com"></script>` line, delete it and every `<script>...</script>` block that immediately follows it up to (but not including) the next non-script line (Font Awesome `<link>` in every one of these 20 files), and insert the two-line replacement from the pattern above in its place. None of these 20 files have any additional dark-mode-related code outside this contiguous block (verified during brainstorming via `grep -c "initializeTheme()"` returning exactly 2 — one definition, one call, both inside the block being deleted — for every file in this list except the four worked examples above, which were separately verified).

- [ ] **Step 6: Verify no page still references the CDN**

Run: `grep -rl "cdn.tailwindcss.com" resources/views --include=*.blade.php`
Expected: only `resources/views/layouts/app.blade.php` (untouched, out of scope). No other file listed.

- [ ] **Step 7: Verify every converted page references the new pipeline**

Run:
```bash
for f in resources/views/dashboard.blade.php resources/views/enrollment.blade.php resources/views/clearance.blade.php resources/views/schedule.blade.php resources/views/payment.blade.php resources/views/login.blade.php resources/views/auth/apply.blade.php resources/views/admin/curriculum.blade.php resources/views/admin/reports.blade.php resources/views/admin/departments.blade.php resources/views/admin/dashboard.blade.php resources/views/admin/create-student.blade.php resources/views/admin/audit.blade.php resources/views/registrar/dashboard.blade.php resources/views/registrar/reports.blade.php resources/views/registrar/students.blade.php resources/views/registrar/student-grades.blade.php resources/views/cashier/dashboard.blade.php resources/views/cashier/transactions.blade.php resources/views/cashier/billing.blade.php resources/views/cashier/accounts.blade.php resources/views/approver/dashboard.blade.php resources/views/department/dashboard.blade.php resources/views/faculty/schedule.blade.php; do
  grep -q "@vite(\['resources/css/app.css', 'resources/js/app.js'\])" "$f" && grep -q "@include('partials.theme-init')" "$f" || echo "MISSING in $f"
done
```
Expected: no `MISSING in ...` lines printed.

- [ ] **Step 8: Run the existing PHPUnit suite**

Run: `php artisan test`
Expected: all tests still pass (no test coupled to CDN/head markup, per Global Constraints).

- [ ] **Step 9: Commit**

```bash
git add resources/views
git commit -m "feat: replace Tailwind CDN with compiled Vite pipeline on all Blade pages"
```

---

### Task 5: Fix `schedule.blade.php`'s theme-toggle script-ordering hazard

**Files:**
- Modify: `resources/views/schedule.blade.php` (body script, near the bottom of the file)

**Interfaces:**
- Consumes: `window.toggleTheme` (Task 2's `app.js`, loaded as a deferred `type="module"` script via `@vite` — deferred/module scripts execute after the document is parsed but before `DOMContentLoaded` fires).

Why this is needed: this file's own script currently reassigns `window.toggleTheme` at the top level of a classic (non-module) inline `<script>` block near the end of `<body>`. Classic inline scripts execute immediately as the parser reaches them — which happens *before* any deferred/module script (including the new `app.js`) has run. If left as-is, `const _origToggle = window.toggleTheme` would capture `undefined`, and clicking the toggle button on this page would throw `_origToggle is not a function`. Moving this code inside the page's existing `DOMContentLoaded` handler defers it until after `app.js` has already set `window.toggleTheme`, fixing the ordering. This same edit also removes a now-dead `initializeTheme()` call — that function no longer exists on this page after Task 4 deleted its definition (flash-prevention is now handled by `partials/theme-init.blade.php` in `<head>`, which already runs before this point).

- [ ] **Step 1: Read the current code**

Current content (near the end of the file, in the timetable-rendering `<script>` block):
```js
// -- Init on load -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
    renderTimetable();
    renderQRCode();
});

// Re-render timetable when theme is toggled (colors change)
const _origToggle = window.toggleTheme;
window.toggleTheme = function() {
    _origToggle();
    renderTimetable();
};
```

- [ ] **Step 2: Replace it**

```js
// -- Init on load -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    renderTimetable();
    renderQRCode();

    // Re-render timetable when theme is toggled (colors change)
    const _origToggle = window.toggleTheme;
    window.toggleTheme = function() {
        _origToggle();
        renderTimetable();
    };
});
```

- [ ] **Step 3: Verify no other stale reference remains**

Run: `grep -n "initializeTheme" resources/views/schedule.blade.php`
Expected: no output (function definition and both calls are gone — the head-block one from Task 4, the body one from this step).

- [ ] **Step 4: Commit**

```bash
git add resources/views/schedule.blade.php
git commit -m "fix: defer schedule page's theme-toggle wrapper until after app.js loads"
```

---

### Task 6: Convert static inline `style=""` attributes to Tailwind classes

**Files:**
- Modify: `resources/views/partials/profile-menu.blade.php`
- Modify: `resources/views/login.blade.php`

**Interfaces:** none (pure markup cleanup, no behavior change).

- [ ] **Step 1: Convert `partials/profile-menu.blade.php`'s inline font-size**

Before:
```blade
<i class="fa-solid fa-chevron-down text-brandNavy/40 dark:text-slate-500" style="font-size:5px;"></i>
```

After:
```blade
<i class="fa-solid fa-chevron-down text-brandNavy/40 dark:text-slate-500 text-[5px]"></i>
```

- [ ] **Step 2: Convert `login.blade.php`'s four decorative `.geo` divs**

Before:
```blade
        <div class="geo geo-a" style="width:68px;height:68px;top:17%;right:14%;"></div>
        <div class="geo geo-b" style="width:36px;height:36px;top:41%;right:28%;"></div>
        <div class="geo geo-c" style="width:16px;height:16px;top:66%;right:17%;"></div>
        <div class="geo geo-d" style="width:130px;height:130px;top:8%;left:6%;"></div>
```

After:
```blade
        <div class="geo geo-a w-[68px] h-[68px] top-[17%] right-[14%]"></div>
        <div class="geo geo-b w-[36px] h-[36px] top-[41%] right-[28%]"></div>
        <div class="geo geo-c w-[16px] h-[16px] top-[66%] right-[17%]"></div>
        <div class="geo geo-d w-[130px] h-[130px] top-[8%] left-[6%]"></div>
```

- [ ] **Step 3: Convert `login.blade.php`'s static transition style**

Before:
```blade
     style="transition: opacity .3s;">
```

After: find this attribute's parent element and replace the `style="transition: opacity .3s;"` attribute with a `transition-opacity duration-300` addition to that element's existing `class="..."` list, then delete the `style="..."` attribute entirely.

- [ ] **Step 4: Confirm the remaining inline styles were intentionally left alone**

Run: `grep -n 'style="' resources/views/admin/reports.blade.php resources/views/schedule.blade.php resources/views/auth/apply.blade.php resources/views/login.blade.php`
Expected output: only these remain —
- `admin/reports.blade.php`: `style="width: {{ $pct }}%"` (PHP-computed, left inline)
- `schedule.blade.php`: the JS-template-literal-generated cell styles (dynamic, left inline)
- `auth/apply.blade.php`: the dynamic `asset()` banner background-image + its gradient overlay (left inline)
- `login.blade.php`: the decorative gradient divider (`background: linear-gradient(...)`) (left inline, static but not worth arbitrary-value unreadability)

- [ ] **Step 5: Commit**

```bash
git add resources/views/partials/profile-menu.blade.php resources/views/login.blade.php
git commit -m "style: convert static inline styles to Tailwind arbitrary-value classes"
```

---

### Task 7: Build and manually verify

**Files:** none (verification only).

- [ ] **Step 1: Production build**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 2: Start the app**

Run `php artisan serve` (or use XAMPP/Apache as usual) — no `npm run dev` needed since Task 1's build already compiled static assets into `public/build`.

- [ ] **Step 3: Manual browser verification** (use the `/run` skill if available to drive this)

Check, in order:
1. `/login` — page renders with the navy/green/gold brand colors, Cormorant Garamond display font on headings, DM Sans body font, decorative `.geo` shapes still positioned correctly, no console errors.
2. `/apply` — page renders correctly, `.prog-card`/`.form-slide` interactive states (hover lift, selected outline, expand/collapse) still work.
3. Log in as a student (`2300410` / `password`) and land on the dashboard — verify the theme toggle button flips light/dark, the sun/moon icon updates, and the choice persists across a page reload with no flash of the wrong theme on load.
4. Visit `/schedule` — verify the dark-mode toggle still works AND the timetable cell colors actually re-render when toggling (this is the specific behavior Task 5 fixed — open the browser console first and confirm no `_origToggle is not a function` error is thrown on click).
5. Confirm Font Awesome icons render on at least two pages (they were never touched, but worth confirming nothing else broke their loading).

- [ ] **Step 4: Run the full test suite one more time**

Run: `php artisan test`
Expected: all tests pass (same count as before this plan — no test file was touched).

- [ ] **Step 5: Final commit (if Step 3 surfaced any fixes)**

If manual verification required any fix-up edits, commit them separately with a descriptive message before considering this plan complete.
