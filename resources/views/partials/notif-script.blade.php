<script>
@php
    use App\Models\Clearance;
    use App\Models\User;

    $authUser  = Auth::user();
    $authRole  = $authUser->role ?? 'student';
    $authId    = $authUser->id ?? 0;
    $notifs    = [];
    $nid       = 1;

    // ── STUDENT ──────────────────────────────────────────────────────────────
    if ($authRole === 'student') {
        $cl = ($clearance ?? Clearance::where('user_id', $authId)->first())?->loadMissing('items.department');

        if ($cl) {
            if ($cl->chair_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',  'color' => '#1D7A46', 'title' => 'Dept. Chair Approved',      'desc' => 'Your clearance has been signed by the Department Chair.',          'time' => 'Clearance update'];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half','color' => '#E2A700', 'title' => 'Awaiting Chair Signature',   'desc' => 'Your clearance is pending the Department Chair\'s sign-off.',     'time' => 'Action needed'];
            }

            if ($cl->registrar_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-signature', 'color' => '#0B3C5D', 'title' => 'Registrar Cleared',          'desc' => 'The Registrar has verified and signed your clearance slip.',       'time' => 'Clearance update'];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half','color' => '#E2A700', 'title' => 'Registrar Pending',           'desc' => 'Waiting for the Registrar to process your clearance.',             'time' => 'Action needed'];
            }

            if ($cl->cashier_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-wallet',         'color' => '#F97316', 'title' => 'Payment Verified',            'desc' => 'Your payment has been received and verified by the Cashier.',      'time' => 'Finance update'];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-credit-card',    'color' => '#F97316', 'title' => 'Payment Required',            'desc' => 'Please settle your balance to proceed with clearance.',            'time' => 'Action needed'];
            }

            $cl->loadMissing('items.department');
            foreach ($cl->items->where('status', 'Hold') as $heldItem) {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => $heldItem->department->name . ' Clearance On Hold', 'desc' => $heldItem->remarks ?? 'Contact the office for details.', 'time' => 'Action needed'];
            }
            $pendingDepartmentItems = $cl->items->where('status', 'Pending');
            if ($pendingDepartmentItems->isNotEmpty()) {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Department Clearance Pending', 'desc' => $pendingDepartmentItems->pluck('department.name')->implode(', ') . ' clearance still pending.', 'time' => 'Action needed'];
            }

            $allCleared = $cl->chair_status === 'Approved'
                       && $cl->registrar_status === 'Approved'
                       && $cl->cashier_status === 'Approved'
                       && $cl->allItemsApproved();

            if ($allCleared) {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-graduation-cap', 'color' => '#1D7A46', 'title' => 'Enrollment Unlocked!',        'desc' => 'All clearances approved. You may now enroll for A.Y. 2025–2026.', 'time' => 'System'];
            }

            $latestEnrollment = \App\Models\Enrollment::where('user_id', $authId)->latest()->first();
            if ($latestEnrollment) {
                if ($latestEnrollment->status === 'pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Enrollment Under Review', 'desc' => 'Your subject picks are with the Department Chair for approval.', 'time' => 'Enrollment update'];
                } elseif ($latestEnrollment->status === 'enrolled') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-graduation-cap', 'color' => '#1D7A46', 'title' => 'Officially Enrolled', 'desc' => 'Your enrollment is confirmed. View your schedule and COR anytime.', 'time' => 'Enrollment update'];
                } elseif ($latestEnrollment->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-xmark', 'color' => '#DC2626', 'title' => 'Enrollment Returned', 'desc' => 'The Chair returned your enrollment: ' . \Illuminate\Support\Str::limit($latestEnrollment->remarks ?? 'See remarks.', 80), 'time' => 'Action needed'];
                }
            }

            $latestMatriculationChange = \App\Models\MatriculationChange::where('user_id', $authId)->latest('id')->first();
            if ($latestMatriculationChange) {
                if ($latestMatriculationChange->status === 'pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#E2A700', 'title' => 'Change Request Under Review', 'desc' => 'Your change of matriculation is with the Department Chair for approval.', 'time' => 'Matriculation update'];
                } elseif ($latestMatriculationChange->status === 'approved') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#1D7A46', 'title' => 'Change of Matriculation Approved', 'desc' => 'Your schedule has been updated. View your COR anytime.', 'time' => 'Matriculation update'];
                } elseif ($latestMatriculationChange->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-xmark', 'color' => '#DC2626', 'title' => 'Change Request Returned', 'desc' => 'The Chair returned your change request: ' . \Illuminate\Support\Str::limit($latestMatriculationChange->remarks ?? 'See remarks.', 80), 'time' => 'Action needed'];
                }
            }
            $latestDocument = \App\Models\DocumentSubmission::where('user_id', $authId)->latest()->first();
            if ($latestDocument) {
                if ($latestDocument->status === 'pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-folder-open', 'color' => '#E2A700', 'title' => 'Document Under Review', 'desc' => 'Your ' . $latestDocument->typeLabel() . ' is with the Registrar for review.', 'time' => 'Document update'];
                } elseif ($latestDocument->status === 'accepted') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-circle-check', 'color' => '#1D7A46', 'title' => 'Document Accepted', 'desc' => 'Your ' . $latestDocument->typeLabel() . ' was accepted by the Registrar.', 'time' => 'Document update'];
                } elseif ($latestDocument->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-circle-xmark', 'color' => '#DC2626', 'title' => 'Document Rejected', 'desc' => 'Your ' . $latestDocument->typeLabel() . ' was rejected: ' . \Illuminate\Support\Str::limit($latestDocument->remarks ?? 'See remarks.', 80), 'time' => 'Action needed'];
                }
            }
            $latestPayment = \App\Models\TransactionLedger::where('user_id', $authId)->where('gateway', 'paymongo')->latest()->first();
            if ($latestPayment) {
                if ($latestPayment->status === 'Settled') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-money-check-dollar', 'color' => '#1D7A46', 'title' => 'Payment Received', 'desc' => '₱' . number_format((float) $latestPayment->amount, 2) . ' settled via PayMongo. Ref ' . $latestPayment->reference_no . '.', 'time' => 'Finance update'];
                } elseif ($latestPayment->status === 'Pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Payment Awaiting Verification', 'desc' => 'Use Verify Payment on your Ledger page to confirm your online payment.', 'time' => 'Action needed'];
                }
            }
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#E2A700', 'title' => 'Clearance Not Started',    'desc' => 'Your clearance record has not been initialized yet. Contact the Registrar.',  'time' => 'System'];
        }

        $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-info', 'color' => '#2563EB', 'title' => 'AITSA Portal Active',
                     'desc' => 'Welcome back, ' . ($authUser->name ?? 'Student') . '. A.Y. 2025–2026 enrollment is open.', 'time' => 'System'];

    // ── REGISTRAR / ADMISSION ─────────────────────────────────────────────────
    } elseif (in_array($authRole, ['registrar', 'admission'])) {
        $pendingApplicants = User::where('role', 'applicant')->count();
        $pendingClearances = Clearance::where('registrar_status', 'Pending')->count();
        $totalClearances   = Clearance::count();
        $clearedCount      = Clearance::where('registrar_status', 'Approved')->count();

        if ($pendingApplicants > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-clock',     'color' => '#E2A700', 'title' => 'New Applications Waiting',
                         'desc' => $pendingApplicants . ' applicant(s) are pending your review and verification.',  'time' => 'Action needed'];
        }
        if ($pendingClearances > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-signature',  'color' => '#0B3C5D', 'title' => 'Clearances Need Signature',
                         'desc' => $pendingClearances . ' student clearance(s) are waiting for your sign-off.',     'time' => 'Action needed'];
        }
        $pendingDocuments = \App\Models\DocumentSubmission::where('status', 'pending')->count();
        if ($pendingDocuments > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-folder-open', 'color' => '#E2A700', 'title' => 'Documents Awaiting Review',
                         'desc' => $pendingDocuments . ' document submission(s) awaiting your review.', 'time' => 'Action needed'];
        }
        if ($pendingApplicants === 0 && $pendingClearances === 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',   'color' => '#1D7A46', 'title' => 'Queue All Clear',
                         'desc' => 'No pending applications or clearances. Great work!',                             'time' => 'System'];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-chart-bar',          'color' => '#2563EB', 'title' => 'Clearance Progress',
                     'desc' => $clearedCount . ' of ' . $totalClearances . ' student clearances fully processed.',  'time' => 'Summary'];

    // ── ADMIN ─────────────────────────────────────────────────────────────────
    } elseif ($authRole === 'admin') {
        $verifiedApplicants = User::where('role', 'verified_applicant')->count();
        $pendingApplicants  = User::where('role', 'applicant')->count();
        $totalStudents      = User::where('role', 'student')->count();

        if ($verifiedApplicants > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-plus',      'color' => '#1D7A46', 'title' => 'Accounts Ready to Create',
                         'desc' => $verifiedApplicants . ' registrar-verified applicant(s) are waiting for student accounts.',  'time' => 'Action needed'];
        }
        if ($pendingApplicants > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-clock',     'color' => '#E2A700', 'title' => 'Applications Still Pending',
                         'desc' => $pendingApplicants . ' application(s) are still awaiting registrar verification.',           'time' => 'In progress'];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-users',              'color' => '#2563EB', 'title' => 'Student Population',
                     'desc' => $totalStudents . ' active student account(s) are registered in the system.',                     'time' => 'Summary'];
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-shield-check',       'color' => '#7C3AED', 'title' => 'System Operational',
                     'desc' => 'All modules are running normally. A.Y. 2025–2026 enrollment period is active.',                 'time' => 'System'];

    // ── CASHIER ───────────────────────────────────────────────────────────────
    } elseif ($authRole === 'cashier') {
        $pendingPayments = Clearance::where('cashier_status', 'Pending')->count();
        $settledPayments = Clearance::where('cashier_status', 'Approved')->count();
        $onlineToday = \App\Models\TransactionLedger::where('gateway', 'paymongo')->where('status', 'Settled')->whereDate('paid_at', today())->count();
        if ($onlineToday > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-money-bill-wave', 'color' => '#1D7A46', 'title' => 'Online Payments Received',
                         'desc' => $onlineToday . ' online payment(s) settled via PayMongo today.', 'time' => 'Finance update'];
        }

        if ($pendingPayments > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-cash-register',  'color' => '#F97316', 'title' => 'Payments Awaiting Verification',
                         'desc' => $pendingPayments . ' student payment(s) need your cashier verification.',                    'time' => 'Action needed'];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',   'color' => '#1D7A46', 'title' => 'No Pending Payments',
                         'desc' => 'All submitted payments have been processed. Queue is clear.',                                'time' => 'System'];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-chart-line',         'color' => '#2563EB', 'title' => 'Settlements This Period',
                     'desc' => $settledPayments . ' student payment(s) fully settled and archived.',                            'time' => 'Summary'];

    // ── DEPT CHAIR / APPROVER ─────────────────────────────────────────────────
    } elseif ($authRole === 'chair') {
        $pendingSign = Clearance::where('chair_status', 'Pending')->count();
        $signedCount = Clearance::where('chair_status', 'Approved')->count();

        if ($pendingSign > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-pen-to-square',  'color' => '#7C3AED', 'title' => 'Clearances Pending Your Approval',
                         'desc' => $pendingSign . ' student clearance(s) require your academic department sign-off.',           'time' => 'Action needed'];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',   'color' => '#1D7A46', 'title' => 'All Clearances Signed',
                         'desc' => 'No pending clearances in your queue. All students have been processed.',                    'time' => 'System'];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-graduation-cap',     'color' => '#0B3C5D', 'title' => 'Approvals This Period',
                     'desc' => $signedCount . ' student clearance(s) approved by your department.',                             'time' => 'Summary'];

        $pendingCount = \App\Models\Enrollment::where('status', 'pending')->count();
        if ($pendingCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-graduate', 'color' => '#E2A700', 'title' => 'Enrollments Awaiting Approval', 'desc' => $pendingCount . ' irregular enrollment(s) need your review.', 'time' => 'Action needed'];
        }

        $pendingChangeCount = \App\Models\MatriculationChange::where('status', 'pending')->count();
        if ($pendingChangeCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#E2A700', 'title' => 'Change Requests Awaiting Approval', 'desc' => $pendingChangeCount . ' change of matriculation request(s) need your review.', 'time' => 'Action needed'];
        }

    // ── FACULTY ───────────────────────────────────────────────────────────────
    } elseif ($authRole === 'faculty') {
        $loadCount = \App\Models\Section::where('faculty_id', $authId)
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))->count();

        if ($loadCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-chalkboard-user', 'color' => '#0B3C5D', 'title' => 'Teaching Load', 'desc' => 'You are loaded with ' . $loadCount . ' section(s) this A.Y.', 'time' => 'Summary'];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-chalkboard-user', 'color' => '#E2A700', 'title' => 'No Teaching Load', 'desc' => 'No teaching load assigned yet.', 'time' => 'System'];
        }

    // ── DEPARTMENT OFFICER ───────────────────────────────────────────────────
    } elseif ($authRole === 'department_officer') {
        $pendingItems = \App\Models\ClearanceItem::where('department_id', $authUser->department_id ?? 0)
            ->where('status', 'Pending')->count();

        if ($pendingItems > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-clipboard-check', 'color' => '#E2A700', 'title' => 'Clearances Awaiting Review',
                         'desc' => $pendingItems . ' student clearance item(s) need your review.', 'time' => 'Action needed'];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check', 'color' => '#1D7A46', 'title' => 'Queue Clear',
                         'desc' => 'No pending clearance items in your queue.', 'time' => 'System'];
        }
    }
@endphp

// ── Notifications (role-aware, server-generated) ──────────────────────────────
const NOTIFS    = @json($notifs);
const NOTIF_KEY = 'aitsa_notifs_{{ $authId }}_{{ $authRole }}';

function getReadIds() {
    try { return JSON.parse(localStorage.getItem(NOTIF_KEY) || '[]'); } catch { return []; }
}
function setRead(id) {
    const ids = getReadIds();
    if (!ids.includes(id)) { ids.push(id); localStorage.setItem(NOTIF_KEY, JSON.stringify(ids)); }
}
function markAllRead() {
    localStorage.setItem(NOTIF_KEY, JSON.stringify(NOTIFS.map(n => n.id)));
    renderNotifs();
}

function escNotif(value) {
    return String(value).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[c]));
}

function renderNotifs() {
    const readIds  = getReadIds();
    const unread   = NOTIFS.filter(n => !readIds.includes(n.id));
    const badge    = document.getElementById('notif-badge');
    const countLbl = document.getElementById('notif-count-label');
    const list     = document.getElementById('notif-list');

    if (badge)    { badge.textContent = unread.length; badge.classList.toggle('hidden', unread.length === 0); }
    if (countLbl) { countLbl.textContent = unread.length + ' new'; countLbl.classList.toggle('hidden', unread.length === 0); }

    if (!list) return;

    if (NOTIFS.length === 0) {
        list.innerHTML = `<div class="px-4 py-8 text-center text-brandNavy/40 dark:text-slate-500 text-xs">No notifications at this time.</div>`;
        return;
    }

    list.innerHTML = NOTIFS.map(n => {
        const isRead = readIds.includes(n.id);
        return `
        <div class="px-4 py-3 cursor-pointer transition-colors border-l-2
            ${isRead
                ? 'border-transparent opacity-50 hover:opacity-70'
                : 'border-brandGreen bg-brandGreen/[0.03] hover:bg-brandGreen/[0.06] dark:bg-brandGreen/5 dark:hover:bg-brandGreen/10'}"
            onclick="readNotif(${n.id})">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-bold text-brandNavy dark:text-slate-200 leading-tight">${escNotif(n.title)}</p>
                ${!isRead ? '<div class="w-1.5 h-1.5 rounded-full bg-brandGreen flex-shrink-0"></div>' : ''}
            </div>
            <p class="text-[10px] text-brandNavy/60 dark:text-slate-400 mt-0.5 leading-snug">${escNotif(n.desc)}</p>
            <p class="text-[9px] text-brandNavy/35 dark:text-slate-500 mt-1">${escNotif(n.time)}</p>
        </div>`;
    }).join('');
}

function readNotif(id) { setRead(id); renderNotifs(); }

function toggleNotifs(e) {
    e.stopPropagation();
    const dd = document.getElementById('notif-dropdown');
    if (dd) dd.classList.toggle('hidden');
}

document.addEventListener('click', (e) => {
    const container = document.getElementById('notif-container');
    const dd        = document.getElementById('notif-dropdown');
    if (dd && container && !container.contains(e.target)) dd.classList.add('hidden');
});

document.addEventListener('DOMContentLoaded', renderNotifs);
</script>
