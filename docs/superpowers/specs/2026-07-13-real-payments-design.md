# Real Payments (PayMongo Sandbox) — Design

**Date:** 2026-07-13
**Status:** Approved

## Overview

Replaces the ledger page's fake payment flow with a real one. Today `/ledger` shows a
hardcoded ₱3,500 balance and a mock modal whose Success/Fail buttons post to
`/ledger/mock-pay`, which flips `cashier_status` without recording anything. After this
feature, a student's balance is computed from real fee rates and their planned subject
load, checkout happens on PayMongo's hosted test-mode page (GCash/card/Maya), every
payment is a `TransactionLedger` row, and a verified full payment auto-approves the
cashier clearance. This closes the capstone paper's "payments via gateway" gap
(DocuSign e-signatures remain a separate open gap).

## Decisions made during brainstorming

- **Real PayMongo sandbox** (test mode), not an in-app simulation — hosted **Checkout
  Session** integration style.
- **Per-unit tuition + fixed misc fee**, admin-configurable via `settings`.
- Assessment is based on the **planned load for the term** (payment happens before
  enrollment, since the cashier signature gates enrollment).
- **Full balance only** at checkout — no partial payments.
- **Auto-approve `cashier_status`** when the balance reaches zero via a
  gateway-verified payment. Cashier walk-in flow (`approveClearance`) is untouched.
- Payment is treated as real **only after server-side verification** (retrieve the
  checkout session from the PayMongo API); the success redirect alone proves nothing.
- No webhooks — impractical on localhost XAMPP. Verification happens on the return
  redirect, with a manual "Verify payment" fallback button.

## Fee assessment — `FeeAssessmentService`

Answers "what does this student owe?".

**Rates** (in `settings`, seeded defaults, admin-editable):

| key | default | meaning |
|---|---|---|
| `tuition_per_unit` | `300` | pesos per subject unit |
| `misc_fee` | `1500` | flat miscellaneous/registration fee per term |

**Planned units** for a student, first match wins:

1. Active enrollment this term (`EnrollmentService::activeEnrollment`, status not
   rejected) → sum of units of the enrolled sections' subjects.
2. Regular student → units of the subjects in `EnrollmentService::blockFor($user)`.
3. Irregular student → units of the `eligible === true` subjects in
   `EnrollmentService::catalogueFor($user)`.
4. No program / nothing found → 0 units (misc fee still applies, so every demo student
   has something payable).

**Amounts:**

- `assessment = plannedUnits × tuition_per_unit + misc_fee`
- `paid` = sum of the student's `Settled` rows in `transaction_ledgers`
- `balance = max(assessment − paid, 0)`
- `isFullyPaid()` = `balance === 0 && assessment > 0`

The service returns a breakdown array (units, rate, misc, assessment, paid, balance)
consumed by the ledger page and the checkout route.

## PayMongo integration — `PayMongoService`

`config/services.php` gains a `paymongo` entry reading `PAYMONGO_SECRET_KEY` from
`.env` (`.env.example` documents it; keys never committed; free dashboard account
provides `sk_test_…` keys). Uses Laravel's `Http` client with basic auth
(secret key as username), base `https://api.paymongo.com/v1`.

- `createCheckoutSession(User $user, int $amountCentavos, string $description): array`
  → `POST /checkout_sessions`. One line item for the full balance (amounts in
  centavos), `payment_method_types: ['gcash', 'card', 'paymaya']`,
  `success_url` → `route('ledger.payment.return')`, `cancel_url` →
  `route('ledger.payment.cancel')`, metadata `user_id` + `ledger_id`.
  Returns `['id' => …, 'checkout_url' => …]`.
- `retrieveCheckoutSession(string $id): array` → `GET /checkout_sessions/{id}`.
  Caller inspects `attributes.payments` for a `paid` payment.
- API errors throw a `PaymentGatewayException` (message safe to flash).

## Data model

One migration adding nullable columns to the existing `transaction_ledgers`:

| column | type | notes |
|---|---|---|
| `gateway` | string 20 nullable | `paymongo` for online payments; null for cashier rows |
| `checkout_session_id` | string nullable, indexed | PayMongo session id |
| `paid_at` | timestamp nullable | set on verified settlement |

Statuses used by this feature: `Pending`, `Settled`, `Cancelled`, `Failed` (existing
cashier rows use `Settled` and keep working). `processed_by` stays null for gateway
rows.

## Routes (student, inside the existing auth group)

- `POST /ledger/checkout` (`ledger.checkout`) — recompute balance server-side; if ₱0,
  redirect back with an info flash. Cancel any of the student's stale `Pending`
  gateway rows. Create a `Pending` row (generated reference no, amount = balance,
  `gateway = paymongo`), call `createCheckoutSession`, store the session id, redirect
  to the hosted `checkout_url`. If the API call fails, mark the row `Failed` and flash
  "Payment gateway is unavailable — please try again or pay at the cashier window."
- `GET /ledger/payment/return` (`ledger.payment.return`) — find the student's latest
  `Pending` gateway row; retrieve its session; if paid → mark `Settled` + `paid_at`,
  audit `Payment Settled`, then recompute the balance and, only if
  `isFullyPaid()`, set `cashier_status = 'Approved'` with audit
  `Cashier Cleared (Gateway)`; redirect to `/ledger` with a success flash.
  If unpaid → leave Pending, error flash. Idempotent: a row already `Settled` and a
  clearance already `Approved` are never re-processed.
- `GET /ledger/payment/cancel` (`ledger.payment.cancel`) — mark the latest `Pending`
  gateway row `Cancelled`, info flash.
- `POST /ledger/verify` (`ledger.verify`) — manual re-run of the return verification
  (for students who closed the tab mid-payment). Same logic, same idempotence.
- `POST /ledger/mock-pay` and its Blade modal are **removed**.

## Admin fees endpoint

`POST /api/admin/settings/fees` (existing `auth:sanctum` + `role:admin` API group):
validates `tuition_per_unit` and `misc_fee` as `required|integer|min:0`, writes both
settings, audit-logs `Fees Updated`. The curriculum editor's settings area gains a
small "Fees" card with the two inputs and a save button, next to the
change-matriculation toggle.

## Student ledger page (`payment.blade.php`)

- Balance card shows the real breakdown: planned units × rate, misc fee, total
  assessment, payments made, outstanding balance; green ₱0.00 fully-settled state.
- Replace the mock modal with one **"Pay ₱X via PayMongo"** button (form POST to
  `ledger.checkout`); show a **"Verify payment"** button when a `Pending` gateway row
  exists.
- Payment history list: the student's own ledger rows — reference no, amount, gateway
  or "Cashier", status badge (amber Pending / green Settled / slate Cancelled / red
  Failed), date.
- Brand colors and dark-mode variants as project-standard (brandNavy `#0B3C5D`,
  brandGreen `#1D7A46`, brandGold `#E2A700`).

## Notifications (notif bell, fields escaped by existing `escNotif()`)

- **Student:** latest gateway row — Settled: green "Payment received — ₱X settled;
  cashier clearance approved."; Pending: amber "Payment awaiting verification — use
  Verify Payment on your ledger."
- **Cashier:** count of gateway rows settled today — "N online payment(s) received
  today" (only when N > 0).

## Error handling

- Missing/invalid API keys or PayMongo unreachable → `PaymentGatewayException` →
  row `Failed`, friendly error flash, no state change elsewhere.
- Verification finds session unpaid → row stays `Pending`, error flash.
- ₱0 balance checkout attempt → info flash, no row.
- Double-settlement: settled rows and approved clearances are skipped, never
  re-processed (safe to refresh the return URL).

## Testing

PHPUnit, RefreshDatabase, `Http::fake()` for all PayMongo calls (dummy key via config
in tests; no real network):

- `FeeAssessmentService`: regular block load, irregular eligible load,
  enrolled-load override, payments subtracted, zero floor, defaults when no program.
- Checkout: creates Pending row with correct centavo amount + redirects to faked
  checkout URL; ₱0 balance blocked; stale Pending rows cancelled; API failure marks
  row Failed with error flash; guest redirected.
- Return/verify: paid session → Settled + `paid_at` + cashier Approved + both audit
  logs; unpaid session → stays Pending; cancel → Cancelled; repeat visit is a no-op.
- Admin fees: admin updates rates (settings persisted + audit log), student gets 403,
  validation rejects negatives.
- Ledger page: shows breakdown and own history only.

## Demo setup (documented, one-time)

1. Create a free PayMongo account → Developers → copy the **test** secret key.
2. Add `PAYMONGO_SECRET_KEY=sk_test_…` to `.env`.
3. Demo needs internet. Test GCash/card flows are simulated by PayMongo's hosted page
   (test card `4343 4343 4343 4345`, any future expiry, any CVC).

## Out of scope (explicitly)

- Webhooks (localhost cannot receive them; verification is pull-based).
- Partial payments, refunds, installment plans.
- PDF receipts.
- DocuSign e-signatures (separate open paper gap).
- Production/live keys or real money.
