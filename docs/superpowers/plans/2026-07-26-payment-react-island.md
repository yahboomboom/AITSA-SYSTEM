# Payment (Ledger & Payments) React Island Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert `resources/views/payment.blade.php` (`/ledger`) to a sixth React island, `payment-app.jsx`, and harden the `ledger.checkout`/`ledger.verify` payment actions against abuse and race conditions.

**Architecture:** Sidebar, header, and session flash banners stay server-rendered Blade. A single `#payment-root` mount point receives a `data-context` JSON blob (balance/breakdown/history/clearance-status booleans, all computed server-side) plus `data-csrf-token`/`data-checkout-url`/`data-verify-url` attributes. Three presentational components (`BalanceCard`, `PaymentHistoryCard`, `ClearanceStatusCard`) render from that data; the two payment actions stay native `<form method="POST">` submits — no fetch, no new API surface.

**Tech Stack:** Laravel 11 (PHP), React 18 + Vite (no JS test runner in this project — component verification is via `npm run build` + PHP feature tests asserting on rendered markup/`data-context`), PHPUnit.

## Global Constraints

- Forms stay native `<form method="POST">` submits (full page reload) — no fetch/AJAX conversion. (Explicit brainstorming decision.)
- Security hardening is scoped to `ledger.checkout` and `ledger.verify` (+ `ledger.payment.return`) only — no webhook receiver, no change to `ledger.payment.cancel`. (Explicit brainstorming decision.)
- All business logic (status booleans, currency values, date formatting, gateway labels) is computed server-side in PHP and passed as already-computed JSON — never re-derived in JS.
- Currency values in `data-context` are raw numbers; formatting to `₱ X,XXX.XX` happens client-side via a shared `peso()` helper.
- Sidebar, header, and `session('success')`/`session('error')` flash blocks in `payment.blade.php` are untouched.

---

### Task 1: Shared `peso()` currency formatter

**Files:**
- Create: `resources/js/utils/format.js`
- Modify: `resources/js/clearance/AccountingCard.jsx:1`

**Interfaces:**
- Produces: `peso(n: number | null | undefined): string` — e.g. `peso(1500)` → `"₱ 1,500.00"`. Used by Task 4 (`BalanceCard.jsx`) and Task 2 (`PaymentHistoryCard.jsx`).

- [ ] **Step 1: Create the shared formatter**

Create `resources/js/utils/format.js`:

```js
export const peso = (n) => `₱ ${Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
```

- [ ] **Step 2: Update `AccountingCard.jsx` to use the shared formatter**

In `resources/js/clearance/AccountingCard.jsx`, replace line 1:

```js
const peso = (n) => `₱ ${Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
```

with:

```js
import { peso } from '../utils/format';
```

- [ ] **Step 3: Verify the build compiles and existing clearance tests still pass**

Run: `npm run build`
Expected: exits 0, no errors.

Run: `php artisan test --filter=Clearance`
Expected: all pass (no behavior change — `AccountingCard` renders identically).

- [ ] **Step 4: Commit**

```bash
git add resources/js/utils/format.js resources/js/clearance/AccountingCard.jsx
git commit -m "refactor: hoist peso() currency formatter to a shared util"
```

---

### Task 2: `PaymentHistoryCard` component

**Files:**
- Create: `resources/js/payment/PaymentHistoryCard.jsx`

**Interfaces:**
- Consumes: `peso` from `resources/js/utils/format.js` (Task 1).
- Produces: `PaymentHistoryCard({ history })` where `history` is an array of `{ referenceNo: string, gatewayLabel: string, createdAtFormatted: string, amount: number, status: string }`. Renders `null` when `history` is empty. Consumed by Task 5 (`payment-app.jsx`).

- [ ] **Step 1: Create the component**

Create `resources/js/payment/PaymentHistoryCard.jsx`:

```jsx
import { peso } from '../utils/format';

const STATUS_STYLES = {
    Settled: 'bg-brandGreen/10 text-brandGreen border-brandGreen/20',
    Pending: 'bg-brandGold/10 text-brandGold border-brandGold/20',
    Failed: 'bg-red-600/10 text-red-600 border-red-600/20',
};

export default function PaymentHistoryCard({ history }) {
    if (!history || history.length === 0) return null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-clock-rotate-left mr-2" />Payment History
                </span>
            </div>
            <div className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                {history.map((row) => (
                    <div key={row.referenceNo} className="px-6 py-3.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div>
                            <span className="font-mono font-bold text-brandNavy dark:text-slate-200 block">{row.referenceNo}</span>
                            <span className="text-brandNavy/50 dark:text-slate-500">{row.gatewayLabel} · {row.createdAtFormatted}</span>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-black text-brandNavy dark:text-slate-200">{peso(row.amount)}</span>
                            <span className={`inline-flex items-center px-3 py-1 rounded text-[10px] font-bold border uppercase tracking-wider ${STATUS_STYLES[row.status] ?? 'bg-slate-500/10 text-slate-500 border-slate-500/20'}`}>
                                {row.status}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Verify the build compiles**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 3: Commit**

```bash
git add resources/js/payment/PaymentHistoryCard.jsx
git commit -m "feat: add PaymentHistoryCard component for payment island"
```

---

### Task 3: `ClearanceStatusCard` component

**Files:**
- Create: `resources/js/payment/ClearanceStatusCard.jsx`

**Interfaces:**
- Produces: `ClearanceStatusCard({ cashierCleared, registrarCleared, chairCleared })` (all booleans). Consumed by Task 5 (`payment-app.jsx`).

- [ ] **Step 1: Create the component**

Create `resources/js/payment/ClearanceStatusCard.jsx`:

```jsx
function StatusRow({ cleared, unclearedColor, label }) {
    return (
        <div className="flex items-center space-x-3">
            <i className={`fa-solid ${cleared ? 'fa-circle-check text-brandGreen' : `fa-circle-xmark ${unclearedColor}`} text-sm`} />
            <p className="text-brandNavy/70 dark:text-slate-400">{label}</p>
        </div>
    );
}

export default function ClearanceStatusCard({ cashierCleared, registrarCleared, chairCleared }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-file-invoice mr-2" />Clearance Status Overview
                </span>
            </div>
            <div className="p-6 space-y-3 text-xs">
                <StatusRow cleared={cashierCleared} unclearedColor="text-brandGold" label="Accounting Office — Balance Assessment" />
                <div className="flex items-center space-x-3">
                    <i className="fa-solid fa-circle-check text-brandGreen text-sm" />
                    <p className="text-brandNavy/70 dark:text-slate-400">Library — No pending borrowed items on record</p>
                </div>
                <StatusRow cleared={registrarCleared} unclearedColor="text-red-500" label="Registrar — Administrative document verification" />
                <StatusRow cleared={chairCleared} unclearedColor="text-brandGold" label="Department Head — Curriculum evaluation" />
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Verify the build compiles**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 3: Commit**

```bash
git add resources/js/payment/ClearanceStatusCard.jsx
git commit -m "feat: add ClearanceStatusCard component for payment island"
```

---

### Task 4: `BalanceCard` component

**Files:**
- Create: `resources/js/payment/BalanceCard.jsx`

**Interfaces:**
- Consumes: `peso` from `resources/js/utils/format.js` (Task 1).
- Produces: `BalanceCard({ settled, breakdown, hasPendingGateway, checkoutUrl, verifyUrl, csrfToken })`, where `breakdown = { units, rate, tuition, discount_name, discount_percent, discount_amount, misc, assessment, paid, balance, fully_paid }`. Consumed by Task 5 (`payment-app.jsx`).

- [ ] **Step 1: Create the component**

Create `resources/js/payment/BalanceCard.jsx`:

```jsx
import { peso } from '../utils/format';

export default function BalanceCard({ settled, breakdown, hasPendingGateway, checkoutUrl, verifyUrl, csrfToken }) {
    const b = breakdown ?? {};
    const hasDiscount = (b.discount_amount ?? 0) > 0;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-wallet mr-2" />Account Balance
                </span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
            </div>
            <div className="p-6 lg:p-8 flex flex-col md:flex-row items-start justify-between gap-6">
                <div className="space-y-2 text-center md:text-left">
                    {settled ? (
                        <>
                            <p className="text-[10px] font-bold text-brandGreen uppercase tracking-widest">Outstanding Balance</p>
                            <h3 className="text-4xl font-black text-brandGreen">₱ 0.00</h3>
                            <p className="text-xs text-brandNavy/60 dark:text-slate-400">Your account has been fully settled with the Accounting Office.</p>
                        </>
                    ) : (
                        <>
                            <p className="text-[10px] font-bold text-brandGold uppercase tracking-widest">Outstanding Balance</p>
                            <h3 className="text-4xl font-black text-brandGold dark:text-amber-400">{peso(b.balance)}</h3>
                            <p className="text-xs text-brandNavy/60 dark:text-slate-400">Settle your balance to clear the cashier hold before enrollment.</p>
                        </>
                    )}

                    <div className="mt-4 bg-lightBg dark:bg-slate-900/40 border border-brandNavy/5 dark:border-slate-800 rounded-xl p-4 text-xs space-y-1.5 w-full md:w-80">
                        <p className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Assessment Breakdown</p>
                        <div className="flex justify-between">
                            <span className="text-brandNavy/60 dark:text-slate-400">Tuition ({b.units} units × {peso(b.rate)})</span>
                            <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.tuition)}</span>
                        </div>
                        {hasDiscount && (
                            <div className="flex justify-between text-brandGreen">
                                <span>{b.discount_name} (−{b.discount_percent}% tuition)</span>
                                <span className="font-bold">− {peso(b.discount_amount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-brandNavy/60 dark:text-slate-400">Miscellaneous Fee</span>
                            <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.misc)}</span>
                        </div>
                        <div className="flex justify-between pt-1.5 border-t border-brandNavy/10 dark:border-slate-800">
                            <span className="text-brandNavy/60 dark:text-slate-400">Total Assessment</span>
                            <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.assessment)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-brandNavy/60 dark:text-slate-400">Payments Made</span>
                            <span className="font-bold text-brandGreen">− {peso(b.paid)}</span>
                        </div>
                    </div>
                </div>
                <div className="flex flex-col gap-3 w-full md:w-auto">
                    {settled ? (
                        <button disabled className="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-lightBg text-brandNavy/40 dark:bg-slate-800 dark:text-slate-500 font-bold rounded-xl text-xs uppercase tracking-wider cursor-not-allowed border border-brandNavy/10 dark:border-slate-700">
                            <i className="fa-solid fa-circle-check" />Account Settled
                        </button>
                    ) : (
                        !hasPendingGateway && (
                            <form action={checkoutUrl} method="POST">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md hover:shadow-brandGreen/25 hover:-translate-y-0.5 active:translate-y-0">
                                    <i className="fa-solid fa-credit-card" />Pay {peso(b.balance)} via PayMongo
                                </button>
                            </form>
                        )
                    )}
                    {hasPendingGateway && (
                        <>
                            <form action={verifyUrl} method="POST">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-brandGold/10 hover:bg-brandGold text-brandGold hover:text-white border border-brandGold/30 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors">
                                    <i className="fa-solid fa-rotate" />Verify Payment
                                </button>
                            </form>
                            <p className="text-[10px] text-brandNavy/50 dark:text-slate-500 text-center max-w-48">Finished paying on the gateway but the balance did not update? Verify here.</p>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Verify the build compiles**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 3: Commit**

```bash
git add resources/js/payment/BalanceCard.jsx
git commit -m "feat: add BalanceCard component for payment island"
```

---

### Task 5: `payment-app.jsx` entry point + Vite wiring

**Amendment (discovered during implementation):** this task's brief and the design spec both assumed `resources/js/components/ErrorBoundary.jsx` already existed and was used by all 5 committed islands. It does not — `git log --all` for that path returns nothing, and the actually-committed `clearance-app.jsx`/`dashboard-app.jsx`/etc. import no such thing. (Root cause: earlier brainstorming read uncommitted working-tree state in a different checkout and mistook it for committed history.) Per explicit user decision, this task now also creates a minimal `ErrorBoundary.jsx` from scratch as Step 0 below, rather than dropping the wrapper from this island.

**Files:**
- Create: `resources/js/components/ErrorBoundary.jsx`
- Create: `resources/js/payment-app.jsx`
- Modify: `vite.config.js`

**Interfaces:**
- Consumes: `BalanceCard` (Task 4), `PaymentHistoryCard` (Task 2), `ClearanceStatusCard` (Task 3), `ErrorBoundary` from `resources/js/components/ErrorBoundary.jsx` (new, this task).
- Produces: mounts to `#payment-root`, reading `data-context` (JSON), `data-csrf-token`, `data-checkout-url`, `data-verify-url` off that element. Consumed by Task 6 (`payment.blade.php`). `ErrorBoundary` is also available for other islands to adopt later (out of scope for this task).

- [ ] **Step 0: Create the ErrorBoundary component**

Create `resources/js/components/ErrorBoundary.jsx`:

```jsx
import { Component } from 'react';

export default class ErrorBoundary extends Component {
    state = { hasError: false };

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, info) {
        console.error('React island crashed:', error, info);
    }

    render() {
        if (this.state.hasError) {
            return (
                <p className="text-sm text-red-600 p-4">
                    Something went wrong loading this section. Please refresh the page.
                </p>
            );
        }
        return this.props.children;
    }
}
```

- [ ] **Step 1: Create the entry point**

Create `resources/js/payment-app.jsx`:

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import BalanceCard from './payment/BalanceCard';
import PaymentHistoryCard from './payment/PaymentHistoryCard';
import ClearanceStatusCard from './payment/ClearanceStatusCard';

const EMPTY_BREAKDOWN = {
    units: 0, rate: 0, tuition: 0, discount_name: null, discount_percent: 0,
    discount_amount: 0, misc: 0, assessment: 0, paid: 0, balance: 0, fully_paid: false,
};

const EMPTY_CONTEXT = {
    settled: true,
    hasPendingGateway: false,
    breakdown: EMPTY_BREAKDOWN,
    history: [],
    cashierCleared: false,
    registrarCleared: false,
    chairCleared: false,
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            settled: !!parsed.settled,
            hasPendingGateway: !!parsed.hasPendingGateway,
            breakdown: parsed.breakdown ?? EMPTY_BREAKDOWN,
            history: Array.isArray(parsed.history) ? parsed.history : [],
            cashierCleared: !!parsed.cashierCleared,
            registrarCleared: !!parsed.registrarCleared,
            chairCleared: !!parsed.chairCleared,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function PaymentApp({ context, csrfToken, checkoutUrl, verifyUrl }) {
    return (
        <div className="space-y-6">
            <BalanceCard
                settled={context.settled}
                breakdown={context.breakdown}
                hasPendingGateway={context.hasPendingGateway}
                checkoutUrl={checkoutUrl}
                verifyUrl={verifyUrl}
                csrfToken={csrfToken}
            />
            <PaymentHistoryCard history={context.history} />
            <ClearanceStatusCard
                cashierCleared={context.cashierCleared}
                registrarCleared={context.registrarCleared}
                chairCleared={context.chairCleared}
            />
        </div>
    );
}

const el = document.getElementById('payment-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const checkoutUrl = el.dataset.checkoutUrl ?? '';
    const verifyUrl = el.dataset.verifyUrl ?? '';
    createRoot(el).render(
        <ErrorBoundary>
            <PaymentApp context={context} csrfToken={csrfToken} checkoutUrl={checkoutUrl} verifyUrl={verifyUrl} />
        </ErrorBoundary>
    );
}
```

- [ ] **Step 2: Add the entry to Vite's input array**

In `vite.config.js`, add `'resources/js/payment-app.jsx'` to the `input` array (alongside the existing `clearance-app.jsx`, `schedule-app.jsx`, etc. entries).

- [ ] **Step 3: Verify the build compiles and produces the new asset**

Run: `npm run build`
Expected: exits 0; output lists a compiled asset for `payment-app`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/payment-app.jsx vite.config.js
git commit -m "feat: add payment-app.jsx island entry point"
```

---

### Task 6: Wire `payment.blade.php` to the island + island test + update existing tests

**Files:**
- Modify: `resources/views/payment.blade.php:70-202` (the Balance Overview, Payment History, and Clearance Status Summary blocks)
- Create: `tests/Feature/PaymentIslandTest.php`
- Modify: `tests/Feature/LedgerPageTest.php`

**Interfaces:**
- Consumes: `payment-app.jsx` (Task 5), route names `ledger.checkout`/`ledger.verify` (existing, unchanged in this task).

- [ ] **Step 1: Write the failing island test**

Create `tests/Feature/PaymentIslandTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_page_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/ledger');

        $response->assertOk();
        $response->assertSee('id="payment-root"', false);
        $response->assertDontSee('Assessment Breakdown');
        $response->assertDontSee('Clearance Status Overview');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/ledger')->assertRedirect();
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=PaymentIslandTest`
Expected: FAIL — `id="payment-root"` not found (the mount point doesn't exist yet).

- [ ] **Step 3: Replace the static card markup in `payment.blade.php` with the island mount**

In `resources/views/payment.blade.php`, replace everything from the `{{-- BALANCE OVERVIEW CARD --}}` comment (line 70) through the closing `</div>` of the `{{-- CLEARANCE STATUS SUMMARY --}}` block (line 202) with:

```blade
@php
    $paymentContext = [
        'settled' => ($breakdown['balance'] ?? 0) <= 0,
        'hasPendingGateway' => $hasPendingGateway,
        'breakdown' => $breakdown,
        'history' => $history->map(fn ($row) => [
            'referenceNo' => $row->reference_no,
            'gatewayLabel' => $row->gateway === 'paymongo' ? 'PayMongo (online)' : 'Cashier window',
            'createdAtFormatted' => $row->created_at->format('M d, Y g:i A'),
            'amount' => $row->amount,
            'status' => $row->status,
        ])->all(),
        'cashierCleared' => isset($clearance) && $clearance->cashier_status === 'Approved',
        'registrarCleared' => isset($clearance) && $clearance->registrar_status === 'Approved',
        'chairCleared' => isset($clearance) && $clearance->chair_status === 'Approved',
    ];
@endphp

<div id="payment-root"
     data-context="{{ json_encode($paymentContext) }}"
     data-csrf-token="{{ csrf_token() }}"
     data-checkout-url="{{ route('ledger.checkout') }}"
     data-verify-url="{{ route('ledger.verify') }}">
    <p class="text-sm text-slate-500">Loading…</p>
</div>
```

Then, immediately after the existing `@include('partials.notif-script')` line (near the bottom of `<body>`), add:

```blade
@viteReactRefresh
@vite('resources/js/payment-app.jsx')
```

- [ ] **Step 4: Run the island test to verify it passes**

Run: `php artisan test --filter=PaymentIslandTest`
Expected: PASS.

- [ ] **Step 5: Bust the compiled Blade view cache**

Run: `php artisan view:clear`

- [ ] **Step 6: Run the existing `LedgerPageTest` to see it now fail**

Run: `php artisan test --filter=LedgerPageTest`
Expected: FAIL — `assertSee('Assessment Breakdown')` and `assertSee('Verify Payment')` no longer appear in server-rendered HTML (that markup now lives in React).

- [ ] **Step 7: Update `LedgerPageTest` to assert against `data-context` instead of visible text**

In `tests/Feature/LedgerPageTest.php`, replace `test_ledger_shows_breakdown_and_own_history_only`'s assertions:

```php
$response->assertOk()
    ->assertSee('Assessment Breakdown')
    ->assertSee('Miscellaneous Fee')
    ->assertSee('PMG-MINE123456')
    ->assertDontSee('PMG-OTHERS7890')
    ->assertDontSee('mockPay');
```

with:

```php
$response->assertOk()
    ->assertSee('id="payment-root"', false)
    ->assertSee('"referenceNo":"PMG-MINE123456"', false)
    ->assertDontSee('PMG-OTHERS7890')
    ->assertDontSee('mockPay');
```

And replace `test_pending_gateway_row_shows_verify_button`'s assertions:

```php
$this->actingAs($student)->get('/ledger')
    ->assertOk()
    ->assertSee('Verify Payment')
    ->assertDontSee('via PayMongo'); // Pay is hidden until the pending payment is verified or cancelled
```

with:

```php
$this->actingAs($student)->get('/ledger')
    ->assertOk()
    ->assertSee('"hasPendingGateway":true', false);
```

- [ ] **Step 8: Run `LedgerPageTest` to verify it passes**

Run: `php artisan test --filter=LedgerPageTest`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add resources/views/payment.blade.php tests/Feature/PaymentIslandTest.php tests/Feature/LedgerPageTest.php
git commit -m "feat: mount payment-app.jsx island on the ledger page"
```

---

### Task 7: Rate-limit checkout, verify, and return routes

**Files:**
- Modify: `routes/web.php:146,163,179` (the `ledger.checkout`, `ledger.payment.return`, `ledger.verify` route definitions)
- Create: `tests/Feature/PaymentThrottleTest.php`

- [ ] **Step 1: Write the failing throttle tests**

Create `tests/Feature/PaymentThrottleTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithBalance(): User
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);

        return $student;
    }

    public function test_checkout_is_rate_limited_after_six_attempts_per_minute(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $student = $this->studentWithBalance();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($student)->post('/ledger/checkout');
        }

        $this->actingAs($student)->post('/ledger/checkout')->assertStatus(429);
    }

    public function test_verify_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Pending',
            'gateway' => 'paymongo', 'checkout_session_id' => 'cs_test_throttle',
        ]);
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_test_throttle' => Http::response([
                'data' => ['id' => 'cs_test_throttle', 'attributes' => ['payments' => []]],
            ], 200),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->post('/ledger/verify');
        }

        $this->actingAs($student)->post('/ledger/verify')->assertStatus(429);
    }

    public function test_payment_return_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Pending',
            'gateway' => 'paymongo', 'checkout_session_id' => 'cs_test_throttle',
        ]);
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_test_throttle' => Http::response([
                'data' => ['id' => 'cs_test_throttle', 'attributes' => ['payments' => []]],
            ], 200),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->get('/ledger/payment/return');
        }

        $this->actingAs($student)->get('/ledger/payment/return')->assertStatus(429);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PaymentThrottleTest`
Expected: FAIL — all three requests return `302` (redirect), not `429` (no throttle middleware applied yet).

- [ ] **Step 3: Add throttle middleware to the routes**

In `routes/web.php`, chain `->middleware('throttle:6,1')` onto the `ledger.checkout` route definition (around line 146-161) and `->middleware('throttle:10,1')` onto both the `ledger.payment.return` (around line 163-171) and `ledger.verify` (around line 179-187) route definitions. For example:

```php
Route::post('/ledger/checkout', function (FeeAssessmentService $fees, PaymentService $payments) {
    // ...unchanged body...
})->name('ledger.checkout')->middleware('throttle:6,1');
```

```php
Route::get('/ledger/payment/return', function (PaymentService $payments) {
    // ...unchanged body...
})->name('ledger.payment.return')->middleware('throttle:10,1');
```

```php
Route::post('/ledger/verify', function (PaymentService $payments) {
    // ...unchanged body...
})->name('ledger.verify')->middleware('throttle:10,1');
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=PaymentThrottleTest`
Expected: PASS.

- [ ] **Step 5: Run the existing checkout/return/verify test suites to confirm no regressions**

Run: `php artisan test --filter=PaymentCheckoutTest`
Run: `php artisan test --filter=PaymentReturnTest`
Expected: both PASS (each test method makes only 1-2 requests, well under the new limits).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php tests/Feature/PaymentThrottleTest.php
git commit -m "feat: rate-limit checkout, verify, and payment-return routes"
```

---

### Task 8: Atomic checkout creation (prevent duplicate concurrent Pending rows)

**Files:**
- Modify: `app/Services/PaymentService.php:25-54` (`startCheckout`)

**Interfaces:**
- Consumes: `Illuminate\Support\Facades\Cache` (Laravel built-in; the app's configured `CACHE_STORE=file` supports atomic locks).

- [ ] **Step 1: Add the `Cache` import**

In `app/Services/PaymentService.php`, add to the `use` statements at the top:

```php
use Illuminate\Support\Facades\Cache;
```

- [ ] **Step 2: Wrap the cancel-then-create sequence in a per-user lock**

Replace the first two statements of `startCheckout()` (the stale-row cancellation and new-row creation):

```php
public function startCheckout(User $user, float $balance): string
{
    TransactionLedger::where('user_id', $user->id)
        ->where('gateway', 'paymongo')->where('status', 'Pending')
        ->update(['status' => 'Cancelled']);

    $row = TransactionLedger::create([
        'user_id' => $user->id,
        'reference_no' => 'PMG-' . strtoupper(Str::random(10)),
        'amount' => $balance,
        'status' => 'Pending',
        'gateway' => 'paymongo',
        'remarks' => 'Online payment via PayMongo checkout.',
    ]);
```

with:

```php
public function startCheckout(User $user, float $balance): string
{
    $row = Cache::lock("ledger-checkout-{$user->id}", 10)->block(5, function () use ($user, $balance) {
        TransactionLedger::where('user_id', $user->id)
            ->where('gateway', 'paymongo')->where('status', 'Pending')
            ->update(['status' => 'Cancelled']);

        return TransactionLedger::create([
            'user_id' => $user->id,
            'reference_no' => 'PMG-' . strtoupper(Str::random(10)),
            'amount' => $balance,
            'status' => 'Pending',
            'gateway' => 'paymongo',
            'remarks' => 'Online payment via PayMongo checkout.',
        ]);
    });
```

The rest of the method (the `try`/`catch` around `$this->gateway->createCheckoutSession(...)` and the final `$row->update([...])`) is unchanged — it stays outside the lock so the external gateway call never holds the mutex.

- [ ] **Step 3: Run the existing checkout tests to confirm no regressions**

Run: `php artisan test --filter=PaymentCheckoutTest`
Expected: PASS — `test_checkout_creates_pending_row_and_redirects_to_gateway`,
`test_new_checkout_cancels_stale_pending_rows`, and
`test_gateway_failure_marks_row_failed_with_error_flash` all still pass, confirming the
lock wrapping didn't change observable behavior for sequential requests.

Note: a single-process PHPUnit test cannot literally simulate two truly concurrent HTTP
requests, so this task's verification is a regression check, not a concurrency proof —
the lock is defensive hardening against real concurrent double-submits (double-click,
duplicate tab), consistent with the design spec.

- [ ] **Step 4: Commit**

```bash
git add app/Services/PaymentService.php
git commit -m "fix: guard checkout creation with a per-user lock to prevent duplicate Pending rows"
```

---

### Task 9: Full verification pass

**Files:** none (verification only)

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test`
Expected: all tests pass, no regressions anywhere in the suite.

- [ ] **Step 2: Rebuild frontend assets**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 3: Authenticated HTTP-level spot check**

Using the same authenticated-curl approach used for the prior islands (extract CSRF
token from `/login`, POST credentials with a cookie jar, GET protected pages with the
same jar), authenticate as the demo regular student `2300410` / `password` and `GET
/ledger`. Confirm:
- HTTP 200, `id="payment-root"` present.
- A `payment-app-*.js` Vite asset referenced.
- `data-context` contains real breakdown data for this student (non-null `balance`,
  `assessment` fields).
- Neither `Assessment Breakdown` nor `Clearance Status Overview` appear as raw
  server-rendered headings (confirming they now come from the React island).

- [ ] **Step 4: Manual browser verification**

Using the Chrome browser automation tools, log in as `2300410` / `password` and visit
`/ledger`. Confirm the balance card, payment history (if any), and clearance status card
render correctly in both light and dark mode; that the "Pay via PayMongo" button (or
"Account Settled"/"Verify Payment", depending on this student's current balance state)
renders as a real form pointing at the correct route; and zero console errors. Do not
actually submit the checkout form against the live PayMongo sandbox unless intentionally
testing that flow.

- [ ] **Step 5: Commit** (only if any fixups were needed during verification; otherwise skip)
