# Remove Tailwind CDN + Centralize Theme — Design

**Date:** 2026-07-19
**Status:** Approved

## Overview

The paper's remaining React-SPA gap and a standing tech-debt complaint ("we're still
using inline style") share a root cause: every one of the 25 Blade views is fully
standalone (none use `@extends` — `layouts/app.blade.php` exists but nothing extends
it), so each page independently repeats a `<script src="https://cdn.tailwindcss.com">`
tag, an inline `tailwind.config = {...}` block (colors/fonts/animations), and a
dark-mode toggle script (~15-30 duplicated lines × 25 files). Compiled Tailwind v4 is
already wired up via Vite (`@tailwindcss/vite`, `resources/css/app.css`) and already
used by the two React islands (`enrollment-app.jsx`, `curriculum-app.jsx`) — this
change extends that existing pipeline to every remaining Blade page instead of
building anything new.

This is explicitly the smaller, groundwork half of the paper's React-SPA gap, not the
SPA migration itself. It only touches each page's `<head>`; body/sidebar/nav markup is
untouched. A future full layout-inheritance refactor (`@extends('layouts.app')`
everywhere) was considered and rejected for now — see "Alternatives considered."

## Decisions made during brainstorming

- **Canonical theme source: `login.blade.php`'s palette.** `brandNavy` (#0B3C5D),
  `brandGreen` (#1D7A46), `brandGold` (#E2A700), `darkBg` (#07101C), `lightBg`
  (#EFF3F7), `panelDark` (#0D1B2A), `surfaceDark` (#111E2E). `auth/apply.blade.php`
  defines a divergent copy (`darkBg` #121212, `panelDark` #1E1E1E) but never uses any
  `dark:` class on that page (confirmed via grep) — it's dead config, dropped rather
  than reconciled.
- **Preserve class-based dark mode.** Tailwind v4's default dark variant follows
  `prefers-color-scheme`. This app toggles dark mode manually via a `.dark` class on
  `<html>`, persisted to `localStorage`. Losing this would silently break the toggle
  button on every page, so `app.css` gets an explicit
  `@custom-variant dark (&:where(.dark, .dark *));`.
- **Split the duplicated toggle script in two, by paint-timing requirement.** The
  "read localStorage / apply `.dark` before first paint" part must stay a raw
  blocking inline `<script>` (a Vite-bundled module loads async/deferred and would
  cause a flash of the wrong theme) — extracted to one shared partial,
  `partials/theme-init.blade.php`, included identically on every page. The
  `toggleTheme()` click-handler part has no such timing constraint and moves into
  `resources/js/app.js`, loaded via `@vite`. Verified the toggle icon element
  (`id="theme-icon"`) is consistent across all pages that have it, so one shared
  function can bind correctly everywhere.
- **`toggleTheme` must stay `window`-scoped.** Toggle buttons use inline
  `onclick="toggleTheme()"` HTML attributes. Moving the function into an ES module
  (`app.js`) does not put it in global scope by default, so `app.js` must explicitly
  set `window.toggleTheme = toggleTheme` or every existing button breaks silently.
- **Font Awesome and Google Fonts `<link>` tags are out of scope.** They're
  duplicated per-page too, but that's a separate concern from Tailwind/inline-script
  cleanup — not touched here.
- **Inline `style=""` cleanup rule: static+simple → Tailwind arbitrary-value class;
  dynamic or complex → stays inline.** Only 5 files have literal `style=""`
  attributes, and most of them are legitimately computed (PHP `{{ $pct }}%`,
  JS-template-literal-generated calendar cell styles, a dynamic `asset()` background
  image) — converting those to arbitrary-value classes would just obscure a real
  computed value behind unreadable syntax, so they stay inline. Only static,
  simple values convert.

## `resources/css/app.css` changes

Add to the existing `@theme` block:

```css
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

(Exact keyframe/easing values carried over verbatim from `login.blade.php`'s inline
config; `.prog-card`/`.form-slide` carried over verbatim from `auth/apply.blade.php`'s
inline `<style>` block.)

## New files

- **`resources/views/partials/theme-init.blade.php`** — blocking inline script, runs
  in `<head>` before any stylesheet paints:
  ```html
  <script>
      (function () {
          var theme = localStorage.getItem('theme');
          var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
          document.documentElement.classList.toggle('dark', isDark);
      })();
  </script>
  ```
- **`resources/js/app.js` additions** — the toggle function, exposed globally so
  existing `onclick="toggleTheme()"` markup keeps working unchanged:
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
  (Pages whose toggle script does extra work on top of this — e.g.
  `schedule.blade.php` re-rendering timetable colors on theme change — keep that
  extra bit as a small page-local script; only the duplicated core moves to `app.js`.)

## Per-page change (all 25 Blade views)

Remove:
- `<script src="https://cdn.tailwindcss.com"></script>`
- the inline `tailwind.config = {...}` block
- the inline dark-mode toggle script (core logic now in `app.js`; page-specific extras
  like `schedule.blade.php`'s timetable re-render stay inline, calling the same
  `toggleTheme`/icon convention)

Add, in `<head>`:
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
@include('partials.theme-init')
```

Untouched: Font Awesome `<link>`, Google Fonts `<link>` (login/apply only), and all
body/sidebar/nav/content markup on every page.

## Inline `style=""` cleanup

Convert (static, simple):
- `partials/profile-menu.blade.php`: `style="font-size:5px;"` → `text-[5px]`
- `login.blade.php` decorative `.geo` divs (4×): fixed `width/height/top/right` values
  → `w-[68px] h-[68px] top-[17%] right-[14%]` etc.
- `login.blade.php`: `style="transition: opacity .3s;"` → `transition-opacity
  duration-300`

Leave inline (dynamic or not worth the arbitrary-value unreadability):
- `admin/reports.blade.php`: `style="width: {{ $pct }}%"` (PHP-computed)
- `schedule.blade.php`: all JS-template-literal-generated cell styles (dynamic
  colors/sizes from data)
- `auth/apply.blade.php`: dynamic `asset()` banner background-image + its gradient
  overlay
- `login.blade.php`: the decorative gradient divider (`background:
  linear-gradient(...)`) — static but not worth the bracket-syntax unreadability for a
  one-off

## Testing / Verification

- Confirmed via grep: no PHPUnit test couples to CDN markup or inline `tailwind.config`
  content, so the existing suite is unaffected by this change.
- Manual/visual verification (no JS test infra exists for Blade-rendered pages in this
  repo, consistent with how prior UI-only changes were verified): `npm run build`,
  then check that login and apply pages render with correct custom fonts/colors/
  animations, one authenticated dashboard renders correctly, the dark-mode toggle
  still flips and persists across a reload with no flash of the wrong theme on load,
  and Font Awesome icons still display.
- Both `php artisan serve` (or XAMPP/Apache) for the PHP side and either `npm run dev`
  (development, hot-reload) or a one-time `npm run build` (static compiled assets, no
  dev server needed) for the front-end are required — same as the existing
  enrollment/curriculum islands already need today, nothing new.

## Out of scope (explicitly)

- Any change to page body/sidebar/nav markup, or converting any page to
  `@extends('layouts.app')` — a full layout-inheritance refactor is a separate,
  larger initiative and was rejected here because pages slated for the eventual React
  SPA migration would have that Blade-body work thrown away anyway once converted to
  React islands.
- Font Awesome / Google Fonts CDN deduplication.
- Any change to `layouts/app.blade.php` itself (stays unused/dead for now).
- Migrating any additional page to a React island — that's the next, separate spec
  (student `dashboard.blade.php` was the pick for that future round).
