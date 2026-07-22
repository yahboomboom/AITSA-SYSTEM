# Clearance React Island Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert `resources/views/clearance.blade.php`'s content area (status badge, accounting card, registrar/hold card, submitted-documents history, upload modal) into a React island (`clearance-app.jsx`), with no change to server-side data, routes, or the upload endpoint's behavior.

**Architecture:** Five components under `resources/js/clearance/` (`MasterStatusBadge`, `AccountingCard`, `RegistrarCard`, `SubmittedDocumentsList`, `SubmitRequirementModal`), mounted by a `clearance-app.jsx` entry into a `#clearance-root` div that replaces the current inline markup (including the modal, which mounts as part of the same React tree instead of a separate Blade block). Sidebar, header, and flash messages stay Blade.

**Tech Stack:** React 18, Vite (`laravel-vite-plugin`, `@vitejs/plugin-react`), Blade, PHPUnit.

## Global Constraints

- The hardcoded fake fee-items list (`Reservation Fee`, `Tuition Fee — 1st..5th Payment`, etc.) is preserved verbatim as a static array in `AccountingCard.jsx` — do not wire it to `FeeAssessmentService` or any real ledger data.
- The `#enrollmentShortcut` "click here to proceed to enrollment" banner is preserved verbatim as permanently hidden (`className="hidden ..."`) — do not wire it up to show conditionally.
- **Exception — fix this one pre-existing typo while porting:** the per-item fee amount currently displays a literal `?` instead of a peso sign (₱) in `clearance.blade.php:243`. `AccountingCard.jsx` must use `₱` for these amounts (the balance heading above already correctly uses ₱; this is a one-character parity fix, not new scope).
- The document-upload modal keeps its native browser form POST — a real `<form action={submitUrl} method="POST" encType="multipart/form-data">` submitted via `formRef.current.submit()`, same full-page-reload + flash-message behavior as today. Do not convert to fetch/AJAX. Do not modify `clearance.submitRequirement`.
- All "business logic" (status booleans, date formatting, `documents.show` route URLs) is computed server-side in Blade's `@php` block and passed as already-computed values in the `data-context` JSON — do not re-derive any of this in JS.
- No change to the `/clearance` route closure, `Clearance::initializeFor()`, `clearance.submitRequirement`, or any registrar/chair/cashier hold/sign/approve route.
- Sidebar, `<header>` (notif-bell/theme-toggle/profile-menu), the flash message blocks (`session('success')`, `$errors->any()`), the `drop-zone`/`modal-enter` `<style>` block in `<head>`, and `@include('partials.notif-script')` must NOT be touched.
- No JS test runner in this repo — verification is `npm run build` succeeding, PHPUnit Feature tests, and an authenticated-curl HTTP-level check. Manual/visual browser verification is a known, explicitly-flagged gap.
- Follow the exact island-mounting convention already used by the other four islands: `const el = document.getElementById('clearance-root'); if (el) createRoot(el).render(<ClearanceApp .../>);`.

---

### Task 1: Create `MasterStatusBadge.jsx` and `AccountingCard.jsx`

**Files:**
- Create: `resources/js/clearance/MasterStatusBadge.jsx`
- Create: `resources/js/clearance/AccountingCard.jsx`

**Interfaces:**
- Produces: `MasterStatusBadge` (default export), props `{ isCleared, cashierCleared, registrarCleared, chairCleared, items }` where `items: Array<{ departmentName, status, remarks }>`.
- Produces: `AccountingCard` (default export), props `{ cashierCleared }`.
- Both consumed by `clearance-app.jsx` (Task 4).

- [ ] **Step 1: Create `resources/js/clearance/MasterStatusBadge.jsx`**

Ported from `resources/views/clearance.blade.php:131-172`. IDs (`masterBadgeCard`, `masterStatusBadge`, `checkIconAccounting`, `checkIconRegistrar`, `checkIconChair`) are unused by any JS in the original (confirmed via repo-wide search) but preserved for markup fidelity:

```jsx
export default function MasterStatusBadge({ isCleared, cashierCleared, registrarCleared, chairCleared, items }) {
    return (
        <div id="masterBadgeCard" className={`bg-white dark:bg-panelDark border ${isCleared ? 'border-brandGreen/30' : 'border-brandGold/20'} rounded-xl p-6 flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-8`}>
            <div id="masterStatusBadge" className="flex flex-col items-center text-center justify-center md:border-r border-brandNavy/10 dark:border-slate-800 pr-0 md:pr-8 flex-shrink-0 w-full md:w-44">
                {isCleared ? (
                    <>
                        <div className="w-12 h-12 rounded-full bg-brandGreen/10 text-brandGreen flex items-center justify-center text-2xl mb-2">
                            <i className="fa-solid fa-circle-check" />
                        </div>
                        <span className="text-sm font-black text-brandGreen uppercase tracking-wider">Officially Cleared</span>
                    </>
                ) : (
                    <>
                        <div className="w-12 h-12 rounded-full bg-brandGold/10 text-brandGold flex items-center justify-center text-2xl mb-2 animate-pulse">
                            <i className="fa-solid fa-circle-exclamation" />
                        </div>
                        <span className="text-sm font-black text-brandGold uppercase tracking-wider">Pending Sign-off</span>
                    </>
                )}
            </div>
            <div className="flex-1 text-xs space-y-2 w-full">
                <div className="flex items-start space-x-2">
                    <i id="checkIconAccounting" className={`fa-solid ${cashierCleared ? 'fa-circle-check text-brandGreen' : 'fa-circle-xmark text-brandGold'} mt-0.5`} />
                    <p className="text-brandNavy/70 dark:text-slate-400">Accounting Office — Balance assessment verification.</p>
                </div>
                <div className="flex items-start space-x-2">
                    <i id="checkIconRegistrar" className={`fa-solid ${registrarCleared ? 'fa-circle-check text-brandGreen' : 'fa-circle-xmark text-red-500'} mt-0.5`} />
                    <p className="text-brandNavy/70 dark:text-slate-400">Registrar — On-hold administrative document verification.</p>
                </div>
                <div className="flex items-start space-x-2">
                    <i id="checkIconChair" className={`fa-solid ${chairCleared ? 'fa-circle-check text-brandGreen' : 'fa-circle-xmark text-brandGold'} mt-0.5`} />
                    <p className="text-brandNavy/70 dark:text-slate-400">Department Head — Curriculum evaluation sign-off.</p>
                </div>
                {items.map((item, i) => (
                    <div key={i} className="flex items-start space-x-2">
                        <i className={`fa-solid ${item.status === 'Approved' ? 'fa-circle-check text-brandGreen' : item.status === 'Hold' ? 'fa-circle-xmark text-red-500' : 'fa-circle-xmark text-brandGold'} mt-0.5`} />
                        <p className="text-brandNavy/70 dark:text-slate-400">
                            {item.departmentName} —{' '}
                            {item.status === 'Approved' ? 'Cleared.' : item.status === 'Hold' ? `On hold: ${item.remarks}` : 'Pending review.'}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Create `resources/js/clearance/AccountingCard.jsx`**

Ported from `resources/views/clearance.blade.php:187-259`. The fake action-items array is copied verbatim (labels/paid/amount values unchanged) except the per-item amount now uses `₱` instead of the original's literal `?` character (the one deliberate fidelity exception noted in Global Constraints):

```jsx
const ACTION_ITEMS = [
    { label: 'Reservation Fee', paid: true, amount: '500.00' },
    { label: 'Tuition Fee — 1st Payment', paid: true, amount: '3,500.00' },
    { label: 'Tuition Fee — 2nd Payment', paid: false, amount: '3,500.00' },
    { label: 'Tuition Fee — 3rd Payment', paid: false, amount: '3,500.00' },
    { label: 'Tuition Fee — 4th Payment', paid: false, amount: '3,500.00' },
    { label: 'Tuition Fee — 5th Payment', paid: false, amount: '3,500.00' },
    { label: 'Acquaintance Party', paid: true, amount: '150.00' },
    { label: 'SportsFest', paid: false, amount: '200.00' },
    { label: 'Grad Ball (Graduating)', paid: false, amount: '500.00' },
];

export default function AccountingCard({ cashierCleared }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-credit-card mr-2" />Account Status</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
            </div>
            <div id="accountingCardBody" className="p-6 space-y-5">
                {cashierCleared ? (
                    <div className="text-center space-y-1">
                        <h3 className="text-xl font-black text-brandGreen">Balance: ₱ 0.00</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">Your account balance has been fully settled.</p>
                    </div>
                ) : (
                    <div className="text-center space-y-2">
                        <h3 className="text-xl font-black text-brandGold">Balance: Pending Assessment</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">You have pending tuition or institutional fee obligations. Settle the items below to complete your clearance.</p>
                    </div>
                )}

                <div className="border border-brandNavy/8 dark:border-slate-700 rounded-xl overflow-hidden">
                    <div className="px-4 py-2.5 bg-lightBg dark:bg-slate-800/50 border-b border-brandNavy/8 dark:border-slate-700 flex items-center justify-between">
                        <span className="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">Clearance Action Items</span>
                        <span className="text-[9px] font-black text-brandNavy/40 dark:text-slate-500">A.Y. 2025–2026</span>
                    </div>
                    <div className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        {ACTION_ITEMS.map((item) => (
                            <div key={item.label} className="flex items-center justify-between px-4 py-3 text-xs">
                                <div className="flex items-center gap-3">
                                    {item.paid ? (
                                        <>
                                            <div className="w-5 h-5 rounded-full bg-brandGreen/10 flex items-center justify-center flex-shrink-0" />
                                            <span className="font-medium text-brandNavy/60 dark:text-slate-400 line-through">{item.label}</span>
                                        </>
                                    ) : (
                                        <>
                                            <div className="w-5 h-5 rounded-full bg-brandGold/10 border border-brandGold/30 flex items-center justify-center flex-shrink-0" />
                                            <span className="font-semibold text-brandNavy dark:text-slate-200">{item.label}</span>
                                        </>
                                    )}
                                </div>
                                <div className="text-right flex-shrink-0 ml-4">
                                    {item.paid ? (
                                        <span className="text-[10px] font-bold text-brandGreen">Settled</span>
                                    ) : (
                                        <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400">₱ {item.amount}</span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {!cashierCleared && (
                    <div className="flex justify-end">
                        <a href="/ledger" className="inline-flex items-center gap-2 text-xs font-bold bg-brandGold hover:bg-yellow-500 text-brandNavy px-5 py-2.5 rounded-xl border border-brandGold/30 transition-all shadow-sm">
                            <i className="fa-solid fa-wallet" />Go to Payment
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}
```

(`href="/ledger"` is hardcoded, matching the existing convention of cross-page links from React islands — e.g. `enrollment-app.jsx` hardcodes `href="/clearance"`.)

- [ ] **Step 3: Commit**

```bash
git add resources/js/clearance/MasterStatusBadge.jsx resources/js/clearance/AccountingCard.jsx
git commit -m "feat: add MasterStatusBadge and AccountingCard React components for clearance island"
```

---

### Task 2: Create `RegistrarCard.jsx` and `SubmittedDocumentsList.jsx`

**Files:**
- Create: `resources/js/clearance/RegistrarCard.jsx`
- Create: `resources/js/clearance/SubmittedDocumentsList.jsx`

**Interfaces:**
- Produces: `RegistrarCard` (default export), props `{ registrarCleared, submission, onOpenModal }` where `submission: { hasSubmission, submissionPending, createdAt, originalName }` and `onOpenModal: (isResubmit: boolean) => void`.
- Produces: `SubmittedDocumentsList` (default export), props `{ submissions }` where `submissions: Array<{ typeLabel, documentsShowUrl, originalName, createdAtFormatted, status, remarks }>`.
- Both consumed by `clearance-app.jsx` (Task 4). `onOpenModal` will be wired there to open `SubmitRequirementModal` (Task 3).

- [ ] **Step 1: Create `resources/js/clearance/RegistrarCard.jsx`**

Ported from `resources/views/clearance.blade.php:261-326`. The `openSubmitModal(isResubmit)` global function calls become `onOpenModal(isResubmit)` prop calls:

```jsx
export default function RegistrarCard({ registrarCleared, submission, onOpenModal }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-ban mr-2" />On-Hold Record Status</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar</span>
            </div>
            <div id="registrarCardBody" className="p-6 space-y-4">
                {registrarCleared ? (
                    <div className="text-center space-y-2">
                        <h3 className="text-base font-bold text-brandGreen">Cleared</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">No on-hold records with this department.</p>
                    </div>
                ) : (
                    <div className="text-left max-w-xl mx-auto space-y-3">
                        <h3 className="text-base font-bold text-red-600 dark:text-red-500 text-center">Not Yet Cleared</h3>
                        <p className="text-xs text-brandNavy/70 dark:text-slate-400">Your account has an active administrative documentation hold:</p>

                        <div className="flex items-start space-x-3 p-3.5 rounded-xl bg-red-600/5 dark:bg-red-500/5 border border-red-600/10 dark:border-red-500/10">
                            <i className="fa-solid fa-circle-xmark text-red-500 mt-0.5 flex-shrink-0" />
                            <div>
                                <span className="text-xs font-bold text-brandNavy dark:text-slate-200 block">Office of the University Registrar</span>
                                <span className="text-[11px] text-brandNavy/60 dark:text-slate-500">Pending Original Copy Submission — Form 137 / Permanent Academic Records</span>
                            </div>
                        </div>

                        {submission.submissionPending ? (
                            <div id="submissionPendingBanner" className="flex items-start gap-3 p-4 rounded-xl bg-blue-600/5 border border-blue-600/15 dark:bg-blue-500/5 dark:border-blue-500/15">
                                <div className="w-8 h-8 rounded-full bg-blue-600/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i className="fa-solid fa-clock text-blue-600 dark:text-blue-400 text-xs" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs font-bold text-brandNavy dark:text-slate-200">Documents Submitted — Awaiting Registrar Review</p>
                                    <p className="text-[11px] text-brandNavy/60 dark:text-slate-500 mt-0.5">
                                        Submitted on {submission.createdAt}.
                                        The Registrar's Office will review your documents within 1–3 business days.
                                    </p>
                                    {submission.originalName && (
                                        <div className="mt-2 inline-flex items-center gap-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 bg-blue-600/5 px-2 py-1 rounded-lg">
                                            <i className="fa-solid fa-file-pdf" />{submission.originalName}
                                        </div>
                                    )}
                                </div>
                                <button onClick={() => onOpenModal(true)} className="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex-shrink-0 underline-offset-2">
                                    Resubmit
                                </button>
                            </div>
                        ) : (
                            <div className="flex items-center justify-between gap-4 p-4 rounded-xl bg-brandNavy/3 dark:bg-slate-800/40 border border-brandNavy/8 dark:border-slate-700/40">
                                <div>
                                    <p className="text-xs font-bold text-brandNavy dark:text-slate-200">Resolve this hold</p>
                                    <p className="text-[11px] text-brandNavy/60 dark:text-slate-500 mt-0.5">Upload a scanned copy of your Form 137 or equivalent document directly to the Registrar.</p>
                                </div>
                                <button
                                    onClick={() => onOpenModal(false)}
                                    className="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-bold rounded-xl transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0 uppercase tracking-wider whitespace-nowrap"
                                >
                                    <i className="fa-solid fa-upload" />Submit Documents
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Create `resources/js/clearance/SubmittedDocumentsList.jsx`**

Ported from `resources/views/clearance.blade.php:330-363`. Deliberate safety addition beyond the original: `rel="noreferrer"` on the `target="_blank"` link (the original has `target="_blank"` with no `rel`, a minor reverse-tabnabbing risk on any `target="_blank"` link — flag this as an intentional hardening, not scope creep, if a reviewer asks):

```jsx
export default function SubmittedDocumentsList({ submissions }) {
    if (submissions.length === 0) return null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-folder-open mr-2" />My Submitted Documents</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar Review</span>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-800">
                {submissions.map((doc, i) => (
                    <div key={i} className="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div className="min-w-0">
                            <span className="font-bold text-brandNavy dark:text-slate-200 block">{doc.typeLabel}</span>
                            <a href={doc.documentsShowUrl} target="_blank" rel="noreferrer" className="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                <i className="fa-solid fa-paperclip mr-1" />{doc.originalName}
                            </a>
                            <span className="text-brandNavy/50 dark:text-slate-500 ml-2">{doc.createdAtFormatted}</span>
                            {doc.status === 'rejected' && doc.remarks && (
                                <p className="text-[11px] text-red-500 mt-1"><i className="fa-solid fa-comment-dots mr-1" />Registrar: {doc.remarks}</p>
                            )}
                        </div>
                        <div className="flex-shrink-0">
                            {doc.status === 'pending' && (
                                <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                            )}
                            {doc.status === 'accepted' && (
                                <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Accepted</span>
                            )}
                            {doc.status !== 'pending' && doc.status !== 'accepted' && (
                                <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Rejected</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/clearance/RegistrarCard.jsx resources/js/clearance/SubmittedDocumentsList.jsx
git commit -m "feat: add RegistrarCard and SubmittedDocumentsList React components for clearance island"
```

---

### Task 3: Create `SubmitRequirementModal.jsx`

**Files:**
- Create: `resources/js/clearance/SubmitRequirementModal.jsx`

**Interfaces:**
- Produces: `SubmitRequirementModal` (default export), props `{ open, isResubmit, onClose, csrfToken, submitUrl }`. Consumed by `clearance-app.jsx` (Task 4), which owns the `open`/`isResubmit` state and passes `onOpenModal` down through `RegistrarCard` (Task 2) to flip it.

This is the highest-risk file in this plan — it re-implements the modal's drag-and-drop, file-preview, and submit-button loading-state logic (currently direct DOM manipulation in `resources/views/clearance.blade.php:486-588`) as React state, while keeping a **real native form submission** (not fetch). No automated test is possible (no JS test runner) — verification is careful comparison against the original script during review; build verification happens in Task 4 once this file is imported.

- [ ] **Step 1: Create `resources/js/clearance/SubmitRequirementModal.jsx`**

Reference — the original behavior being ported (for comparison during review), from `clearance.blade.php:371-588`:
- `openSubmitModal(isResubmit)`: shows the modal; if `isResubmit`, clears any previously-selected file first.
- `closeSubmitModal()` / backdrop click: hides the modal.
- Drag-and-drop uses the `DataTransfer` trick (`dt.items.add(file); fileInput.files = dt.files`) to populate the real file input, since the actual form submission relies on the browser's native multipart encoding of that input — this must be preserved exactly.
- `handleSubmit()`: if no file selected, briefly redden the drop-zone border (1.5s) and stop; otherwise disable the submit button, swap its text to a spinner, and call the form's native `.submit()` (bypassing any `submit` event, same as the original).

```jsx
import { useEffect, useRef, useState } from 'react';

const DOCUMENT_TYPES = [
    { value: 'form137', label: 'Form 137 — Permanent Record / Senior HS Report Card' },
    { value: 'form138', label: 'Form 138 — Report Card' },
    { value: 'birth_cert', label: 'PSA Birth Certificate' },
    { value: 'good_moral', label: 'Certificate of Good Moral Character' },
    { value: 'other', label: 'Other Supporting Document' },
];

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function fileIconClass(file) {
    if (file.type === 'application/pdf') return 'fa-solid fa-file-pdf text-red-500 text-base';
    if (file.type.startsWith('image/')) return 'fa-solid fa-file-image text-blue-500 text-base';
    return 'fa-solid fa-file text-slate-400 text-base';
}

export default function SubmitRequirementModal({ open, isResubmit, onClose, csrfToken, submitUrl }) {
    const [file, setFile] = useState(null);
    const [dragOver, setDragOver] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [showRequiredHint, setShowRequiredHint] = useState(false);
    const fileInputRef = useRef(null);
    const formRef = useRef(null);

    useEffect(() => {
        if (open && isResubmit) {
            setFile(null);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    }, [open, isResubmit]);

    if (!open) return null;

    const handleFileSelect = (e) => {
        if (e.target.files && e.target.files[0]) setFile(e.target.files[0]);
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        setDragOver(true);
    };

    const handleDragLeave = () => setDragOver(false);

    const handleDrop = (e) => {
        e.preventDefault();
        setDragOver(false);
        const dropped = e.dataTransfer.files[0];
        if (dropped) {
            const dt = new DataTransfer();
            dt.items.add(dropped);
            if (fileInputRef.current) fileInputRef.current.files = dt.files;
            setFile(dropped);
        }
    };

    const clearFile = (e) => {
        if (e) e.stopPropagation();
        if (fileInputRef.current) fileInputRef.current.value = '';
        setFile(null);
    };

    const handleSubmit = () => {
        if (!fileInputRef.current?.files?.[0]) {
            setShowRequiredHint(true);
            setTimeout(() => setShowRequiredHint(false), 1500);
            return;
        }
        setSubmitting(true);
        formRef.current.submit();
    };

    return (
        <div
            id="submitModal"
            className="fixed inset-0 bg-brandNavy/50 dark:bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div id="submitModalBox" className="modal-enter bg-white dark:bg-[#0D1B2A] rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl border border-brandNavy/8 dark:border-slate-800">

                <div className="bg-lightBg dark:bg-slate-950 px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-lg bg-brandNavy/8 dark:bg-slate-800 flex items-center justify-center">
                            <i className="fa-solid fa-file-arrow-up text-brandNavy dark:text-brandGold text-sm" />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-brandNavy dark:text-white">Submit Missing Requirements</h3>
                            <p className="text-[10px] text-brandNavy/50 dark:text-slate-500 mt-0.5">Office of the University Registrar</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="w-8 h-8 rounded-full bg-brandNavy/5 dark:bg-slate-800 text-brandNavy/50 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white flex items-center justify-center transition-colors">
                        <i className="fa-solid fa-xmark text-xs" />
                    </button>
                </div>

                <div className="mx-6 mt-5 flex items-start gap-3 p-3.5 rounded-xl bg-red-600/5 border border-red-600/10 dark:bg-red-500/5 dark:border-red-500/10 text-xs">
                    <i className="fa-solid fa-triangle-exclamation text-red-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <span className="font-bold text-brandNavy dark:text-slate-200">Outstanding Requirement</span>
                        <p className="text-brandNavy/60 dark:text-slate-500 mt-0.5">Original Copy — Form 137 / Permanent Academic Records</p>
                    </div>
                </div>

                <form ref={formRef} id="submissionForm" action={submitUrl} method="POST" encType="multipart/form-data" className="p-6 space-y-5">
                    <input type="hidden" name="_token" value={csrfToken} />

                    <div>
                        <label className="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider block mb-2">
                            Scanned Document <span className="text-red-500">*</span>
                        </label>
                        <div
                            id="dropZone"
                            className={`drop-zone rounded-xl p-6 text-center cursor-pointer bg-lightBg/50 dark:bg-slate-900/40 hover:bg-lightBg dark:hover:bg-slate-900/60 transition-colors ${dragOver ? 'dragover' : ''}`}
                            style={showRequiredHint ? { borderColor: '#ef4444' } : undefined}
                            onClick={() => fileInputRef.current?.click()}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                        >
                            <input
                                ref={fileInputRef}
                                id="fileInput"
                                type="file"
                                name="document"
                                accept=".pdf,.jpg,.jpeg,.png"
                                className="hidden"
                                onChange={handleFileSelect}
                            />

                            {!file ? (
                                <div>
                                    <div className="w-10 h-10 rounded-full bg-brandNavy/5 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                                        <i className="fa-solid fa-cloud-arrow-up text-brandNavy/40 dark:text-slate-500 text-lg" />
                                    </div>
                                    <p className="text-xs font-semibold text-brandNavy/70 dark:text-slate-400">Drag &amp; drop your file here, or <span className="text-brandGreen dark:text-brandGold font-bold">browse</span></p>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-1">Accepted: PDF, JPG, PNG — Max 10 MB</p>
                                </div>
                            ) : (
                                <div>
                                    <div className="file-chip inline-flex items-center gap-2.5 px-4 py-2.5 bg-white dark:bg-slate-800 border border-brandNavy/10 dark:border-slate-700 rounded-xl shadow-sm">
                                        <i className={fileIconClass(file)} />
                                        <div className="text-left">
                                            <p className="text-xs font-bold text-brandNavy dark:text-slate-200 truncate max-w-[200px]">{file.name}</p>
                                            <p className="text-[10px] text-brandNavy/50 dark:text-slate-500">{formatBytes(file.size)}</p>
                                        </div>
                                        <button type="button" onClick={clearFile} className="ml-1 w-5 h-5 rounded-full bg-brandNavy/5 dark:bg-slate-700 text-brandNavy/40 dark:text-slate-500 hover:bg-red-500/10 hover:text-red-500 flex items-center justify-center transition-colors">
                                            <i className="fa-solid fa-xmark text-[9px]" />
                                        </button>
                                    </div>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-2">Click to change file</p>
                                </div>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider block mb-2">Document Type</label>
                        <select name="document_type" className="w-full bg-lightBg/50 dark:bg-slate-900/40 text-brandNavy dark:text-slate-200 text-xs font-medium px-4 py-3 rounded-xl border border-brandNavy/10 dark:border-slate-700 focus:outline-none focus:border-brandGreen dark:focus:border-brandGold/50 transition-colors">
                            {DOCUMENT_TYPES.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider block mb-2">Notes to Registrar <span className="font-normal normal-case">(optional)</span></label>
                        <textarea
                            name="notes"
                            rows="3"
                            placeholder="e.g. Attached is the certified true copy issued by my previous school. Original is being mailed separately."
                            className="w-full bg-lightBg/50 dark:bg-slate-900/40 text-brandNavy dark:text-slate-200 text-xs px-4 py-3 rounded-xl border border-brandNavy/10 dark:border-slate-700 focus:outline-none focus:border-brandGreen dark:focus:border-brandGold/50 transition-colors resize-none placeholder-brandNavy/30 dark:placeholder-slate-600"
                        />
                    </div>

                    <div className="flex items-start gap-2.5 p-3 rounded-xl bg-blue-600/5 border border-blue-600/10 dark:bg-blue-500/5 dark:border-blue-500/10 text-[11px] text-brandNavy/60 dark:text-slate-500">
                        <i className="fa-solid fa-circle-info text-blue-500 mt-0.5 flex-shrink-0" />
                        <p>Your submission will be forwarded directly to the Registrar&apos;s Office. You will be notified once your document has been reviewed, typically within <strong className="text-brandNavy dark:text-slate-300">1–3 business days</strong>. Submitting does not guarantee immediate clearance.</p>
                    </div>

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={onClose} className="flex-1 py-3 rounded-xl text-xs font-bold text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 border border-brandNavy/10 dark:border-slate-700 transition-colors">
                            Cancel
                        </button>
                        <button
                            type="button"
                            id="submitBtn"
                            onClick={handleSubmit}
                            disabled={submitting}
                            className="flex-1 py-3 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-md hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0 uppercase tracking-wider disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        >
                            <span id="submitBtnText">
                                {submitting ? (
                                    <><i className="fa-solid fa-spinner animate-spin mr-1.5" />Uploading...</>
                                ) : (
                                    <><i className="fa-solid fa-paper-plane mr-1.5" />Submit to Registrar</>
                                )}
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/clearance/SubmitRequirementModal.jsx
git commit -m "feat: add SubmitRequirementModal React component for clearance island"
```

---

### Task 4: Create `clearance-app.jsx` entry and wire `vite.config.js`

**Files:**
- Create: `resources/js/clearance-app.jsx`
- Modify: `vite.config.js`

**Interfaces:**
- Consumes: `MasterStatusBadge`, `AccountingCard` (Task 1), `RegistrarCard`, `SubmittedDocumentsList` (Task 2), `SubmitRequirementModal` (Task 3) — all default exports, props as documented in their respective tasks.
- Produces: a `#clearance-root` mount point contract with `data-context` (JSON), `data-csrf-token`, and `data-submit-url` attributes — Task 5's Blade edit must provide this element.

This task's build step is the first point where all five components (Tasks 1-3) actually get compiled together — a successful build here verifies all three prior tasks' files compile cleanly.

- [ ] **Step 1: Create `resources/js/clearance-app.jsx`**

```jsx
import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import MasterStatusBadge from './clearance/MasterStatusBadge';
import AccountingCard from './clearance/AccountingCard';
import RegistrarCard from './clearance/RegistrarCard';
import SubmittedDocumentsList from './clearance/SubmittedDocumentsList';
import SubmitRequirementModal from './clearance/SubmitRequirementModal';

const EMPTY_CONTEXT = {
    isCleared: false,
    cashierCleared: false,
    registrarCleared: false,
    chairCleared: false,
    remarks: null,
    items: [],
    submission: { hasSubmission: false, submissionPending: false, createdAt: null, originalName: null },
    submissions: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            isCleared: !!parsed.isCleared,
            cashierCleared: !!parsed.cashierCleared,
            registrarCleared: !!parsed.registrarCleared,
            chairCleared: !!parsed.chairCleared,
            remarks: parsed.remarks ?? null,
            items: Array.isArray(parsed.items) ? parsed.items : [],
            submission: parsed.submission ?? EMPTY_CONTEXT.submission,
            submissions: Array.isArray(parsed.submissions) ? parsed.submissions : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function ClearanceApp({ context, csrfToken, submitUrl }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalIsResubmit, setModalIsResubmit] = useState(false);

    const openModal = (isResubmit) => {
        setModalIsResubmit(isResubmit);
        setModalOpen(true);
    };
    const closeModal = () => setModalOpen(false);

    return (
        <>
            <div className="space-y-6">
                <div className="text-center py-2 relative">
                    <h1 className="text-2xl font-bold tracking-tight text-brandNavy dark:text-white">Enrollment Clearance</h1>
                    <div className="hidden justify-center mt-3 animate-bounce">
                        <a href="/enrollment" className="inline-flex items-center space-x-2 text-xs font-black bg-brandGreen text-white px-5 py-2.5 rounded-xl shadow-lg hover:bg-emerald-600 transition-all">
                            <i className="fa-solid fa-rocket" />
                            <span>CONGRATULATIONS! CLICK HERE TO PROCEED TO ENROLLMENT</span>
                        </a>
                    </div>
                </div>

                <MasterStatusBadge
                    isCleared={context.isCleared}
                    cashierCleared={context.cashierCleared}
                    registrarCleared={context.registrarCleared}
                    chairCleared={context.chairCleared}
                    items={context.items}
                />

                {context.remarks && (
                    <div className="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 text-xs">
                        <i className="fa-solid fa-triangle-exclamation mr-2" /><strong>Remarks:</strong> {context.remarks}
                    </div>
                )}

                <div className="space-y-6">
                    <div className="flex items-center justify-between border-b border-brandNavy/5 dark:border-slate-800 pb-2">
                        <p className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">Clearance Details</p>
                        <p className="text-[10px] text-brandNavy/50 dark:text-slate-400 italic">Updates reflect immediately upon administrative action.</p>
                    </div>

                    <AccountingCard cashierCleared={context.cashierCleared} />
                    <RegistrarCard registrarCleared={context.registrarCleared} submission={context.submission} onOpenModal={openModal} />
                </div>

                <SubmittedDocumentsList submissions={context.submissions} />
            </div>

            <SubmitRequirementModal
                open={modalOpen}
                isResubmit={modalIsResubmit}
                onClose={closeModal}
                csrfToken={csrfToken}
                submitUrl={submitUrl}
            />
        </>
    );
}

const el = document.getElementById('clearance-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const submitUrl = el.dataset.submitUrl ?? '';
    createRoot(el).render(<ClearanceApp context={context} csrfToken={csrfToken} submitUrl={submitUrl} />);
}
```

- [ ] **Step 2: Register the new entry in `vite.config.js`**

Current `input` array (after the schedule island was added):
```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/enrollment-app.jsx',
    'resources/js/curriculum-app.jsx',
    'resources/js/dashboard-app.jsx',
    'resources/js/schedule-app.jsx',
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
    'resources/js/schedule-app.jsx',
    'resources/js/clearance-app.jsx',
],
```

- [ ] **Step 3: Verify the build**

Run: `npm run build`
Expected: exits 0. Check the output listing includes a `clearance-app` chunk (e.g. `clearance-app-XXXXXXXX.js` among the built assets), confirming Vite compiled the new entry and all five components without syntax errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/clearance-app.jsx vite.config.js
git commit -m "feat: add clearance-app Vite entry wiring all five clearance components"
```

---

### Task 5: Mount the island in `clearance.blade.php`, update tests

**Files:**
- Modify: `resources/views/clearance.blade.php`
- Test: `tests/Feature/ClearanceIslandTest.php` (new)
- Test: `tests/Feature/ClearancePageDocumentsTest.php` (update)
- Test: `tests/Feature/ClearanceHoldTest.php` (update)

**Interfaces:**
- Consumes: `resources/js/clearance-app.jsx` (Task 4) via `@vite('resources/js/clearance-app.jsx')`, and the `#clearance-root` mount contract from Task 4.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ClearanceIslandTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_clearance_page_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk();
        $response->assertSee('id="clearance-root"', false);
        $response->assertDontSee('Clearance Action Items');
        $response->assertDontSee('On-Hold Record Status');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/clearance')->assertRedirect();
    }
}
```

- [ ] **Step 2: Run the new test to verify it fails**

Run: `php artisan test --filter=ClearanceIslandTest`
Expected: FAIL on `assertSee('id="clearance-root"', false)` — the current Blade template has no such element yet.

- [ ] **Step 3: Replace the content sections in `clearance.blade.php`**

Replace the block from `{{-- PAGE TITLE + ENROLLMENT SHORTCUT --}}` through the closing `@endif` of the "MY SUBMITTED DOCUMENTS" section (`resources/views/clearance.blade.php:105-363` in the pre-edit file) with:

```blade
            @php
                $isCleared = isset($clearance) && (
                    $clearance->cashier_status === 'Approved' &&
                    $clearance->registrar_status === 'Approved' &&
                    $clearance->admission_status === 'Approved' &&
                    $clearance->chair_status === 'Approved' &&
                    $clearance->allItemsApproved()
                );
                $registrarCleared  = isset($clearance) && $clearance->registrar_status === 'Approved';
                $cashierCleared    = isset($clearance) && $clearance->cashier_status === 'Approved';
                $chairCleared      = isset($clearance) && $clearance->chair_status === 'Approved';
                $hasSubmission     = isset($submission) && $submission !== null;
                $submissionPending = $hasSubmission && ($submission->status ?? '') === 'pending';

                $clearanceContext = [
                    'isCleared' => $isCleared,
                    'cashierCleared' => $cashierCleared,
                    'registrarCleared' => $registrarCleared,
                    'chairCleared' => $chairCleared,
                    'remarks' => $clearance->remarks,
                    'items' => $clearance->items->map(fn ($item) => [
                        'departmentName' => $item->department->name,
                        'status' => $item->status,
                        'remarks' => $item->remarks,
                    ])->all(),
                    'submission' => [
                        'hasSubmission' => $hasSubmission,
                        'submissionPending' => $submissionPending,
                        'createdAt' => $hasSubmission ? (isset($submission->created_at) ? $submission->created_at->format('M d, Y g:i A') : 'recently') : null,
                        'originalName' => $hasSubmission ? ($submission->original_name ?? null) : null,
                    ],
                    'submissions' => $submissions->map(fn ($doc) => [
                        'typeLabel' => $doc->typeLabel(),
                        'documentsShowUrl' => route('documents.show', $doc),
                        'originalName' => $doc->original_name,
                        'createdAtFormatted' => $doc->created_at->format('M d, Y g:i A'),
                        'status' => $doc->status,
                        'remarks' => $doc->remarks,
                    ])->all(),
                ];
            @endphp

            <div id="clearance-root"
                 data-context="{{ json_encode($clearanceContext) }}"
                 data-csrf-token="{{ csrf_token() }}"
                 data-submit-url="{{ route('clearance.submitRequirement') }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>
```

- [ ] **Step 4: Remove the old document-submission modal and its script**

Delete the entire `{{-- DOCUMENT SUBMISSION MODAL --}}` block through the closing `</script>` (`resources/views/clearance.blade.php:368-588` in the pre-edit file — starts at `<div id="submitModal"` and ends at the `</script>` right before `@include('partials.notif-script')`). This logic now lives in `SubmitRequirementModal.jsx` (Task 3), mounted as part of the same `#clearance-root` React tree.

Do NOT remove: the `drop-zone`/`modal-enter` `<style>` block in `<head>` (lines 11-39), or `@include('partials.notif-script')`.

- [ ] **Step 5: Add the Vite entry for the island**

Current tail of the file (after the deleted modal/script block):
```blade
@include('partials.notif-script')
</body>
</html>
```

Replace with:
```blade
@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/clearance-app.jsx')
</body>
</html>
```

- [ ] **Step 6: Run the new test to verify it passes**

Run: `php artisan test --filter=ClearanceIslandTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Run the existing test suite to confirm which tests now fail**

Run: `php artisan test --filter=ClearancePageDocumentsTest`
Run: `php artisan test --filter=ClearanceHoldTest`
Expected: `ClearancePageDocumentsTest`'s two tests FAIL (they assert visible text like `'My Submitted Documents'` that no longer appears in server-rendered HTML). `ClearanceHoldTest::test_remarks_shown_on_student_clearance_page` FAILS for the same reason (asserts `'Please see the Department Chair.'` as visible text). This confirms the expected breakage before fixing it in the next step — do not skip this confirmation.

- [ ] **Step 8: Update `tests/Feature/ClearancePageDocumentsTest.php`**

Current content:
```php
<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearancePageDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_own_submissions_with_status_and_remarks(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'original_name' => 'my-form137.pdf',
            'status' => 'rejected', 'remarks' => 'Scan is blurry.',
        ]);
        DocumentSubmission::factory()->create(['original_name' => 'someone-elses.pdf']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk()
            ->assertSee('My Submitted Documents')
            ->assertSee('my-form137.pdf')
            ->assertSee('Scan is blurry.')
            ->assertDontSee('someone-elses.pdf');
    }

    public function test_page_hides_history_section_when_no_submissions(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertDontSee('My Submitted Documents');
    }
}
```

Replace with:
```php
<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearancePageDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_own_submissions_with_status_and_remarks(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DocumentSubmission::factory()->create([
            'user_id' => $student->id, 'original_name' => 'my-form137.pdf',
            'status' => 'rejected', 'remarks' => 'Scan is blurry.',
        ]);
        DocumentSubmission::factory()->create(['original_name' => 'someone-elses.pdf']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk()
            ->assertSee('id="clearance-root"', false)
            ->assertSee('"originalName":"my-form137.pdf"')
            ->assertSee('"remarks":"Scan is blurry."')
            ->assertDontSee('someone-elses.pdf');
    }

    public function test_page_hides_history_section_when_no_submissions(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"submissions":[]');
    }
}
```

(The submitted-documents list now renders client-side from `data-context`'s JSON, so the meaningful server-side check is that the JSON payload itself contains — or omits — the expected data, rather than checking visible rendered text. `assertSee` with default escaping correctly matches these substrings because Blade's `{{ json_encode(...) }}` HTML-escapes the same way Laravel's `assertSee` escapes its search string by default.)

- [ ] **Step 9: Update `tests/Feature/ClearanceHoldTest.php`**

In the existing file, replace only this method:
```php
    public function test_remarks_shown_on_student_clearance_page(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Hold', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'remarks' => 'Please see the Department Chair.',
        ]);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('Please see the Department Chair.');
    }
```

With:
```php
    public function test_remarks_shown_on_student_clearance_page(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Hold', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'remarks' => 'Please see the Department Chair.',
        ]);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"remarks":"Please see the Department Chair."');
    }
```

Leave every other method in this file unchanged.

- [ ] **Step 10: Run all three affected test files to verify they pass**

Run: `php artisan test --filter=ClearanceIslandTest`
Run: `php artisan test --filter=ClearancePageDocumentsTest`
Run: `php artisan test --filter=ClearanceHoldTest`
Expected: all PASS.

- [ ] **Step 11: Run the full suite to confirm no regressions**

Run: `php artisan test`
Expected: all tests pass (189 existing + 2 new `ClearanceIslandTest` tests = 191; `ClearancePageDocumentsTest` and `ClearanceHoldTest` keep their existing test counts, just with updated assertions in 3 of their methods).

- [ ] **Step 12: Commit**

```bash
git add resources/views/clearance.blade.php tests/Feature/ClearanceIslandTest.php tests/Feature/ClearancePageDocumentsTest.php tests/Feature/ClearanceHoldTest.php
git commit -m "feat: mount clearance React island in clearance.blade.php, update dependent tests"
```

---

### Task 6: Build and verify

**Files:** none (verification only).

- [ ] **Step 1: Production build**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 2: Start the app**

Run `php artisan serve` (or use XAMPP/Apache as usual) — no `npm run dev` needed since Task 4's build already compiled static assets into `public/build`.

- [ ] **Step 3: HTTP-level verification**

Using the same authenticated-curl approach used for the prior three island plans (extract CSRF token from `/login`, POST credentials with a cookie jar, GET protected pages with the same jar):

1. Authenticate as the demo regular student `2300410` / `password`.
2. `GET /clearance`: confirm HTTP 200, `id="clearance-root"` present, a `clearance-app-*.js` Vite asset referenced, `data-context` contains valid-looking JSON (e.g. contains `"isCleared"`), and neither "Clearance Action Items" nor "On-Hold Record Status" nor "My Submitted Documents" present as static server-rendered text.
3. Also authenticate as a registrar demo account (check the seeder for the exact login, e.g. one of the `*01` staff accounts with role `registrar`), `POST /registrar/documents/{id}/accept` against a freshly-created pending `DocumentSubmission` for a test student (or note if this requires an existing pending submission from Step 1's student — adapt as needed), and confirm the linked `Clearance.registrar_status` becomes `Approved` — this is a quick end-to-end sanity check that the `126da72` fix and this migration coexist correctly (the fix isn't part of this diff, but confirming it still works after the mount-point change costs little and closes the loop on the bug that motivated pausing this plan).

- [ ] **Step 4: Manual browser verification (if a browser tool is available)**

Check: all five sections (badge, accounting, registrar/hold, submitted documents, upload modal) render identically to how they looked before this change, in both light and dark mode; the upload modal's drag-and-drop and file preview work, and a real file submission still round-trips (redirects back with the flash success message); no console errors. If no browser-automation tool is available in this environment, explicitly flag this step as unperformed rather than claiming it passed — consistent with how the prior three island plans handled the same gap.

- [ ] **Step 5: Run the full test suite one more time**

Run: `php artisan test`
Expected: all tests pass (same count as after Task 5).

- [ ] **Step 6: Final commit (if manual verification surfaced any fixes)**

If Step 3 or Step 4 required any fix-up edits, commit them separately with a descriptive message before considering this plan complete.
