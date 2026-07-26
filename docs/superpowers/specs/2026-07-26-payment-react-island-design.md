# Ledger & Payments → React Island — Design

**Date:** 2026-07-26
**Status:** Approved

## Overview

Five islands are mounted so far (enrollment, curriculum, dashboard, schedule, clearance).
This spec converts `resources/views/payment.blade.php` — the student's "Ledger &
Payments" page, routed as `/ledger` — to a sixth, `payment-app.jsx`.

Unlike the other islands, this page has almost no client-side interactivity: it's
conditional server-rendered display (balance card, payment history, clearance status
summary) plus two one-click POST-and-redirect buttons (checkout, verify). The
conversion is done for architectural consistency with the rest of the student-facing
side, not for new interactivity — confirmed explicitly during brainstorming rather than
assumed.

Alongside the port, this spec also hardens the two payment actions
(`ledger.checkout`, `ledger.verify`) against abuse, since "port this page" and "make
sure the payment path is safe" were raised together and the actions live on the page
being touched.

## Decisions made during brainstorming

- **Convert despite no new interactivity gain**, for stack consistency across the
  student side (explicit user choice, not a default).
- **Forms stay native.** `<form method="POST" action=...>` with a hidden CSRF input,
  rendered by React but submitted as a real full-page POST+redirect — same as
  `clearance-app.jsx`'s upload modal. No fetch/JSON API introduced. Converting these to
  an AJAX/JSON API was explicitly considered and rejected: no interactivity payoff, and
  it would require the existing routes to grow a JSON response mode for no consumer.
- **Security hardening scoped to `ledger.checkout` and `ledger.verify` only** — not the
  wider payment subsystem. There is no PayMongo webhook receiver anywhere in this
  codebase today (`PaymentService::verifyLatestPending()` is purely pull-based, called
  only from the manual "Verify Payment" button or the `/ledger/payment/return`
  redirect); building one is separate, larger feature work, not an extension of this
  page's migration. Flagged as a follow-up: if a student closes the tab mid-checkout
  without hitting return/verify, the row stays `Pending` indefinitely with nothing to
  reconcile it.
- **`peso()` currency formatter hoisted to a shared module.** It's currently duplicated
  inline in `resources/js/clearance/AccountingCard.jsx`; `BalanceCard.jsx` needs the
  identical formatter, so it moves to `resources/js/utils/format.js` and both islands
  import it. Currency values themselves stay raw numbers in the JSON context (matching
  `AccountingCard`'s existing convention) — formatting happens client-side.
- **Three components**, one per visual section: `BalanceCard`, `PaymentHistoryCard`,
  `ClearanceStatusCard`.
- **All business logic stays server-side.** `$settled`, `$hasPendingGateway`, and the
  `$breakdown` array are computed exactly as today; date formatting
  (`created_at->format('M d, Y g:i A')`) and the gateway label ("PayMongo (online)" vs.
  "Cashier window") are computed in PHP into the history rows, not re-derived in JS.

## New files

- **`resources/js/utils/format.js`** — exports `peso(n)`, moved verbatim from
  `AccountingCard.jsx`.
- **`resources/js/payment/BalanceCard.jsx`** — props: `{ settled, breakdown,
  hasPendingGateway, checkoutUrl, verifyUrl, csrfToken }`. Renders the outstanding
  balance, the assessment breakdown line items (tuition, discount if any, misc,
  assessment total, paid, balance), and the conditional actions: "Account Settled"
  (disabled) when settled, a native checkout form when not settled and no pending
  gateway row, and a native verify form (plus the "didn't update?" hint text) when
  `hasPendingGateway`.
- **`resources/js/payment/PaymentHistoryCard.jsx`** — props: `{ history }` (array of
  `{ referenceNo, gatewayLabel, createdAtFormatted, amount, status }`). Renders nothing
  if `history.length === 0` (mirrors Blade's `@if(isset($history) && $history->isNotEmpty())`),
  including the status pill color-mapping (Settled/Pending/Failed/other) currently done
  inline in Blade.
- **`resources/js/payment/ClearanceStatusCard.jsx`** — props: `{ cashierCleared,
  registrarCleared, chairCleared }`. The four-row status summary (Accounting, Library —
  hardcoded cleared same as today, Registrar, Department Head).
- **`resources/js/payment-app.jsx`** — entry point. Reads `data-context` (JSON),
  `data-csrf-token` off `#payment-root`. Renders the three components in a `space-y-6`
  wrapper (same reasoning as other islands: the outer Blade content div's spacing rule
  stops applying once these sections collapse into a single child), wrapped in the
  existing `<ErrorBoundary>`.

## Modified files

- **`resources/js/clearance/AccountingCard.jsx`** — replace its inline `peso()`
  definition with `import { peso } from '../utils/format';`. No behavior change.
- **`vite.config.js`** — add `'resources/js/payment-app.jsx'` to the `input` array.
- **`resources/views/payment.blade.php`**:
  - Replace the three card blocks (Balance Overview, Payment History, Clearance Status
    Summary) with a single `<div id="payment-root" data-context="{{
    json_encode($paymentContext) }}" data-csrf-token="{{ csrf_token() }}"><p
    class="text-sm text-slate-500">Loading…</p></div>`.
  - Add a `@php` block building `$paymentContext` from the existing `$clearance`,
    `$breakdown`, `$history`, `$hasPendingGateway` variables (same shape listed under
    New Files → `payment-app.jsx`'s props, aggregated).
  - Add near the bottom of `<body>`: `@viteReactRefresh` and
    `@vite('resources/js/payment-app.jsx')`.
  - **Untouched:** sidebar, header, the `session('success')`/`session('error')` flash
    blocks (stay Blade, outside `#payment-root`, same pattern as clearance's flash
    messages).
- **`routes/web.php`**:
  - The `/ledger` route closure is unchanged — it still passes
    `compact('clearance', 'breakdown', 'history', 'hasPendingGateway')` to the view.
    `$paymentContext` is built in `payment.blade.php`'s new `@php` block from those same
    variables, matching how `clearance.blade.php` built `$clearanceContext` in its view
    rather than its route closure.
  - Add `throttle:6,1` middleware to `POST /ledger/checkout`.
  - Add `throttle:10,1` middleware to `POST /ledger/verify` and `GET
    /ledger/payment/return`.
- **`app/Services/PaymentService.php`** — `startCheckout()`: wrap the
  cancel-stale-rows-then-create-new-row logic in `DB::transaction()` with
  `lockForUpdate()` on the user's existing `paymongo`/`Pending` rows, so two concurrent
  requests (double-click, duplicate tab) can't both pass the cancel step before either
  inserts and end up with two live Pending rows against two separate PayMongo sessions
  for the same balance.

## Existing-test impact

- Any ledger/payment feature test asserting visible balance/history text in the
  server-rendered HTML needs updating to assert against `data-context`'s JSON content
  instead (same treatment as the clearance island's test updates).
- New test: two rapid `POST /ledger/checkout` calls while one Pending row already exists
  result in exactly one live Pending row (covers the transaction/lock fix).
- New test: a 7th `POST /ledger/checkout` (or `/ledger/verify`) within the throttle
  window returns `429`.

## Testing / Verification

- New `tests/Feature/PaymentIslandTest.php` (mirrors `ClearanceIslandTest.php`): assert
  `id="payment-root"` present, old static card markup gone from server-rendered HTML,
  guest redirect.
- Existing-test updates above (TDD: confirm red against old assertions, then update and
  confirm green).
- `npm run build` must exit 0 with the new entry compiled.
- Authenticated-curl HTTP-level check: confirm `id="payment-root"`, the new Vite JS
  asset referenced, `data-context` containing real breakdown/history data, zero
  leftover static card markup.
- Full suite run (`php artisan test`) at the end to confirm no regressions.
- Manual/visual browser verification (checkout button, verify button, dark mode, empty
  history state) using the Chrome browser tools now available in this environment —
  unlike the clearance island (built before those tools existed), this should be
  performed, not flagged as a gap.

## Out of scope (explicitly)

- Any PayMongo webhook/signature-verification receiver.
- Converting checkout/verify to a fetch/JSON API.
- Any change to `ledger.payment.cancel`, `Clearance::initializeFor()`, or any
  registrar/chair/cashier hold/sign/approve route.
- Converting any other Blade page to a React island — this spec is payment-only.
