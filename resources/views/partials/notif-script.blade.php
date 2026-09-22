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
        $cl = ($clearance ?? ($authUser ? Clearance::currentFor($authUser) : null))?->loadMissing('items.department');

        if ($cl) {
            if ($cl->chair_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',  'color' => '#1D7A46', 'title' => 'Dept. Chair Approved',      'desc' => 'Your clearance has been signed by the Department Chair.',          'time' => $cl->chair_signed_at?->diffForHumans() ?? 'Clearance update', 'url' => route('clearance')];
            } elseif ($cl->chair_status === 'Hold') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => 'Chair Clearance On Hold', 'desc' => $cl->remarks ?? 'Contact the Department Chair for details.', 'time' => $cl->updated_at->diffForHumans(), 'url' => route('clearance')];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half','color' => '#E2A700', 'title' => 'Awaiting Chair Signature',   'desc' => 'Your clearance is pending the Department Chair\'s sign-off.',     'time' => $cl->created_at->diffForHumans(), 'url' => route('clearance')];
            }

            if ($cl->registrar_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-signature', 'color' => '#0B3C5D', 'title' => 'Registrar Cleared',          'desc' => 'The Registrar has verified and signed your clearance slip.',       'time' => $cl->registrar_signed_at?->diffForHumans() ?? 'Clearance update', 'url' => route('clearance')];
            } elseif ($cl->registrar_status === 'Hold') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => 'Registrar Clearance On Hold', 'desc' => $cl->remarks ?? 'Contact the Registrar for details.', 'time' => $cl->updated_at->diffForHumans(), 'url' => route('clearance')];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half','color' => '#E2A700', 'title' => 'Registrar Pending',           'desc' => 'Waiting for the Registrar to process your clearance.',             'time' => $cl->created_at->diffForHumans(), 'url' => route('clearance')];
            }

            if ($cl->cashier_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-wallet',         'color' => '#F97316', 'title' => 'Payment Verified',            'desc' => 'Your payment has been received and verified by the Cashier.',      'time' => $cl->cashier_signed_at?->diffForHumans() ?? 'Finance update', 'url' => route('ledger')];
            } elseif ($cl->cashier_status === 'Hold') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => 'Cashier Clearance On Hold', 'desc' => $cl->remarks ?? 'Contact the Cashier for details.', 'time' => $cl->updated_at->diffForHumans(), 'url' => route('ledger')];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-credit-card',    'color' => '#F97316', 'title' => 'Payment Required',            'desc' => 'Please settle your balance to proceed with clearance.',            'time' => $cl->created_at->diffForHumans(), 'url' => route('ledger')];
            }

            $cl->loadMissing('items.department');
            foreach ($cl->items->where('status', 'Hold') as $heldItem) {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => $heldItem->department->name . ' Clearance On Hold', 'desc' => $heldItem->remarks ?? 'Contact the office for details.', 'time' => $heldItem->updated_at->diffForHumans(), 'url' => route('clearance')];
            }
            $pendingDepartmentItems = $cl->items->where('status', 'Pending');
            if ($pendingDepartmentItems->isNotEmpty()) {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Department Clearance Pending', 'desc' => $pendingDepartmentItems->pluck('department.name')->implode(', ') . ' clearance still pending.', 'time' => $pendingDepartmentItems->max('updated_at')->diffForHumans(), 'url' => route('clearance')];
            }

            $allCleared = $cl->chair_status === 'Approved'
                       && $cl->registrar_status === 'Approved'
                       && $cl->cashier_status === 'Approved'
                       && $cl->allItemsApproved();

            if ($allCleared) {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-graduation-cap', 'color' => '#1D7A46', 'title' => 'Enrollment Unlocked!',        'desc' => 'All clearances approved. You may now enroll for A.Y. 2025–2026.', 'time' => collect([$cl->chair_signed_at, $cl->registrar_signed_at, $cl->cashier_signed_at])->filter()->max()?->diffForHumans() ?? 'System', 'url' => route('enrollment')];
            }

            $latestEnrollment = \App\Models\Enrollment::where('user_id', $authId)->latest()->first();
            if ($latestEnrollment) {
                if ($latestEnrollment->status === 'pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Enrollment Under Review', 'desc' => 'Your subject picks are with the Department Chair for approval.', 'time' => $latestEnrollment->updated_at->diffForHumans(), 'url' => route('enrollment')];
                } elseif ($latestEnrollment->status === 'enrolled') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-graduation-cap', 'color' => '#1D7A46', 'title' => 'Officially Enrolled', 'desc' => 'Your enrollment is confirmed. View your schedule and COR anytime.', 'time' => $latestEnrollment->updated_at->diffForHumans(), 'url' => route('cor')];
                } elseif ($latestEnrollment->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-xmark', 'color' => '#DC2626', 'title' => 'Enrollment Returned', 'desc' => 'The Chair returned your enrollment: ' . \Illuminate\Support\Str::limit($latestEnrollment->remarks ?? 'See remarks.', 80), 'time' => $latestEnrollment->updated_at->diffForHumans(), 'url' => route('enrollment')];
                }
            }

            $latestMatriculationChange = \App\Models\MatriculationChange::where('user_id', $authId)->latest('id')->first();
            if ($latestMatriculationChange) {
                if ($latestMatriculationChange->status === 'pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#E2A700', 'title' => 'Change Request Under Review', 'desc' => 'Your change of matriculation is with the Department Chair for approval.', 'time' => $latestMatriculationChange->updated_at->diffForHumans(), 'url' => route('enrollment')];
                } elseif ($latestMatriculationChange->status === 'approved') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#1D7A46', 'title' => 'Change of Matriculation Approved', 'desc' => 'Your schedule has been updated. View your COR anytime.', 'time' => $latestMatriculationChange->updated_at->diffForHumans(), 'url' => route('cor')];
                } elseif ($latestMatriculationChange->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-xmark', 'color' => '#DC2626', 'title' => 'Change Request Returned', 'desc' => 'The Chair returned your change request: ' . \Illuminate\Support\Str::limit($latestMatriculationChange->remarks ?? 'See remarks.', 80), 'time' => $latestMatriculationChange->updated_at->diffForHumans(), 'url' => route('enrollment')];
                }
            }
            $latestDocument = \App\Models\DocumentSubmission::where('user_id', $authId)->latest()->first();
            if ($latestDocument) {
                if ($latestDocument->status === 'pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-folder-open', 'color' => '#E2A700', 'title' => 'Document Under Review', 'desc' => 'Your ' . $latestDocument->typeLabel() . ' is with the Registrar for review.', 'time' => $latestDocument->created_at->diffForHumans(), 'url' => route('documents')];
                } elseif ($latestDocument->status === 'accepted') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-circle-check', 'color' => '#1D7A46', 'title' => 'Document Accepted', 'desc' => 'Your ' . $latestDocument->typeLabel() . ' was accepted by the Registrar.', 'time' => ($latestDocument->reviewed_at ?? $latestDocument->created_at)->diffForHumans(), 'url' => route('documents')];
                } elseif ($latestDocument->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-circle-xmark', 'color' => '#DC2626', 'title' => 'Document Rejected', 'desc' => 'Your ' . $latestDocument->typeLabel() . ' was rejected: ' . \Illuminate\Support\Str::limit($latestDocument->remarks ?? 'See remarks.', 80), 'time' => ($latestDocument->reviewed_at ?? $latestDocument->created_at)->diffForHumans(), 'url' => route('documents')];
                }
            }
            $latestPayment = \App\Models\TransactionLedger::where('user_id', $authId)->where('gateway', 'paymongo')->latest()->first();
            if ($latestPayment) {
                if ($latestPayment->status === 'Settled') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-money-check-dollar', 'color' => '#1D7A46', 'title' => 'Payment Received', 'desc' => '₱' . number_format((float) $latestPayment->amount, 2) . ' settled via PayMongo. Ref ' . $latestPayment->reference_no . '.', 'time' => ($latestPayment->paid_at ?? $latestPayment->created_at)->diffForHumans(), 'url' => route('ledger')];
                } elseif ($latestPayment->status === 'Pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Payment Awaiting Verification', 'desc' => 'Use Verify Payment on your Ledger page to confirm your online payment.', 'time' => $latestPayment->created_at->diffForHumans(), 'url' => route('ledger')];
                }
            }
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#E2A700', 'title' => 'Clearance Not Started',    'desc' => 'Your clearance record has not been initialized yet. Contact the Registrar.',  'time' => 'System', 'url' => route('clearance')];
        }

        $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-info', 'color' => '#2563EB', 'title' => 'AITSA Portal Active',
                     'desc' => 'Welcome back, ' . ($authUser->name ?? 'Student') . '. A.Y. 2025–2026 enrollment is open.', 'time' => 'System', 'url' => route('dashboard')];

    // ── REGISTRAR / ADMISSION ─────────────────────────────────────────────────
    } elseif (in_array($authRole, ['registrar', 'admission'])) {
        $pendingApplicants = User::where('role', 'applicant')->count();
        $pendingClearances = Clearance::where('registrar_status', 'Pending')
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();
        $totalClearances   = Clearance::where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();
        $clearedCount      = Clearance::where('registrar_status', 'Approved')
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();

        if ($pendingApplicants > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-clock',     'color' => '#E2A700', 'title' => 'New Applications Waiting',
                         'desc' => $pendingApplicants . ' applicant(s) are pending your review and verification.',  'time' => 'Action needed', 'url' => route('registrar.dashboard')];
        }
        if ($pendingClearances > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-signature',  'color' => '#0B3C5D', 'title' => 'Clearances Need Signature',
                         'desc' => $pendingClearances . ' student clearance(s) are waiting for your sign-off.',     'time' => 'Action needed', 'url' => route('registrar.dashboard')];
        }
        $pendingDocuments = \App\Models\DocumentSubmission::where('status', 'pending')->count();
        if ($pendingDocuments > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-folder-open', 'color' => '#E2A700', 'title' => 'Documents Awaiting Review',
                         'desc' => $pendingDocuments . ' document submission(s) awaiting your review.', 'time' => 'Action needed', 'url' => route('registrar.documents.search')];
        }
        if ($pendingApplicants === 0 && $pendingClearances === 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',   'color' => '#1D7A46', 'title' => 'Queue All Clear',
                         'desc' => 'No pending applications or clearances. Great work!',                             'time' => 'System', 'url' => route('registrar.dashboard')];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-chart-bar',          'color' => '#2563EB', 'title' => 'Clearance Progress',
                     'desc' => $clearedCount . ' of ' . $totalClearances . ' student clearances fully processed.',  'time' => 'Summary', 'url' => route('registrar.dashboard')];

    // ── ADMIN ─────────────────────────────────────────────────────────────────
    } elseif ($authRole === 'admin') {
        $pendingApplicants  = User::where('role', 'applicant')->count();
        $totalStudents      = User::where('role', 'student')->count();

        if ($pendingApplicants > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-clock',     'color' => '#E2A700', 'title' => 'Applications Still Pending',
                         'desc' => $pendingApplicants . ' application(s) are still awaiting registrar verification.',           'time' => 'In progress', 'url' => route('admin.students.index')];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-users',              'color' => '#2563EB', 'title' => 'Student Population',
                     'desc' => $totalStudents . ' active student account(s) are registered in the system.',                     'time' => 'Summary', 'url' => route('admin.students.index')];
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-shield-check',       'color' => '#7C3AED', 'title' => 'System Operational',
                     'desc' => 'All modules are running normally. A.Y. 2025–2026 enrollment period is active.',                 'time' => 'System', 'url' => route('admin.dashboard')];

    // ── CASHIER ───────────────────────────────────────────────────────────────
    } elseif ($authRole === 'cashier') {
        $pendingPayments = Clearance::where('cashier_status', 'Pending')
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();
        $settledPayments = Clearance::where('cashier_status', 'Approved')
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();
        $onlineToday = \App\Models\TransactionLedger::where('gateway', 'paymongo')->where('status', 'Settled')->whereDate('paid_at', today())->count();
        if ($onlineToday > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-money-bill-wave', 'color' => '#1D7A46', 'title' => 'Online Payments Received',
                         'desc' => $onlineToday . ' online payment(s) settled via PayMongo today.', 'time' => 'Finance update', 'url' => route('cashier.transactions')];
        }

        if ($pendingPayments > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-cash-register',  'color' => '#F97316', 'title' => 'Payments Awaiting Verification',
                         'desc' => $pendingPayments . ' student payment(s) need your cashier verification.',                    'time' => 'Action needed', 'url' => route('cashier.dashboard')];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',   'color' => '#1D7A46', 'title' => 'No Pending Payments',
                         'desc' => 'All submitted payments have been processed. Queue is clear.',                                'time' => 'System', 'url' => route('cashier.dashboard')];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-chart-line',         'color' => '#2563EB', 'title' => 'Settlements This Period',
                     'desc' => $settledPayments . ' student payment(s) fully settled and archived.',                            'time' => 'Summary', 'url' => route('cashier.transactions')];

    // ── DEPT CHAIR / APPROVER ─────────────────────────────────────────────────
    } elseif ($authRole === 'chair') {
        $pendingSign = Clearance::where('chair_status', 'Pending')
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();
        $signedCount = Clearance::where('chair_status', 'Approved')
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) \App\Models\Setting::get('semester', '1'))
            ->count();

        if ($pendingSign > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-pen-to-square',  'color' => '#7C3AED', 'title' => 'Clearances Pending Your Approval',
                         'desc' => $pendingSign . ' student clearance(s) require your academic department sign-off.',           'time' => 'Action needed', 'url' => route('approver.dashboard')];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',   'color' => '#1D7A46', 'title' => 'All Clearances Signed',
                         'desc' => 'No pending clearances in your queue. All students have been processed.',                    'time' => 'System', 'url' => route('approver.dashboard')];
        }
        $notifs[] = ['id' => $nid++, 'icon' => 'fa-graduation-cap',     'color' => '#0B3C5D', 'title' => 'Approvals This Period',
                     'desc' => $signedCount . ' student clearance(s) approved by your department.',                             'time' => 'Summary', 'url' => route('approver.dashboard')];

        $pendingCount = \App\Models\Enrollment::where('status', 'pending')->count();
        if ($pendingCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-graduate', 'color' => '#E2A700', 'title' => 'Enrollments Awaiting Approval', 'desc' => $pendingCount . ' irregular enrollment(s) need your review.', 'time' => 'Action needed', 'url' => route('approver.dashboard')];
        }

        $pendingChangeCount = \App\Models\MatriculationChange::where('status', 'pending')->count();
        if ($pendingChangeCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#E2A700', 'title' => 'Change Requests Awaiting Approval', 'desc' => $pendingChangeCount . ' change of matriculation request(s) need your review.', 'time' => 'Action needed', 'url' => route('approver.dashboard')];
        }

    // ── FACULTY ───────────────────────────────────────────────────────────────
    } elseif ($authRole === 'faculty') {
        $loadCount = \App\Models\Section::where('faculty_id', $authId)
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))->count();

        if ($loadCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-chalkboard-user', 'color' => '#0B3C5D', 'title' => 'Teaching Load', 'desc' => 'You are loaded with ' . $loadCount . ' section(s) this A.Y.', 'time' => 'Summary', 'url' => route('faculty.sections')];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-chalkboard-user', 'color' => '#E2A700', 'title' => 'No Teaching Load', 'desc' => 'No teaching load assigned yet.', 'time' => 'System', 'url' => route('faculty.sections')];
        }

    // ── DEPARTMENT OFFICER ───────────────────────────────────────────────────
    } elseif ($authRole === 'department_officer') {
        $pendingItems = \App\Models\ClearanceItem::where('department_id', $authUser->department_id ?? 0)
            ->where('status', 'Pending')->count();

        if ($pendingItems > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-clipboard-check', 'color' => '#E2A700', 'title' => 'Clearances Awaiting Review',
                         'desc' => $pendingItems . ' student clearance item(s) need your review.', 'time' => 'Action needed', 'url' => route('department.dashboard')];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check', 'color' => '#1D7A46', 'title' => 'Queue Clear',
                         'desc' => 'No pending clearance items in your queue.', 'time' => 'System', 'url' => route('department.dashboard')];
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
    const footer   = document.getElementById('notif-footer-text');

    if (badge)    { badge.textContent = unread.length; badge.classList.toggle('hidden', unread.length === 0); }
    if (countLbl) { countLbl.textContent = unread.length + ' new'; countLbl.classList.toggle('hidden', unread.length === 0); }
    if (footer)   { footer.textContent = unread.length === 0 ? "You're all caught up" : `${unread.length} unread notification${unread.length === 1 ? '' : 's'}`; }

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

function readNotif(id) {
    setRead(id);
    const notif = NOTIFS.find(n => n.id === id);
    if (notif && notif.url) {
        window.location.href = notif.url;
        return;
    }
    renderNotifs();
}

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
