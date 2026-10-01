<?php

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\AuthController;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\ClearanceItem;
use App\Models\Department;
use App\Models\DiscountType;
use App\Models\DocumentSubmission;
use App\Models\Enrollment;
use App\Models\EnrollmentAgreement;
use App\Models\MatriculationChange;
use App\Models\GradeSubmission;
use App\Models\GradeSubmissionItem;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Notifications\ClearanceStatusUpdatedNotification;
use App\Notifications\DocumentRejectedNotification;
use App\Notifications\DocumentStatusReminderNotification;
use App\Services\FeeAssessmentService;
use App\Services\MatriculationChangeService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| Public Guest Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1,login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password reset — login_id-or-email identifier (same as the login form),
// resolved internally to the account's email for Laravel's password broker.
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1,forgot-password');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1,reset-password');

// Open Student Application Sequence Endpoints
Route::get('/apply', [AuthController::class, 'showApplicationForm'])->name('apply');
Route::post('/apply', [AuthController::class, 'processApplication'])->name('apply.store');

// Applicant slot-reservation fee — paid immediately after submitting the
// application form, before the applicant has an account to log into.
// These use Laravel's signed-URL middleware so the links can't be tampered
// with or reused for a different applicant.
Route::middleware('signed')->group(function () {
        Route::get('/apply/reservation/{user}/return', function (User $user, PaymentService $payments) {
        // PayMongo's webhook (server-to-server) can settle this payment and
        // auto-activate the applicant into a student account before the
        // applicant's own browser finishes redirecting back here — the role
        // may already be 'student' by the time this request arrives. Only
        // 404 when there's no evidence a reservation was ever paid for this
        // user at all.
        $settledReservation = TransactionLedger::where('user_id', $user->id)
            ->where('fee_type', 'reservation')
            ->where('status', 'Settled')
            ->latest('paid_at')
            ->first();

        abort_unless($user->role === 'applicant' || $settledReservation, 404);

        if (! $settledReservation) {
            try {
                $result = $payments->verifyLatestPending($user);
            } catch (PaymentGatewayException $e) {
                return redirect()->route('apply')->with('error', $e->getMessage());
            }

            if (! $result['ok']) {
                return redirect()->route('apply')->with('error', $result['message']);
            }
        }

        // Payment confirmed — PaymentService already auto-created the student
        // account (see AdmissionService::activateStudentAccount, called from
        // PaymentService::settleRow). Pull the fresh record + the settled
        // transaction so we can show a proper receipt / statement of account.
        $user->refresh();
        $txn = $settledReservation ?? TransactionLedger::where('user_id', $user->id)
            ->where('fee_type', 'reservation')
            ->where('status', 'Settled')
            ->latest('paid_at')
            ->first();

        return redirect()->route('apply')->with('success', 'Application successfully submitted and reservation fee paid.')->with('receipt', [
            'reference_no'    => $txn?->reference_no,
            'amount'          => $txn?->amount,
            'paid_at'         => optional($txn?->paid_at)->format('M d, Y g:i A'),
            'applicant_name'  => $user->name,
            'program_name'    => $user->major,
            'login_id'        => $user->login_id,
            'email'           => $user->email,
            'email_sent'      => (bool) $user->credentials_email_sent_at,
        ]);
    })->name('apply.reservation.return');

    Route::get('/apply/reservation/{user}/cancel', function (User $user, PaymentService $payments) {
        abort_unless($user->role === 'applicant', 404);
        $payments->cancelLatestPending($user);

        return redirect()->route('apply')->with('error', 'Reservation payment cancelled. Your application was still submitted — you can settle the reservation fee at the cashier window instead.');
    })->name('apply.reservation.cancel');

    // Native enrollment-agreement signing ceremony (canvas signature + audit
    // trail) — reached before an applicant has an account to log into, so
    // it lives behind Laravel's own signed-URL middleware instead.
    Route::get('/agreement/{user}/sign', [\App\Http\Controllers\AgreementController::class, 'sign'])
        ->name('agreement.sign');
    Route::post('/agreement/{user}/sign', [\App\Http\Controllers\AgreementController::class, 'submit'])
        ->name('agreement.sign.submit');
});

/*
|--------------------------------------------------------------------------
| Protected Authenticated Systems
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
     Route::get('/my-signature', [\App\Http\Controllers\SignatureController::class, 'edit'])->name('signature.edit')->middleware('password.confirm:,900');
     Route::post('/my-signature', [\App\Http\Controllers\SignatureController::class, 'update'])->name('signature.update')->middleware('password.confirm:,900');

     Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
     Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
     Route::post('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update')->middleware('throttle:5,1,profile-password');
     Route::post('/profile/password/reset-link', [\App\Http\Controllers\ProfileController::class, 'sendPasswordResetLink'])->name('profile.password.reset-link')->middleware('throttle:5,1,profile-password-reset-link');

     Route::get('/my-agreement', [\App\Http\Controllers\AgreementController::class, 'downloadMine'])->name('agreement.mine');

     // Step-up re-authentication: a small set of financial/personal-record
     // pages (see the password.confirm middleware applied further down)
     // redirect here first if the user hasn't confirmed their password
     // recently. Deliberately NOT gated by password.confirm itself, or
     // confirming would redirect back to a page asking to confirm again.
     Route::get('/confirm-password', [\App\Http\Controllers\ConfirmPasswordController::class, 'show'])->name('password.confirm');
     Route::post('/confirm-password', [\App\Http\Controllers\ConfirmPasswordController::class, 'store'])->name('password.confirm.store')->middleware('throttle:5,1,confirm-password');

     // Forced to change a Registrar-issued temporary password before doing
     // anything else — see EnsurePasswordChangeIsNotPending.
     Route::get('/force-password-change', [\App\Http\Controllers\AuthController::class, 'showForcePasswordChangeForm'])->name('password.force-change');
     Route::post('/force-password-change', [\App\Http\Controllers\AuthController::class, 'forcePasswordChangeUpdate'])->name('password.force-change.submit');
    // 1. Student Dashboard Module
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Session validation failure.');
        }

        $clearance = strtoupper((string) $user->program_level) === 'TESDA'
            ? Clearance::currentFor($user)
            : null;

        if (! $clearance) {
            $clearance = Clearance::initializeFor(
                $user->id,
                Setting::get('school_year', '2026-2027'),
                (int) Setting::get('semester', '1'),
                [
                    'admission_status'   => 'Pending',
                    'chair_status'       => 'Pending',
                    'cashier_status'     => 'Pending',
                    'registrar_status'   => 'Pending',
                ]
            );
        }

        $announcements = Announcement::where('is_active', true)->latest()->take(5)
            ->get(['id', 'title', 'created_at', 'attachment_path', 'attachment_name', 'link_url', 'link_image_path']);

        $context = [
            'clearancePercent' => $clearance->completionPercent(),
            'announcements' => $announcements->map(fn ($a) => [
                'title' => $a->title,
                'postedAt' => $a->created_at->format('M d, Y'),
                'isNew' => $a->created_at->gt(now()->subDays(3)),
                'attachmentUrl' => $a->attachment_path ? route('announcements.attachment', $a) : null,
                'attachmentIsImage' => $a->isImageAttachment(),
                'attachmentName' => $a->attachment_name,
                'linkUrl' => $a->link_url,
                'linkType' => $a->linkPreview()['type'] ?? null,
                'linkSrc' => $a->linkPreview()['src'] ?? null,
                'linkHost' => $a->linkPreview()['host'] ?? null,
                'linkImageUrl' => $a->link_image_path ? route('announcements.link-image', $a) : null,
            ])->values(),
        ];

        return view('dashboard', compact('clearance', 'context'));
    })->name('dashboard');

    // 2. Student e-Clearance Routing Module
    Route::get('/clearance', function (FeeAssessmentService $fees) {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $clearance = strtoupper((string) $user->program_level) === 'TESDA'
            ? Clearance::currentFor($user)
            : null;

        if (! $clearance) {
            $clearance = Clearance::initializeFor(
                $user->id,
                Setting::get('school_year', '2026-2027'),
                (int) Setting::get('semester', '1'),
                [
                    'admission_status'   => 'Pending',
                    'chair_status'       => 'Pending',
                    'cashier_status'     => 'Pending',
                    'registrar_status'   => 'Pending',
                ]
            );
        }

        $clearance->load('items.department');

        // Only the latest submission (any type) is needed here, to show the
        // registrar hold banner's pending/awaiting-review state. The full
        // upload UI and submission history now live on the /documents page.
        $submission = DocumentSubmission::where('user_id', $user->id)->latest()->first();
        $breakdown = $fees->breakdownFor($user);
        $agreement = $user->agreements()->latest()->first();
        return view('clearance', compact('clearance', 'submission', 'breakdown', 'agreement'));
    })->name('clearance');

    // Inline document submission from the Clearance page itself.
    Route::post('/clearance/submit-requirement', function (Request $request) {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'document_type' => ['required', 'in:' . implode(',', array_keys(DocumentSubmission::TYPES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('document');
        $submission = DocumentSubmission::create([
            'user_id' => $user->id,
            'document_type' => $request->input('document_type'),
            'notes' => $request->input('notes'),
            'file_path' => $file->store('documents', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        AuditLog::record('Document Submitted', 'Student ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . ') submitted ' . $submission->typeLabel() . '.', 'DocumentSubmission', $submission->id);

        return redirect()->route('clearance')->with('success', 'Your document has been submitted to the Registrar for review.');
    })->name('clearance.submitRequirement');

    // 2.1. Documents Module — submit & track institutional requirements,
    // separate from Clearance (matches the paper's distinct Fig 55 vs Fig 56 pages).
    Route::get('/documents', function () {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $submissions = DocumentSubmission::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('document_type')
            ->values();

        // Transcript of Records / Honorable Dismissal are only asked of
        // students who transferred or returned from another school — a NEW
        // applicant has no prior institution to request them from.
        $isTransfereeOrReturnee = in_array($user->applicant_type, ['TRANSFEREE', 'RETURNEE'], true);
        $types = collect(DocumentSubmission::TYPES);
        if (! $isTransfereeOrReturnee) {
            $types = $types->except(['transcript_of_records', 'honorable_dismissal']);
        }

        // One row per known requirement type, showing its latest submission (if any).
        $requirements = $types->map(function ($label, $type) use ($submissions) {
            $latest = $submissions->firstWhere('document_type', $type);
            return [
                'type' => $type,
                'label' => $label,
                'status' => $latest->status ?? 'missing',
                'remarks' => $latest->remarks ?? null,
                'originalName' => $latest->original_name ?? null,
                'createdAt' => $latest ? $latest->created_at->format('M d, Y') : null,
            ];
        })->values();

        return view('documents', compact('requirements', 'submissions'));
    })->name('documents');

    Route::post('/documents/submit-requirement', function (Request $request) {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->signature_path) {
            return redirect()->route('documents')->with('error', 'Please set up your e-signature before submitting documents. Go to "My Signature" in your profile menu.');
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'document_type' => ['required', 'in:' . implode(',', array_keys(DocumentSubmission::TYPES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('document');

        $submission = DocumentSubmission::create([
            'user_id' => $user->id,
            'document_type' => $request->input('document_type'),
            'notes' => $request->input('notes'),
            'file_path' => $file->store('documents', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'signed_at' => now(),
        ]);

        AuditLog::record('Document Submitted', 'Student ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . ') submitted ' . $submission->typeLabel() . ' and e-signed the submission.', 'DocumentSubmission', $submission->id);

        return redirect()->route('documents')->with('success', 'Your document has been submitted to the Registrar for review.');
    })->name('documents.submitRequirement');

    // 3. Enrollment Hub Module
    Route::get('/enrollment', function () {
        $user = Auth::user();
        $clearance = $user ? Clearance::currentFor($user) : null;

        $grades      = StudentGrade::where('user_id', $user->id)->get();
        $passedCodes = $grades->where('status', 'Passed')->pluck('subject_code')->values()->toArray();
        $failedCodes = $grades->where('status', 'Failed')->pluck('subject_code')->values()->toArray();
        $isIrregular = count($failedCodes) > 0;

        $yearMap = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4];
        $yearNum = $yearMap[$user->year_level] ?? 1;

        return view('enrollment', compact('clearance', 'passedCodes', 'failedCodes', 'isIrregular', 'yearNum'));
    })->name('enrollment');

    Route::get('/enrollment/{enrollment}/cor', function (Enrollment $enrollment, FeeAssessmentService $fees) {
        $user = Auth::user();
        abort_unless($enrollment->user_id === $user->id, 403);
        abort_unless($enrollment->status === 'enrolled', 403, 'This enrollment is not yet approved.');

        $enrollment->loadMissing('sections.subject', 'user');
        $clearance = Clearance::currentFor($user);
        $breakdown = $fees->breakdownFor($user);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('enrollment.cor-print', compact('enrollment', 'clearance', 'breakdown'));
        return $pdf->stream('COR-' . $user->login_id . '.pdf');
    })->name('enrollment.cor');

    // 5. Ledger Workspace Module (view renamed to `payment`)
    Route::get('/ledger', function (FeeAssessmentService $fees) {
        $user = Auth::user();
        $clearance = $user ? Clearance::currentFor($user) : null;
        $breakdown = $fees->breakdownFor($user);
        $history = TransactionLedger::where('user_id', $user->id)->latest()->get();
        $hasPendingGateway = $history->contains(fn ($row) => $row->gateway === 'paymongo' && $row->status === 'Pending');

        return view('payment', compact('clearance', 'breakdown', 'history', 'hasPendingGateway'));
    })->name('ledger');

    Route::post('/ledger/checkout', function (Request $request, FeeAssessmentService $fees, PaymentService $payments) {
        $user = Auth::user();
        $breakdown = $fees->breakdownFor($user);

        if ($breakdown['balance'] <= 0) {
            return redirect()->route('ledger')->with('error', 'You have no outstanding balance to pay.');
        }

        // The amount is always computed server-side from the student's own
        // assessment — never trust a client-submitted amount for a payment.
        if ($request->input('type') === 'down_payment') {
            $amount = max($breakdown['down_payment_required'] - $breakdown['paid'], 0);
            if ($amount <= 0) {
                return redirect()->route('ledger')->with('error', 'Your down payment requirement is already met.');
            }
        } else {
            $amount = $breakdown['balance'];
        }

        try {
            $url = $payments->startCheckout($user, (float) $amount);
        } catch (PaymentGatewayException $e) {
            return redirect()->route('ledger')->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    })->name('ledger.checkout')->middleware('throttle:6,1,ledger-checkout');

    Route::get('/ledger/payment/return', function (PaymentService $payments) {
        try {
            $result = $payments->verifyLatestPending(Auth::user());
        } catch (PaymentGatewayException $e) {
            return redirect()->route('ledger')->with('error', $e->getMessage());
        }

        return redirect()->route('ledger')->with($result['ok'] ? 'success' : 'error', $result['message']);
    })->name('ledger.payment.return')->middleware('throttle:10,1,ledger-return');

    Route::get('/ledger/payment/cancel', function (PaymentService $payments) {
        $payments->cancelLatestPending(Auth::user());

        return redirect()->route('ledger')->with('error', 'Payment cancelled. Your balance is unchanged.');
    })->name('ledger.payment.cancel');

    Route::post('/ledger/verify', function (PaymentService $payments) {
        try {
            $result = $payments->verifyLatestPending(Auth::user());
        } catch (PaymentGatewayException $e) {
            return redirect()->route('ledger')->with('error', $e->getMessage());
        }

        return redirect()->route('ledger')->with($result['ok'] ? 'success' : 'error', $result['message']);
    })->name('ledger.verify')->middleware('throttle:10,1,ledger-verify');

    // 5. Certificate of Registration (COR) View
    Route::get('/cor', [AuthController::class, 'showCor'])->name('cor');
    Route::get('/clearance/{id}/print', function ($id) {
        $clearance = Clearance::with(['user', 'items.department', 'chairSignedBy', 'cashierSignedBy', 'registrarSignedBy'])
            ->findOrFail($id);

        $user = Auth::user();
        abort_unless($user->id === $clearance->user_id || in_array($user->role, ['admin', 'registrar']), 403);

        $isCleared = $clearance->admission_status === 'Approved'
            && $clearance->chair_status === 'Approved'
            && $clearance->cashier_status === 'Approved'
            && $clearance->registrar_status === 'Approved'
            && $clearance->allItemsApproved();

        abort_unless($isCleared, 403, 'Clearance is not yet fully approved.');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('clearance.print', compact('clearance'));
        return $pdf->stream('clearance-' . $clearance->user->login_id . '.pdf');
    })->name('clearance.print');	

    /*
    |--------------------------------------------------------------------------
    | Staff & Faculty Administration Core Layer
    |--------------------------------------------------------------------------
    | Note: These groups can be further isolated by custom middleware roles if needed
    */
    
    // --- CONSOLIDATED REGISTRAR & ADMISSION WORKSPACE ---
    Route::middleware('role:registrar,admission')->group(function () {
    Route::get('/registrar/dashboard', function (\App\Services\AdmissionWithdrawalService $withdrawals) {
        $user       = Auth::user();
        $clearances = Clearance::has('user')->excludingWithdrawn()->with('user')
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->get();
        $applicants = User::where('role', 'applicant')->with('agreements')->orderByDesc('created_at')->get();

        $pendingGradeApprovals = GradeSubmission::with(['section.subject', 'section.faculty', 'items.user'])
            ->where('status', 'pending_registrar')
            ->latest('chair_at')
            ->get();

        // Temporary passwords this Registrar generated in the last 15 minutes and hasn't dismissed.
        $tempPasswords = collect(session('tempPasswords', []))
            ->filter(fn ($t) => $t['createdAt'] > now()->subMinutes(15)->timestamp)->values();
        session(['tempPasswords' => $tempPasswords->all()]);
        $tempPasswords = $tempPasswords->map(fn ($t) => $t + [
            'dismissUrl' => route('registrar.password-resets.dismiss', $t['studentId']),
            'createdAtFormatted' => \Illuminate\Support\Carbon::createFromTimestamp($t['createdAt'])->format('g:i A'),
        ]);

        $passwordResetRequests = User::where('role', 'student')
            ->whereNotNull('password_reset_requested_at')
            ->orderBy('password_reset_requested_at')
            ->get();

        $canManageWithdrawals = $user->role === 'registrar';
        $noShows = $canManageWithdrawals ? $withdrawals->possibleNoShows() : collect();
        $withdrawnStudents = $canManageWithdrawals ? $withdrawals->withdrawn() : collect();
        $noShowThresholdDays = $withdrawals->thresholdDays();

        return view('registrar.dashboard', compact(
            'clearances', 'applicants', 'pendingGradeApprovals', 'passwordResetRequests', 'tempPasswords',
            'canManageWithdrawals', 'noShows', 'withdrawnStudents', 'noShowThresholdDays'
        ));
    })->name('registrar.dashboard');

    Route::post('/registrar/password-resets/{student}', function (Request $request, User $student) {
        abort_unless($student->role === 'student', 404);

        // Handed over in person, so skip look-alikes (I/l/1, O/o/0).
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $tempPassword = '';
        for ($i = 0; $i < 12; $i++) {
            $tempPassword .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $student->forceFill([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
            'password_reset_requested_at' => null,
        ])->save();

        AuditLog::record(
            'Student Password Reset',
            'Registrar reset the password for ' . $student->name . ' (' . $student->login_id . ').',
            'User',
            $student->id
        );

        // Kept in this Registrar's session (not the database) so it survives a
        // reload or a stray click, until they press Done or 15 minutes pass.
        // The student must change it at first login anyway.
        $pending = collect(session('tempPasswords', []))->reject(fn ($t) => $t['studentId'] === $student->id);
        $pending->push([
            'studentId' => $student->id,
            'name' => $student->name,
            'loginId' => $student->login_id,
            'password' => $tempPassword,
            'createdAt' => now()->timestamp,
        ]);
        session(['tempPasswords' => $pending->values()->all()]);

        return back()->with('success', "Password reset for {$student->name}.");
    })->name('registrar.password-resets.reset')->middleware('password.confirm:,900');

    Route::post('/registrar/password-resets/{student}/dismiss', function (User $student) {
        session(['tempPasswords' => collect(session('tempPasswords', []))
            ->reject(fn ($t) => $t['studentId'] === $student->id)->values()->all()]);

        return back();
    })->name('registrar.password-resets.dismiss');

    // Anyone can type a student ID on Forgot password, so the Registrar can
    // turn down a request they couldn't verify in person. Password unchanged.
    Route::post('/registrar/password-resets/{student}/decline', function (User $student) {
        abort_unless($student->role === 'student', 404);
        $student->forceFill(['password_reset_requested_at' => null])->save();

        AuditLog::record(
            'Student Password Reset Declined',
            'Registrar declined the password reset request for ' . $student->name . ' (' . $student->login_id . ').',
            'User',
            $student->id
        );

        return back()->with('success', "Password reset request for {$student->name} declined.");
    })->name('registrar.password-resets.decline')->middleware('password.confirm:,900');

    // Document submissions get their own page (moved off the Dashboard, which
    // was getting crowded) — opens straight to the pending queue by default,
    // see DocumentSubmissionsTable's initialSearch/statusFilter.
    Route::get('/registrar/documents', function () {
        // The list itself is loaded on demand via registrar.documents.search
        // (see below) so the page payload doesn't grow with every document
        // ever submitted — only the counts are needed up front, for the "N
        // pending" badge.
        $latestDocuments = DocumentSubmission::orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (DocumentSubmission $submission) => $submission->user_id . ':' . $submission->document_type);

        $context = [
            'documentsSearchUrl' => route('registrar.documents.search'),
            'documentsPendingCount' => $latestDocuments->where('status', 'pending')->count(),
            'documentsRejectedCount' => $latestDocuments->where('status', 'rejected')->count(),
        ];

        return view('registrar.documents', compact('context'));
    })->name('registrar.documents')->middleware('password.confirm:,900');

    Route::get('/registrar/documents/search', function (Request $request) {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $statusFilter = in_array($status, ['pending', 'rejected'], true) ? $status : null;

        if ($q === '' && ! $statusFilter) {
            return response()->json(['documents' => []]);
        }

        $viewAll = $request->boolean('all');

        $documents = DocumentSubmission::with('user')
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('original_name', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('login_id', 'like', "%{$q}%"));
            }))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->unique(fn (DocumentSubmission $submission) => $submission->user_id . ':' . $submission->document_type)
            ->when($statusFilter, fn ($documents) => $documents->where('status', $statusFilter))
            ->when(! $statusFilter && ! $viewAll, fn ($documents) => $documents->whereIn('status', ['pending', 'rejected']))
            ->values();

        return response()->json([
            'documents' => $documents->map(fn ($doc) => [
                'id' => $doc->id,
                'studentName' => $doc->user->name ?? '—',
                'studentId' => $doc->user->login_id ?? '—',
                'typeLabel' => $doc->typeLabel(),
                'documentUrl' => route('documents.show', $doc),
                'originalName' => $doc->original_name,
                'sizeKb' => number_format($doc->size / 1024, 0),
                'notes' => $doc->notes,
                'status' => $doc->status,
                'remarks' => $doc->remarks,
                'createdAtFormatted' => $doc->created_at->format('M d, Y g:i A'),
                'acceptUrl' => route('registrar.documents.accept', $doc),
                'rejectUrl' => route('registrar.documents.reject', $doc),
                'reminderUrl' => route('registrar.documents.remind', $doc),
            ])->values(),
        ]);
    })->name('registrar.documents.search')->middleware('password.confirm:,900');

    Route::get('/registrar/applicants/{id}/agreement', [\App\Http\Controllers\AgreementController::class, 'downloadForUser'])
        ->name('registrar.applicant-agreement');

    // Admission Slots: lets the Registrar set how many applicants each curriculum
    // can take for the current school year. Splitting students into blocks is the
    // Chair's job (Section capacities), so there's no "sections" setting here.
    Route::get('/registrar/slots', function () {
        $schoolYear = \App\Models\Setting::get('school_year', '2026-2027');

        // Build one row per curriculum, creating its slot-limit record on the fly
        // (200 slots by default) the first time it's viewed.
        $enrollment = app(\App\Services\EnrollmentService::class);
        $curricula = collect(config('curricula'))->map(function ($prog) use ($schoolYear, $enrollment) {
            $limit = \App\Models\AdmissionSlotLimit::forProgram($prog['id'], $prog['name'], $schoolYear);

            return [
                'id'           => $limit->id,
                'programKey'   => $prog['id'],
                'programName'  => $prog['name'],
                'level'        => $prog['level'],
                'totalSlots'   => $limit->total_slots,
                'taken'        => $limit->takenCount(),
                'slotsLeft'    => $limit->slotsLeft(),
                // The Chair's 1st-year blocks this term, so the Registrar can
                // keep accepted applicants in line with the seats that exist.
                'blocks'       => $enrollment->firstYearBlockSeats(
                    \App\Models\Program::where('code', $prog['program_code'] ?? null)->first()
                ),
                'updateUrl'    => route('registrar.slots.update', $limit->id),
            ];
        });

        // Current term + the one "Start next semester" should suggest:
        // Sem 1 -> Sem 2 of the same year; Sem 2 -> Sem 1 of the next year.
        $semester = (int) \App\Models\Setting::get('semester', '1');
        $startYear = (int) substr($schoolYear, 0, 4);
        $term = [
            'current' => ['schoolYear' => $schoolYear, 'semester' => $semester],
            'next' => $semester === 1
                ? ['schoolYear' => $schoolYear, 'semester' => 2]
                : ['schoolYear' => ($startYear + 1) . '-' . ($startYear + 2), 'semester' => 1],
        ];

        return view('registrar.slots', compact('curricula', 'schoolYear', 'term'));
    })->name('registrar.slots');

    // Update the total slot limit for one curriculum.
    Route::post('/registrar/slots/{admissionSlotLimit}', function (Request $request, \App\Models\AdmissionSlotLimit $admissionSlotLimit) {
        $data = $request->validate([
            'total_slots' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $admissionSlotLimit->update($data);

        AuditLog::record(
            'Admission Slot Limit Updated',
            'Registrar set ' . $admissionSlotLimit->program_name . ' (' . $admissionSlotLimit->school_year . ') to ' .
                $data['total_slots'] . ' total slots.',
            'AdmissionSlotLimit',
            $admissionSlotLimit->id
        );

        return redirect()->route('registrar.slots')->with('success', 'Slot limit for ' . $admissionSlotLimit->program_name . ' updated.');
    })->name('registrar.slots.update');

    // Action Handlers for Data Handshakes
    Route::post('/admission/approve/{id}', function ($id) {
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update([
                'admission_status' => 'Approved',
                'registrar_status' => 'Pending',
            ]);
            AuditLog::record('Admission Approved', 'Admission approved for student ID ' . ($clearance->user->login_id ?? $clearance->user_id) . ' (' . ($clearance->user->name ?? 'Unknown') . '). Forwarded to Registrar.', 'Clearance', $clearance->id);
            return redirect()->route('registrar.dashboard')->with('success', 'Student credentials approved. Profile forwarded to Registrar.');
        }
        return redirect()->route('registrar.dashboard')->with('error', 'Clearance profile row lookup failed.');
    })->name('admission.approve');

        Route::post('/registrar/sign/{id}', function ($id) {
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update([ 'registrar_status' => 'Approved',
                'registrar_signed_by' => Auth::id(),
                'registrar_signed_at' => now(),
                'remarks' => null,]);
            AuditLog::record('Clearance Signed', 'Registrar signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
            \App\Support\SafeNotify::send($clearance->user,new ClearanceStatusUpdatedNotification('Registrar', 'Approved'));
        }
        return redirect()->route('registrar.dashboard')->with('success', 'Student credentials verified successfully.');
    })->name('registrar.sign');

    Route::post('/registrar/hold/{id}', function (Request $request, $id) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update(['registrar_status' => 'Hold', 'remarks' => $data['remarks']]);
            AuditLog::record('Clearance Held', 'Registrar held clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ': ' . $data['remarks'], 'Clearance', $clearance->id);
            \App\Support\SafeNotify::send($clearance->user,new ClearanceStatusUpdatedNotification('Registrar', 'Hold', $data['remarks']));
            return redirect()->route('registrar.dashboard')->with('success', 'Clearance held with remarks.');
        }
        return redirect()->route('registrar.dashboard')->with('error', 'Record not found.');
    })->name('registrar.hold');

    // Document submission review
    Route::post('/registrar/documents/{submission}/accept', function (DocumentSubmission $submission) {
        if ($submission->status !== 'pending') {
            return redirect()->route('registrar.documents')->with('error', 'This document has already been reviewed.');
        }

        $submission->update(['status' => 'accepted', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        AuditLog::record('Document Reviewed', 'Registrar accepted ' . $submission->typeLabel() . ' from ' . ($submission->user->name ?? 'ID ' . $submission->user_id) . '.', 'DocumentSubmission', $submission->id);

        if ($clearance = Clearance::currentFor($submission->user)) {
            $clearance->update(['registrar_status' => 'Approved', 'remarks' => null]);
        }

        return redirect()->route('registrar.documents')->with('success', 'Document accepted.');
    })->name('registrar.documents.accept');

    Route::post('/registrar/documents/{submission}/reject', function (Request $request, DocumentSubmission $submission) {
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($submission->status !== 'pending') {
            return redirect()->route('registrar.documents')->with('error', 'This document has already been reviewed.');
        }

        $submission->update(['status' => 'rejected', 'remarks' => $request->input('remarks'), 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        AuditLog::record('Document Reviewed', 'Registrar rejected ' . $submission->typeLabel() . ' from ' . ($submission->user->name ?? 'ID ' . $submission->user_id) . '.', 'DocumentSubmission', $submission->id);
        \App\Support\SafeNotify::send($submission->user, new DocumentRejectedNotification($submission, $request->input('remarks')));

        if ($clearance = Clearance::currentFor($submission->user)) {
            $clearance->update(['registrar_status' => 'Hold', 'remarks' => $request->input('remarks')]);
        }

        return redirect()->route('registrar.documents')->with('success', 'Document rejected and returned to the student.');
    })->name('registrar.documents.reject');

    Route::post('/registrar/documents/{submission}/remind', function (DocumentSubmission $submission) {
        abort_unless(in_array($submission->status, ['pending', 'rejected'], true), 422, 'Only pending or rejected documents can receive reminders.');

        \App\Support\SafeNotify::send($submission->user, new DocumentStatusReminderNotification($submission));

        return redirect()->route('registrar.documents')->with('success', 'Reminder sent to the student.');
    })->name('registrar.documents.remind');

    // Once a GradeSubmission reaches 'approved', there is no built-in way to
    // reopen it (by design — no correction flow), including for the edge
    // case of a student being re-enrolled into an already-approved section;
    // that scenario currently requires a direct database intervention.
    Route::post('/registrar/grades/{submission}/approve', function (GradeSubmission $submission) {
        DB::transaction(function () use ($submission) {
            // Atomic conditional update, not abort-then-update: two concurrent
            // approve requests both reading 'pending_registrar' before either
            // writes could otherwise both pass a separate status check and
            // both finalize. Only one UPDATE...WHERE can match the row.
            $claimed = GradeSubmission::where('id', $submission->id)
                ->where('status', 'pending_registrar')
                ->update(['status' => 'approved', 'registrar_id' => Auth::id(), 'registrar_at' => now()]);
            abort_unless($claimed === 1, 403, 'This submission is not awaiting Registrar approval.');

            $submission->refresh()->load('items', 'section.subject');
            $enrolledIds = $submission->section->enrolledStudentIds();

            $rows = $submission->items
                ->filter(fn ($item) => $enrolledIds->contains($item->user_id))
                // Student dropped/swapped out of the section after being
                // graded but before Registrar approval — leave the stale
                // grade_submission_item row in place, but don't finalize
                // it into student_grades.
                ->map(fn ($item) => [
                    'user_id' => $item->user_id,
                    'subject_code' => $submission->section->subject->code,
                    'status' => $item->status,
                    'final_grade' => $item->final_grade,
                    'source' => null, // an in-system grade replaces any earlier credit
                ])
                ->values()
                ->all();

            if (! empty($rows)) {
                StudentGrade::upsert($rows, ['user_id', 'subject_code'], ['status', 'final_grade', 'source']);
            }
        });

        AuditLog::record(
            'Grades Finalized',
            'Registrar finalized grades for ' . $submission->section->subject->code . ' (Block ' . $submission->section->block_label . ').',
            'GradeSubmission',
            $submission->id
        );

        return back()->with('success', 'Grades finalized and posted to student records.');
    })->name('registrar.grades.approve');

    Route::post('/registrar/grades/{submission}/reject', function (Request $request, GradeSubmission $submission) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        $updated = GradeSubmission::where('id', $submission->id)
            ->where('status', 'pending_registrar')
            ->update(['status' => 'draft', 'rejected_by' => 'registrar', 'remarks' => $data['remarks']]);
        abort_unless($updated === 1, 403, 'This submission is not awaiting Registrar approval.');
        $submission->refresh();

        $submission->load('section.subject', 'section.faculty');
        AuditLog::record(
            'Grades Registrar-Rejected',
            'Registrar rejected grades for ' . $submission->section->subject->code . ' (Block ' . $submission->section->block_label . '): ' . $data['remarks'],
            'GradeSubmission',
            $submission->id
        );
        \App\Support\SafeNotify::send($submission->section->faculty, new \App\Notifications\GradeSubmissionRejectedNotification('Registrar', $submission->section, $data['remarks']));

        return back()->with('success', 'Grades returned to faculty with remarks.');
    })->name('registrar.grades.reject');
    }); // end role:registrar,admission


    // --- DEPARTMENT CHAIR HUB ENDPOINTS ---
    Route::middleware('role:chair')->group(function () {
    Route::get('/approver/dashboard', function () {
        $clearances = Clearance::has('user')->excludingWithdrawn()->with('user')
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->get();
        $pendingEnrollments = Enrollment::with(['user', 'sections.subject', 'sections.roomEntity'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        $pendingChanges = MatriculationChange::with(['user', 'items.section.subject', 'items.section.roomEntity', 'items.replacedSection.subject'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        $pendingGradeSubmissions = GradeSubmission::with(['section.subject', 'section.faculty', 'items.user'])
            ->where('status', 'pending_chair')
            ->latest('submitted_at')
            ->get();
        $pendingCredits = \App\Models\SubjectCreditRequest::with(['user', 'requester', 'items'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        return view('approver.dashboard', compact('clearances', 'pendingEnrollments', 'pendingChanges', 'pendingGradeSubmissions', 'pendingCredits'));
    })->name('approver.dashboard');

    // Scheduling — sections (day/time/faculty/room) plus faculty/room
    // management, moved here from the Registrar's Curriculum page. This
    // page's React island browses the subject list read-only (via the same
    // GET /api/admin/programs endpoints Curriculum below uses) to find what
    // to schedule.
    Route::get('/approver/scheduling', function () {
        return view('approver.scheduling');
    })->name('approver.scheduling');

    // Curriculum editing — moved here from the Registrar (who inherited it
    // from Admin) so Programs/Subjects content and section scheduling live
    // under the same Dept Chair workspace.
    Route::get('/approver/curriculum', function () {
        return view('approver.curriculum');
    })->name('approver.curriculum');

    Route::post('/approver/enrollments/{enrollment}/approve', function (Enrollment $enrollment) {
        if ($enrollment->status !== 'pending') {
            return back()->with('error', 'This enrollment is no longer pending.');
        }

        try {
            DB::transaction(function () use ($enrollment) {
                $locked = Section::whereIn('id', $enrollment->sections()->pluck('sections.id'))->lockForUpdate()->get();
                foreach ($locked as $section) {
                    // The pending enrollment's own rows already count in enrolledCount()
                    // (they are non-rejected), so the section is oversubscribed only when
                    // the count exceeds capacity.
                    if ($section->enrolledCount() > $section->capacity) {
                        throw new \App\Exceptions\EnrollmentException('No seats left in ' . $section->subject->code . '.');
                    }
                }
                $enrollment->update(['status' => 'enrolled', 'remarks' => null]);
            });
        } catch (\App\Exceptions\EnrollmentException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::record(
            'Enrollment Approved',
            'Department Chair approved irregular enrollment for ' . ($enrollment->user->name ?? 'ID ' . $enrollment->user_id) . ' (' . ($enrollment->user->login_id ?? 'N/A') . ').',
            'Enrollment',
            $enrollment->id
        );

        return back()->with('success', 'Enrollment approved.');
    })->name('approver.enrollments.approve');

    Route::post('/approver/enrollments/{enrollment}/reject', function (Request $request, Enrollment $enrollment) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($enrollment->status !== 'pending') {
            return back()->with('error', 'This enrollment is no longer pending.');
        }

        $enrollment->update(['status' => 'rejected', 'remarks' => $data['remarks']]);

        AuditLog::record(
            'Enrollment Rejected',
            'Department Chair rejected enrollment for ' . ($enrollment->user->name ?? 'ID ' . $enrollment->user_id) . ': ' . $data['remarks'],
            'Enrollment',
            $enrollment->id
        );

        return back()->with('success', 'Enrollment returned to the student with remarks.');
    })->name('approver.enrollments.reject');

    Route::post('/approver/matriculation/{change}/approve', function (MatriculationChange $change) {
        try {
            app(MatriculationChangeService::class)->approve($change);
        } catch (\App\Exceptions\EnrollmentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Change of matriculation approved.');
    })->name('approver.matriculation.approve');

    Route::post('/approver/matriculation/{change}/reject', function (Request $request, MatriculationChange $change) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);
        try {
            app(MatriculationChangeService::class)->reject($change, $data['remarks']);
        } catch (\App\Exceptions\EnrollmentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Change request returned to the student with remarks.');
    })->name('approver.matriculation.reject');

        Route::post('/approver/sign/{id}', function ($id) {
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update([ 'chair_status' => 'Approved',
                'chair_signed_by' => Auth::id(),
                'chair_signed_at' => now(),
                'remarks' => null,]);
            AuditLog::record('Clearance Signed', 'Department Chair signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
            \App\Support\SafeNotify::send($clearance->user,new ClearanceStatusUpdatedNotification('Department Chair', 'Approved'));
            return redirect()->route('approver.dashboard')->with('success', 'Department structural sign-off written successfully.');
        }
        return redirect()->route('approver.dashboard')->with('error', 'Record not found.');
    })->name('approver.sign');

    Route::post('/approver/hold/{id}', function (Request $request, $id) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update(['chair_status' => 'Hold', 'remarks' => $data['remarks']]);
            AuditLog::record('Clearance Held', 'Department Chair held clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ': ' . $data['remarks'], 'Clearance', $clearance->id);
            \App\Support\SafeNotify::send($clearance->user,new ClearanceStatusUpdatedNotification('Department Chair', 'Hold', $data['remarks']));
            return redirect()->route('approver.dashboard')->with('success', 'Clearance held with remarks.');
        }
        return redirect()->route('approver.dashboard')->with('error', 'Record not found.');
    })->name('approver.hold');

    Route::post('/approver/grades/{submission}/approve', function (GradeSubmission $submission) {
        $updated = GradeSubmission::where('id', $submission->id)
            ->where('status', 'pending_chair')
            ->update(['status' => 'pending_registrar', 'chair_id' => Auth::id(), 'chair_at' => now()]);
        abort_unless($updated === 1, 403, 'This submission is not awaiting Chair approval.');
        $submission->refresh();

        $submission->load('section.subject');
        AuditLog::record(
            'Grades Chair-Approved',
            'Department Chair approved grades for ' . $submission->section->subject->code . ' (Block ' . $submission->section->block_label . ').',
            'GradeSubmission',
            $submission->id
        );

        return back()->with('success', 'Grades approved and forwarded to the Registrar.');
    })->name('approver.grades.approve');

    Route::post('/approver/grades/{submission}/reject', function (Request $request, GradeSubmission $submission) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        $updated = GradeSubmission::where('id', $submission->id)
            ->where('status', 'pending_chair')
            ->update(['status' => 'draft', 'rejected_by' => 'chair', 'remarks' => $data['remarks']]);
        abort_unless($updated === 1, 403, 'This submission is not awaiting Chair approval.');
        $submission->refresh();

        $submission->load('section.subject', 'section.faculty');
        AuditLog::record(
            'Grades Chair-Rejected',
            'Department Chair rejected grades for ' . $submission->section->subject->code . ' (Block ' . $submission->section->block_label . '): ' . $data['remarks'],
            'GradeSubmission',
            $submission->id
        );
        \App\Support\SafeNotify::send($submission->section->faculty, new \App\Notifications\GradeSubmissionRejectedNotification('Department Chair', $submission->section, $data['remarks']));

        return back()->with('success', 'Grades returned to faculty with remarks.');
    })->name('approver.grades.reject');

    // Subject credits submitted by the Registrar for transferees/returnees.
    Route::post('/approver/credits/{creditRequest}/approve', function (\App\Models\SubjectCreditRequest $creditRequest, \App\Services\SubjectCreditService $credits) {
        $credits->approve($creditRequest, Auth::user());

        return back()->with('success', 'Subject credits approved.');
    })->name('approver.credits.approve');

    Route::post('/approver/credits/{creditRequest}/reject', function (Request $request, \App\Models\SubjectCreditRequest $creditRequest, \App\Services\SubjectCreditService $credits) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);
        $credits->reject($creditRequest, Auth::user(), $data['remarks']);

        return back()->with('success', 'Subject credits returned to the Registrar.');
    })->name('approver.credits.reject');
    }); // end role:chair

    Route::middleware('role:faculty')->group(function () {
        Route::get('/faculty/schedule', function () {
            $sections = auth()->user()->taughtSections()
                ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
                ->with(['subject', 'roomEntity'])
                ->orderBy('start_time')
                ->get();

            $dayNames = ['M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];

            $context = [
                'teacherName' => auth()->user()->name,
                'schoolYear' => \App\Models\Setting::get('school_year', '2026-2027'),
                'days' => collect($dayNames)->map(function ($label, $key) use ($sections) {
                    return [
                        'label' => $label,
                        'sections' => $sections->filter(fn ($s) => in_array($key, $s->days))->map(fn ($s) => [
                            'subjectCode' => $s->subject->code,
                            'subjectTitle' => $s->subject->title,
                            'blockLabel' => $s->block_label,
                            'timeRange' => $s->start_time . '–' . $s->end_time,
                            'roomLabel' => $s->roomLabel(),
                            'isOnline' => (bool) ($s->roomEntity && ! $s->roomEntity->isPhysical()),
                        ])->values(),
                    ];
                })->filter(fn ($day) => $day['sections']->isNotEmpty())->values(),
            ];

            return view('faculty.schedule', ['context' => $context]);
        })->name('faculty.schedule');

        Route::get('/faculty/sections', function () {
            $sections = auth()->user()->taughtSections()
                ->where('school_year', Setting::get('school_year', '2026-2027'))
                ->with('subject')
                ->orderBy('block_label')
                ->get()
                ->map(fn (Section $s) => [
                    'id' => $s->id,
                    'subjectCode' => $s->subject->code,
                    'subjectTitle' => $s->subject->title,
                    'blockLabel' => $s->block_label,
                    'enrolledCount' => $s->enrolledCount(),
                ]);

            return view('faculty.sections', compact('sections'));
        })->name('faculty.sections');

        Route::get('/faculty/sections/{section}/grades', function (Section $section) {
            abort_unless($section->faculty_id === auth()->id(), 403);
            $section->load('subject');

            $students = $section->enrollments()
                ->where('enrollments.status', '!=', 'rejected')
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter()
                ->sortBy('name')
                ->values();

            try {
                $submission = GradeSubmission::firstOrCreate(
                    ['section_id' => $section->id],
                    ['faculty_id' => auth()->id(), 'status' => 'draft']
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Two requests for a section with no GradeSubmission row yet
                // (e.g. two tabs) can both miss the initial SELECT and race
                // on the section_id unique constraint. The loser just re-reads
                // the row the winner created.
                $submission = GradeSubmission::where('section_id', $section->id)->firstOrFail();
            }

            $items = $submission->items()->get()->keyBy('user_id');

            return view('faculty.section-grades', compact('section', 'students', 'submission', 'items'));
        })->name('faculty.sections.grades');

        Route::post('/faculty/sections/{section}/grades', function (Request $request, Section $section) {
            abort_unless($section->faculty_id === auth()->id(), 403);
            $section->load('subject');

            try {
                $submission = GradeSubmission::firstOrCreate(
                    ['section_id' => $section->id],
                    ['faculty_id' => auth()->id(), 'status' => 'draft']
                );
            } catch (\Illuminate\Database\QueryException $e) {
                $submission = GradeSubmission::where('section_id', $section->id)->firstOrFail();
            }
            abort_unless($submission->status === 'draft', 403, 'This section\'s grades are not editable right now.');

            $enrolledIds = $section->enrolledStudentIds();

            foreach ($request->input('grades', []) as $userId => $rawGrade) {
                if (! $enrolledIds->contains((int) $userId)) {
                    continue;
                }

                $grade = is_numeric($rawGrade) ? (int) $rawGrade : null;

                if ($grade === null) {
                    GradeSubmissionItem::where('grade_submission_id', $submission->id)->where('user_id', $userId)->delete();
                } else {
                    GradeSubmissionItem::updateOrCreate(
                        ['grade_submission_id' => $submission->id, 'user_id' => $userId],
                        ['final_grade' => (string) $grade, 'status' => $grade >= 75 ? 'Passed' : 'Failed']
                    );
                }
            }

            AuditLog::record(
                'Grades Updated',
                auth()->user()->name . ' recorded grades for ' . $section->subject->code . ' (Block ' . $section->block_label . ').',
                'Section',
                $section->id
            );

            return redirect()->route('faculty.sections.grades', $section->id)->with('success', 'Grades saved.');
        })->name('faculty.sections.grades.store');

        Route::post('/faculty/sections/{section}/grades/submit', function (Section $section) {
            abort_unless($section->faculty_id === auth()->id(), 403);

            $submission = GradeSubmission::where('section_id', $section->id)->first();
            abort_unless($submission && $submission->status === 'draft', 403, 'This section\'s grades are not in a submittable state.');

            $enrolledIds = $section->enrolledStudentIds();

            if ($enrolledIds->isEmpty()) {
                return back()->with('error', 'This section has no enrolled students to submit grades for.');
            }

            $gradedIds = $submission->items()->pluck('user_id');
            $missing = $enrolledIds->diff($gradedIds);

            if ($missing->isNotEmpty()) {
                return back()->with('error', 'Enter a grade for every enrolled student before submitting.');
            }

            $section->load('subject');
            $submission->update([
                'status' => 'pending_chair',
                'submitted_at' => now(),
                'remarks' => null,
                'rejected_by' => null,
                'faculty_id' => auth()->id(),
                'chair_id' => null,
                'chair_at' => null,
            ]);

            AuditLog::record(
                'Grades Submitted for Approval',
                auth()->user()->name . ' submitted grades for ' . $section->subject->code . ' (Block ' . $section->block_label . ') for Department Chair approval.',
                'Section',
                $section->id
            );

            return redirect()->route('faculty.sections.grades', $section->id)->with('success', 'Grades submitted for approval.');
        })->name('faculty.sections.grades.submit');
    }); // end role:faculty

    // --- DEPARTMENT OFFICER QUEUE ---
    Route::middleware('role:department_officer')->group(function () {
    Route::get('/department/dashboard', function () {
        $officer = Auth::user();
        $items = ClearanceItem::where('department_id', $officer->department_id)
            ->whereHas('clearance', function ($query) {
                $query->where('school_year', Setting::get('school_year', '2026-2027'))
                    ->where('semester', (int) Setting::get('semester', '1'))
                    ->excludingWithdrawn();
            })
            ->whereHas('clearance.user')
            ->with('clearance.user')
            ->get();

        return view('department.dashboard', compact('items'));
    })->name('department.dashboard');

    Route::post('/department/items/{item}/approve', function (ClearanceItem $item) {
        abort_unless($item->department_id === Auth::user()->department_id, 403);

        $item->update(['status' => 'Approved', 'remarks' => null, 'signed_by' => Auth::id(), 'signed_at' => now()]);
        AuditLog::record('Clearance Signed', Auth::user()->name . ' approved ' . $item->department->name . ' clearance for ' . ($item->clearance->user->name ?? 'ID ' . $item->clearance->user_id) . '.', 'ClearanceItem', $item->id);
        \App\Support\SafeNotify::send($item->clearance->user,new ClearanceStatusUpdatedNotification($item->department->name ?? 'Department Office', 'Approved'));

        return redirect()->route('department.dashboard')->with('success', 'Clearance item approved.');
    })->name('department.items.approve');

    Route::post('/department/items/{item}/hold', function (Request $request, ClearanceItem $item) {
        abort_unless($item->department_id === Auth::user()->department_id, 403);
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        $item->update(['status' => 'Hold', 'remarks' => $data['remarks'], 'signed_by' => Auth::id(), 'signed_at' => now()]);
        AuditLog::record('Clearance Held', Auth::user()->name . ' held ' . $item->department->name . ' clearance for ' . ($item->clearance->user->name ?? 'ID ' . $item->clearance->user_id) . ': ' . $data['remarks'], 'ClearanceItem', $item->id);
        \App\Support\SafeNotify::send($item->clearance->user,new ClearanceStatusUpdatedNotification($item->department->name ?? 'Department Office', 'Hold', $data['remarks']));

        return redirect()->route('department.dashboard')->with('success', 'Clearance item held with remarks.');
    })->name('department.items.hold');
    }); // end role:department_officer

    // Secure document view/download: owner or registrar/admission only.
    Route::get('/documents/{submission}', function (DocumentSubmission $submission) {
        $user = Auth::user();
        $allowed = $user->id === $submission->user_id || in_array($user->role, ['registrar', 'admission']);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->response($submission->file_path, $submission->original_name);
    })->middleware('auth')->name('documents.show');

    // Announcement attachment: any signed-in user may view it — announcements
    // themselves aren't role-scoped, they're shown app-wide.
    Route::get('/announcements/{announcement}/attachment', function (Announcement $announcement) {
        abort_unless($announcement->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($announcement->attachment_path), 404);

        return Storage::disk('local')->response($announcement->attachment_path, $announcement->attachment_name);
    })->middleware('auth')->name('announcements.attachment');

    // Saved preview picture of an announcement's link (see LinkPreviewImageFetcher).
    Route::get('/announcements/{announcement}/link-image', function (Announcement $announcement) {
        abort_unless($announcement->link_image_path, 404);
        abort_unless(Storage::disk('local')->exists($announcement->link_image_path), 404);

        return Storage::disk('local')->response($announcement->link_image_path);
    })->middleware('auth')->name('announcements.link-image');


    // --- CASHIER HUB ENDPOINTS ---
    Route::middleware('role:cashier')->group(function () {
    Route::get('/cashier/dashboard', [AuthController::class, 'showCashierDashboard'])->name('cashier.dashboard');
    Route::post('/cashier/approve', [AuthController::class, 'approveClearance'])->name('cashier.approve');

    Route::post('/cashier/hold', function (Request $request) {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);
        $clearance = Clearance::currentFor(User::findOrFail($data['user_id']));
        abort_if(! $clearance, 404, 'No current-term clearance found for this student.');
        $clearance->update(['cashier_status' => 'Hold', 'remarks' => $data['remarks']]);
        AuditLog::record('Clearance Held', 'Cashier held clearance for student ID ' . $data['user_id'] . ': ' . $data['remarks'], 'Clearance', $clearance->id);
        \App\Support\SafeNotify::send($clearance->user,new ClearanceStatusUpdatedNotification('Cashier', 'Hold', $data['remarks']));

        return redirect()->back()->with('success', 'Clearance held with remarks.');
    })->name('cashier.hold');

    Route::post('/cashier/waive-down-payment', function (Request $request) {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $clearance = Clearance::currentFor(User::findOrFail($data['user_id']));
        abort_if(! $clearance, 404, 'No current-term clearance found for this student.');

        $clearance->update([
            'down_payment_waived' => true,
            'down_payment_waived_reason' => $data['reason'],
            'down_payment_waived_by' => Auth::id(),
            'down_payment_waived_at' => now(),
        ]);

        AuditLog::record(
            'Down Payment Waived',
            Auth::user()->name . ' waived the down-payment requirement for ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . '): ' . $data['reason'],
            'Clearance',
            $clearance->id
        );

        return redirect()->route('cashier.dashboard')->with('success', 'Down payment requirement waived.');
    })->name('cashier.waive-down-payment');
    Route::get('/cashier/transactions', [AuthController::class, 'showCashierTransactions'])->name('cashier.transactions')->middleware('password.confirm:,900');
    Route::get('/cashier/accounts', [AuthController::class, 'showCashierAccounts'])->name('cashier.accounts')->middleware('password.confirm:,900');

        // Billing configuration: fee rates, discount types, student assignment
    Route::get('/cashier/billing', function () {
        return view('cashier.billing', [
            'tuitionPerUnit' => (int) \App\Models\Setting::get('tuition_per_unit', '300'),
            'miscFee' => (int) \App\Models\Setting::get('misc_fee', '1500'),
            // NEW: reservation fee is now editable here instead of being a hidden default.
            'reservationFee' => (int) \App\Models\Setting::get('reservation_fee', '500'),
            // NEW: flat tuition specifically for TESDA Short-Term Programs.
            'tesdaTuitionFee' => (int) \App\Models\Setting::get('tesda_tuition_fee', '1500'),
            'downPaymentPercent' => (int) \App\Models\Setting::get('down_payment_percent', '30'),
            'discountTypes' => DiscountType::withCount('students')->orderBy('name')->get(),
            'students' => User::where('role', 'student')->with('discountType')->orderBy('name')->get(),
        ]);
    })->name('cashier.billing')->middleware('password.confirm:,900');

    Route::post('/cashier/billing/fees', function (Request $request) {
        $request->validate([
            'tuition_per_unit' => ['required', 'integer', 'min:0'],
            'misc_fee' => ['required', 'integer', 'min:0'],
            'reservation_fee' => ['required', 'integer', 'min:0'],
            'tesda_tuition_fee' => ['required', 'integer', 'min:0'],
            'down_payment_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        \App\Models\Setting::put('tuition_per_unit', (string) $request->integer('tuition_per_unit'));
        \App\Models\Setting::put('misc_fee', (string) $request->integer('misc_fee'));
        \App\Models\Setting::put('reservation_fee', (string) $request->integer('reservation_fee'));
        \App\Models\Setting::put('tesda_tuition_fee', (string) $request->integer('tesda_tuition_fee'));
        \App\Models\Setting::put('down_payment_percent', (string) $request->integer('down_payment_percent'));
        AuditLog::record('Fees Updated', 'Cashier set tuition to ₱' . $request->integer('tuition_per_unit') . '/unit, misc fee to ₱' . $request->integer('misc_fee') . ', reservation fee to ₱' . $request->integer('reservation_fee') . ', and TESDA flat tuition to ₱' . $request->integer('tesda_tuition_fee') . ' and the down-payment threshold to ' . $request->integer('down_payment_percent') . '%.', 'Setting', null);

                return redirect()->route('cashier.billing')->with('success', 'Fee rates updated.');
    })->name('cashier.billing.fees');

    Route::post('/cashier/billing/discounts', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:discount_types,name'],
            'percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $type = DiscountType::create($data);
        AuditLog::record('Discount Type Added', 'Cashier added discount type "' . $type->name . '" (' . $type->percent . '%).', 'DiscountType', $type->id);

        return redirect()->route('cashier.billing')->with('success', 'Discount type added.');
    })->name('cashier.billing.discounts');


    Route::post('/cashier/billing/discounts/{discountType}/toggle', function (Request $request, DiscountType $discountType) {
        $discountType->update(['is_active' => ! $discountType->is_active]);
        AuditLog::record(
            $discountType->is_active ? 'Discount Type Activated' : 'Discount Type Deactivated',
            'Cashier ' . ($discountType->is_active ? 'activated' : 'deactivated') . ' discount type "' . $discountType->name . '".',
            'DiscountType',
            $discountType->id
        );

        if ($request->expectsJson()) {
            return response()->json(['id' => $discountType->id, 'isActive' => $discountType->is_active]);
        }

        return redirect()->route('cashier.billing')->with('success', 'Discount type updated.');
    })->name('cashier.billing.discounts.toggle');

        Route::post('/cashier/billing/assign/{student}', function (Request $request, User $student) {
        $data = $request->validate([
            'discount_type_id' => ['nullable', 'exists:discount_types,id'],
        ]);

        $newTypeId = $data['discount_type_id'] ?? null;
        if ($newTypeId && (int) $newTypeId !== (int) $student->discount_type_id) {
            $isActive = DiscountType::whereKey($newTypeId)->where('is_active', true)->exists();
            if (! $isActive) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'That discount type is inactive.'], 422);
                }
                return redirect()->route('cashier.billing')->withErrors(['discount_type_id' => 'That discount type is inactive.']);
            }
        }

        $student->update(['discount_type_id' => $newTypeId]);
        AuditLog::record('Student Discount Updated', 'Cashier updated discount assignment for student ID ' . $student->id . '.', 'User', $student->id);

        if ($request->expectsJson()) {
            return response()->json(['id' => $student->id, 'discountTypeId' => $newTypeId]);
        }

        return redirect()->route('cashier.billing')->with('success', 'Student discount updated.');
    })->name('cashier.billing.assign');
    }); // end role:cashier
    
    // --- MASTER SYSTEM ADMINISTRATIVE LAYER ---
    Route::middleware('role:admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        $schoolYear = Setting::get('school_year', '2026-2027');
        $semester = (int) Setting::get('semester', '1');

        $termClearances = Clearance::has('user')->excludingWithdrawn()->with('items')
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->get();

        $settled = $termClearances->filter(fn (Clearance $c) => $c->chair_status === 'Approved'
            && $c->cashier_status === 'Approved'
            && $c->registrar_status === 'Approved'
            && $c->allItemsApproved());

        $roleLabels = [
            'chair' => 'Department Chair',
            'cashier' => 'Finance Cashier',
            'registrar' => 'Institutional Registrar',
        ];

        $accounts = User::whereIn('role', array_keys($roleLabels))->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'loginId' => $u->login_id,
                'role' => $u->role,
                'roleLabel' => $roleLabels[$u->role],
            ])->values();

        $context = [
            'stats' => [
                'totalActiveUsers' => User::where('role', '!=', 'withdrawn')->count(),
                'clearancesSettled' => $settled->count(),
                'pendingQueues' => $termClearances->count() - $settled->count(),
            ],
            'accounts' => $accounts,
        ];

        return view('admin.dashboard', compact('context'));
    })->name('admin.dashboard');

    Route::get('/admin/students', function (Request $request) {
        $query = User::where('role', 'student');

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('login_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('program')) {
            $query->where('major', $request->query('program'));
        }

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->query('year_level'));
        }

        $students = $query->orderBy('name')->paginate(20)->withQueryString();
        $programs = Program::orderBy('level')->orderBy('code')->get();

        return view('admin.students.index', compact('students', 'programs'));
    })->name('admin.students.index')->middleware('password.confirm:,900');

    Route::delete('/admin/students/{user}', function (User $user) {
        abort_unless($user->role === 'student', 404);

        $name = $user->name;
        $loginId = $user->login_id;
        $user->delete();

        AuditLog::record('Student Account Deleted', 'Admin deleted student account for ' . $name . ' (Login ID: ' . $loginId . ').', 'User', $user->id);

        return redirect()->route('admin.students.index')->with('success', 'Student account for ' . $name . ' was deleted.');
    })->name('admin.students.destroy');

    Route::get('/admin/audit', function () {
        $logs = AuditLog::orderByDesc('created_at')->paginate(50);
        return view('admin.audit', compact('logs'));
    })->name('admin.audit')->middleware('password.confirm:,900');

    Route::get('/admin/departments', function () {
        return view('admin.departments', [
            'departments' => Department::withCount('officers')->orderBy('name')->get(),
        ]);
    })->name('admin.departments');

    Route::post('/admin/departments', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
        ]);

        $code = Str::slug($data['name']);
        if (Department::where('code', $code)->exists()) {
            $code .= '-' . strtolower(Str::random(4));
        }

        $department = Department::create(['name' => $data['name'], 'code' => $code, 'is_active' => true]);
        AuditLog::record('Department Created', 'Admin created department "' . $department->name . '".', 'Department', $department->id);

        return redirect()->route('admin.departments')->with('success', 'Department added.');
    })->name('admin.departments.store');

    Route::post('/admin/departments/{department}/toggle', function (Department $department) {
        $department->update(['is_active' => ! $department->is_active]);
        AuditLog::record('Department Updated', 'Admin set department "' . $department->name . '" to ' . ($department->is_active ? 'active' : 'inactive') . '.', 'Department', $department->id);

        return redirect()->route('admin.departments')->with('success', 'Department updated.');
    })->name('admin.departments.toggle');

    Route::post('/admin/departments/{department}/officers', function (Request $request, Department $department) {
        if (! $department->is_active) {
            return redirect()->route('admin.departments')->with('error', 'Cannot assign an officer to an inactive department.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', 'unique:users,login_id'],
        ]);

        $officer = User::create([
            'name' => $data['name'],
            'login_id' => $data['login_id'],
            'email' => $data['login_id'] . '@staff.aitsa.test',
            'password' => Hash::make('password123'),
            'role' => 'department_officer',
            'department_id' => $department->id,
        ]);

        AuditLog::record('Department Officer Created', 'Admin created officer account for ' . $officer->name . ' (' . $officer->login_id . '), department: ' . $department->name . '.', 'User', $officer->id);

        return redirect()->route('admin.departments')->with('success', 'Officer account created for ' . $officer->name . '.');
    })->name('admin.departments.officers.store');

    Route::get('/admin/announcements', function () {
        return view('admin.announcements', [
            'announcements' => Announcement::with('poster')->latest()->get(),
        ]);
    })->name('admin.announcements');

    Route::post('/admin/announcements', function (Request $request, \App\Services\LinkPreviewImageFetcher $linkImages) {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'link_url' => ['nullable', 'url:http,https', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:15360'],
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('announcements', 'local');
            $attachmentName = $file->getClientOriginalName();
        }

        // Links the dashboard can't embed (e.g. Facebook posts) show their
        // preview picture instead, saved once now since those URLs expire.
        $linkImagePath = null;
        if (! empty($data['link_url']) && ((new Announcement(['link_url' => $data['link_url']]))->linkPreview()['type'] ?? null) === 'card') {
            $linkImagePath = $linkImages->fetch($data['link_url']);
        }

        $announcement = Announcement::create([
            'title' => $data['title'],
            'link_url' => $data['link_url'] ?? null,
            'link_image_path' => $linkImagePath,
            'posted_by' => Auth::id(),
            'is_active' => true,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);
        AuditLog::record('Announcement Posted', 'Admin posted announcement "' . $announcement->title . '".', 'Announcement', $announcement->id);

        return redirect()->route('admin.announcements')->with('success', 'Announcement posted.');
    })->name('admin.announcements.store');

    Route::post('/admin/announcements/{announcement}/delete', function (Announcement $announcement) {
        $title = $announcement->title;
        if ($announcement->attachment_path) {
            Storage::disk('local')->delete($announcement->attachment_path);
        }
        if ($announcement->link_image_path) {
            Storage::disk('local')->delete($announcement->link_image_path);
        }
        $announcement->delete();
        AuditLog::record('Announcement Deleted', 'Admin deleted announcement "' . $title . '".', 'Announcement', null);

        return redirect()->route('admin.announcements')->with('success', 'Announcement deleted.');
    })->name('admin.announcements.delete');
    }); // end role:admin

    // Student Records — list all students (Registrar). Grade entry is faculty-only
    // (per-section, tied to an actual class the student is enrolled in) — see
    // faculty.sections.grades below. A registrar-side grade editor previously existed
    // here as a second, independent writer to the same student_grades rows with no
    // reconciliation between the two; removed deliberately rather than relabeled, since
    // this deployment has no legacy/transferee grade data that would need a manual
    // backfill path.
    Route::middleware('role:registrar,admission')->group(function () {
    Route::get('/registrar/students', function () {
        // Rows are loaded on demand via registrar.students.search — the
        // registry table stays hidden until the registrar searches, so
        // there's no reason to pull every student (and every student's
        // documents) into the page on every visit.
        $context = ['rows' => [], 'searchUrl' => route('registrar.students.search')];

        return view('registrar.students', compact('context'));
    })->name('registrar.students')->middleware('password.confirm:,900');

    Route::get('/registrar/students/search', function (Request $request) {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $statusFilter = in_array($status, ['hold', 'cleared'], true) ? $status : null;
        $wantsAll = $status === 'all';

        if ($q === '' && $statusFilter === null && ! $wantsAll) {
            return response()->json(['rows' => []]);
        }

        $students = User::where('role', 'student')
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('login_id', 'like', "%{$q}%")
                    ->orWhere('major', 'like', "%{$q}%");
            }))
            // A status filter needs each student's computed admin status before it
            // can be applied (see below), so it can't be scoped with a LIMIT here
            // the way a plain name search can — it's capped after filtering instead.
            ->when($statusFilter === null, fn ($query) => $query->limit(50))
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $clearances = Clearance::whereIn('user_id', $studentIds)
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->get()
            ->keyBy('user_id');
        $documents = DocumentSubmission::whereIn('user_id', $studentIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($submissions) => $submissions->unique('document_type')->values());
        $failedStudentIds = \App\Models\StudentGrade::whereIn('user_id', $studentIds)
            ->where('status', 'Failed')
            ->distinct()
            ->pluck('user_id');

        // Year-level edits are Registrar-only and College-only; each option
        // list is capped at that program's length.
        $canEditYear = Auth::user()->role === 'registrar';
        $collegeYears = $canEditYear
            ? Program::whereIn('level', ['associate', 'bachelor'])->pluck('years', 'code')
            : collect();
        $yearLabels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

        $rows = $students->map(function ($s) use ($documents, $clearances, $failedStudentIds, $collegeYears, $yearLabels) {
            // A document still awaiting review is just as much "needs the
            // registrar's attention" as a rejected one — both put the
            // account on hold, not just the rejected case. The badge shown
            // to the registrar only ever reads "Pending" or "Cleared" — the
            // on-hold/plain-pending distinction is kept as `needsAttention`
            // for the `status=hold` filter below, without cluttering the
            // visible status with a third label.
            $clearance = $clearances->get($s->id);
            $hasPendingAccount = $clearance && $clearance->registrar_status !== 'Approved';
            $hasPendingDocument = $documents->get($s->id, collect())
                ->contains(fn ($document) => in_array($document->status, ['pending', 'rejected'], true));
            $needsAttention = $hasPendingAccount || $hasPendingDocument;
            $isCleared = ! $needsAttention && ($clearance->registrar_status ?? 'Pending') === 'Approved';

            return [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'loginId' => $s->login_id,
                'major' => $s->major,
                'yearLevel' => $s->year_level,
                'yearEdit' => $collegeYears->has($s->major) ? [
                    'url' => route('registrar.students.year-level', $s->id),
                    'options' => array_slice($yearLabels, 0, (int) ($collegeYears[$s->major] ?: 4)),
                ] : null,
                'isIrregular' => $failedStudentIds->contains($s->id) || $s->backSubjectCodes() !== [],
                // Subject crediting: Registrar only, transferees/returnees only.
                'creditsUrl' => Auth::user()->role === 'registrar'
                    && in_array($s->applicant_type, \App\Services\SubjectCreditService::ELIGIBLE_TYPES, true)
                    ? route('registrar.students.credits', $s->id) : null,
                'applicantType' => $s->applicant_type,
                'adminStatus' => $isCleared ? 'Cleared' : 'Pending',
                'needsAttention' => $needsAttention,
                'documents' => $documents->get($s->id, collect())->map(fn ($document) => [
                    'id' => $document->id,
                    'typeLabel' => $document->typeLabel(),
                    'originalName' => $document->original_name,
                    'status' => $document->status,
                    'remarks' => $document->remarks,
                    'documentUrl' => route('documents.show', $document),
                    'createdAtFormatted' => $document->created_at->format('M d, Y g:i A'),
                ])->values(),
            ];
        });

        if ($statusFilter === 'hold') {
            $rows = $rows->filter(fn ($row) => $row['needsAttention'])->values();
        } elseif ($statusFilter === 'cleared') {
            $rows = $rows->filter(fn ($row) => $row['adminStatus'] === 'Cleared')->values();
        }

        return response()->json(['rows' => $rows->take(100)->values()]);
    })->name('registrar.students.search')->middleware('password.confirm:,900');

    Route::get('/registrar/reports', function () {
        $schoolYear = Setting::get('school_year', '2026-2027');
        $semester = (int) Setting::get('semester', '1');
        $clearances        = Clearance::has('user')->with('user')
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->get();

        $totalStudents = User::where('role', 'student')->count();
        $programBreakdown = User::where('role', 'student')
            ->whereNotNull('major')->where('major', '!=', '')
            ->selectRaw('major, count(*) as count')
            ->groupBy('major')->orderByDesc('count')->get();

        // Withdrawn admissions stay listed (schools report them) but aren't
        // counted as active records or as work still waiting on the Registrar.
        $active = $clearances->where('admission_status', '!=', 'Withdrawn');

        $context = [
            'summary' => [
                'total' => $active->count(),
                'registrarSigned' => $active->where('registrar_status', 'Approved')->count(),
                'registrarPending' => $active->count() - $active->where('registrar_status', 'Approved')->count(),
                'withdrawn' => $clearances->count() - $active->count(),
                'fullyCleared' => $active->filter(fn ($c) =>
                    $c->chair_status === 'Approved' && $c->cashier_status === 'Approved' && $c->registrar_status === 'Approved'
                )->count(),
            ],
            'pipeline' => [
                'pendingApplicants' => User::where('role', 'applicant')->count(),
                'verifiedApplicants' => User::where('role', 'verified_applicant')->count(),
                'totalStudents' => $totalStudents,
            ],
            'agreements' => ['signed' => EnrollmentAgreement::count()],
            'programBreakdown' => $programBreakdown->map(fn ($prog) => [
                'major' => $prog->major,
                'count' => $prog->count,
                'pct' => $totalStudents > 0 ? round(($prog->count / $totalStudents) * 100) : 0,
            ])->values(),
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'rows' => $clearances->values()->map(fn ($c, $i) => [
                'id' => $c->id,
                'index' => $i + 1,
                'studentName' => $c->user->name ?? 'Unknown',
                'studentEmail' => $c->user->email ?? '',
                'studentNo' => $c->user->login_id ?? 'N/A',
                'program' => $c->user->major ?? '—',
                'chairStatus' => $c->chair_status,
                'cashierStatus' => $c->cashier_status,
                'registrarStatus' => $c->registrar_status,
                'registrarSigned' => $c->registrar_status === 'Approved',
                'isWithdrawn' => $c->admission_status === 'Withdrawn',
                'isCleared' => $c->chair_status === 'Approved' && $c->cashier_status === 'Approved' && $c->registrar_status === 'Approved',
            ])->values(),
        ];

        return view('registrar.reports', compact('context', 'schoolYear', 'semester'));
    })->name('registrar.reports')->middleware('password.confirm:,900');

    /**
     * Mark or unmark whether an applicant has actually PAID the ₱500
     * slot-reservation fee. Used when it's collected in person at the
     * counter rather than through the online PayMongo checkout.
     */
        /**
         * Directly activate an applicant's student account without touching
         * reservation status — for applicants who never opted to pay a
         * reservation fee online or at the counter. Restores the "registrar
         * reviews, one click creates the account" path the old verify/decline
         * flow used to cover, without reintroducing manual verify/reject.
         */
        Route::post('/registrar/activate-applicant/{id}', function ($id, \App\Services\AdmissionService $admissions) {
            $applicant = User::where('id', $id)->where('role', 'applicant')->firstOrFail();

            $credentials = $admissions->activateStudentAccount($applicant);

            AuditLog::record(
                'Applicant Activated by Registrar',
                'Registrar activated the student account for ' . $applicant->name . ' (' . $applicant->email . ') without a reservation payment.',
                'User',
                $applicant->id
            );

            // Show the credentials here too — if the confirmation email failed
            // to send (e.g. mail isn't configured on this environment), this
            // is the only place the Registrar can see them.
            $message = $applicant->name . '\'s student account has been created.';
            if ($credentials) {
                $message .= ' Login ID: ' . $credentials['login_id'] . ', Password: ' . $credentials['password'];
            }

            return redirect()->back()->with('success', $message);
        })->name('registrar.activate-applicant');

        Route::post('/registrar/toggle-reservation/{id}', function ($id, \App\Services\AdmissionService $admissions) {
            $applicant = User::where('id', $id)->where('role', 'applicant')->firstOrFail();

            // Simple toggle — flips true/false each time it's clicked.
            $applicant->update(['is_reserved' => ! $applicant->is_reserved]);

            // Marking as paid (a counter payment) activates the student account
            // immediately, same as the online PayMongo reservation flow.
            $credentials = null;
            if ($applicant->is_reserved) {
                $credentials = $admissions->activateStudentAccount($applicant);
            }

            AuditLog::record(
                $applicant->is_reserved ? 'Slot Reserved' : 'Slot Reservation Reverted',
                ($applicant->is_reserved ? 'Marked' : 'Unmarked') . ' slot reservation for ' . $applicant->name . ' (' . $applicant->email . ').',
                'User',
                $applicant->id
            );

            $message = $applicant->name . ' is now marked as ' . ($applicant->is_reserved ? 'Reserved' : 'Not Reserved') . '.';
            if ($credentials) {
                $message .= ' Login ID: ' . $credentials['login_id'] . ', Password: ' . $credentials['password'];
            }

            return redirect()->back()->with('success', $message);
        })->name('registrar.toggle-reservation');

        /**
         * Soft-delete a stale/abandoned applicant so they stop cluttering the
         * pending queue. This is NOT the old verify/decline gate — it doesn't
         * judge the application, it just archives records nobody ever acted
         * on (e.g. never paid, never followed up). Reversible via the users
         * table's deleted_at column if ever needed.
         */
        Route::post('/registrar/archive-applicant/{id}', function ($id) {
            $applicant = User::where('id', $id)->where('role', 'applicant')->firstOrFail();

            AuditLog::record(
                'Applicant Archived',
                'Registrar archived the stale application for ' . $applicant->name . ' (' . $applicant->email . ').',
                'User',
                $applicant->id
            );

            $applicant->delete();

            return redirect()->back()->with('success', $applicant->name . '\'s application has been archived.');
        })->name('registrar.archive-applicant');

    }); // end role:registrar,admission

    // --- REGISTRAR-ONLY: term rollover + provisional-extension grants
    // (higher blast-radius / more discretionary than the shared
    // registrar,admission workspace above, so they get their own,
    // tighter role gate). ---
    Route::middleware('role:registrar')->group(function () {
        Route::post('/registrar/start-new-term', function (Request $request) {
            $data = $request->validate([
                'school_year' => ['required', 'string', 'max:20'],
                'semester' => ['required', 'integer', Rule::in([1, 2])],
            ]);

            $currentSchoolYear = Setting::get('school_year', '2026-2027');
            $currentSemester = (int) Setting::get('semester', '1');

            if ($data['school_year'] === $currentSchoolYear && (int) $data['semester'] === $currentSemester) {
                return redirect()->back()->withErrors(['semester' => 'That is already the current term.']);
            }

            $created = 0;
            $promoted = 0;
            $heldBack = 0;
            // A new school year (not just the next semester within the same
            // year) is when a continuing student actually moves up a year —
            // e.g. Sem 2 of 2026-2027 -> Sem 1 of 2027-2028.
            // Only a move *forward* counts: going back (e.g. undoing a
            // mistaken rollover) must not promote everyone a second time.
            $isNewSchoolYear = (int) substr($data['school_year'], 0, 4) > (int) substr($currentSchoolYear, 0, 4);
            $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];

            // Only students officially Enrolled in every semester that actually
            // ran in the school year being closed move up a year level.
            $promotableIds = collect();
            if ($isNewSchoolYear) {
                $semestersThatRan = Enrollment::where('school_year', $currentSchoolYear)->distinct()->pluck('semester');
                if ($semestersThatRan->isNotEmpty()) {
                    $promotableIds = Enrollment::where('school_year', $currentSchoolYear)
                        ->where('status', 'enrolled')
                        ->whereIn('semester', $semestersThatRan)
                        ->groupBy('user_id')
                        ->havingRaw('COUNT(DISTINCT semester) = ?', [$semestersThatRan->count()])
                        ->pluck('user_id');
                }
            }

            DB::transaction(function () use ($data, $isNewSchoolYear, $yearLabels, $promotableIds, &$created, &$promoted, &$heldBack) {
                $collegePrograms = Program::whereIn('level', ['associate', 'bachelor'])->get()->keyBy('code');

                User::where('role', 'student')
                    ->whereIn('major', $collegePrograms->keys())
                    ->chunkById(100, function ($students) use ($data, $isNewSchoolYear, $yearLabels, $collegePrograms, $promotableIds, &$created, &$promoted, &$heldBack) {
                        foreach ($students as $student) {
                            Clearance::initializeFor($student->id, $data['school_year'], (int) $data['semester'], [
                                'admission_status' => 'Approved',
                                'chair_status' => 'Pending',
                                'cashier_status' => 'Pending',
                                'registrar_status' => 'Pending',
                            ]);
                            $created++;

                            if ($isNewSchoolYear) {
                                $currentYearNum = $student->yearNumber();
                                $maxYear = $collegePrograms[$student->major]->years ?? 4;
                                if (! $promotableIds->contains($student->id)) {
                                    $heldBack++;
                                } elseif ($currentYearNum < $maxYear) {
                                    $student->update(['year_level' => $yearLabels[$currentYearNum + 1]]);
                                    $promoted++;
                                }
                            }
                        }
                    });

                Setting::put('school_year', $data['school_year']);
                Setting::put('semester', (string) $data['semester']);
            });

            AuditLog::record(
                'New Term Started',
                Auth::user()->name . ' started ' . $data['school_year'] . ' Semester ' . $data['semester'] .
                    ' — created ' . $created . ' College clearance record(s)' .
                    ($promoted > 0 ? ', advanced ' . $promoted . ' student(s) to their next year level' : '') .
                    ($heldBack > 0 ? ', ' . $heldBack . ' student(s) kept at their year level (not enrolled in every semester)' : '') . '.',
                'Clearance',
                null
            );

            $message = 'Started ' . $data['school_year'] . ' Semester ' . $data['semester'] . ' for ' . $created . ' College student(s).';
            if ($promoted > 0) {
                $message .= ' ' . $promoted . ' student(s) advanced to their next year level.';
            }
            if ($heldBack > 0) {
                $message .= ' ' . $heldBack . ' student(s) kept at their year level (not enrolled in every semester).';
            }

            return redirect()->route('registrar.slots')->with('success', $message);
        })->name('registrar.start-new-term');

        Route::post('/registrar/clearances/{clearance}/grant-provisional', function (Request $request, Clearance $clearance) {
            $data = $request->validate([
                'reason' => ['required', 'string', 'max:1000'],
                'days' => ['required', 'integer', 'min:1', 'max:365'],
            ]);

            $clearance->update([
                'is_provisional' => true,
                'provisional_reason' => $data['reason'],
                'provisional_granted_by' => Auth::id(),
                'provisional_granted_at' => now(),
                'provisional_due_at' => now()->addDays((int) $data['days']),
            ]);

            AuditLog::record(
                'Provisional Extension Granted',
                Auth::user()->name . ' granted a ' . $data['days'] . '-day provisional clearance extension to ' .
                    ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . '): ' . $data['reason'],
                'Clearance',
                $clearance->id
            );

            return redirect()->route('registrar.dashboard')->with('success', 'Provisional extension granted.');
        })->name('registrar.grant-provisional');

        /*
         * Admission no-shows: reserved students who never continued. The
         * Registrar reviews the flagged list and marks them; the reservation
         * fee is non-refundable so nothing touches the ledger.
         */
        Route::post('/registrar/no-shows/withdraw', function (Request $request, \App\Services\AdmissionWithdrawalService $withdrawals) {
            $data = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer'],
                'reason' => ['required', Rule::in(array_keys(\App\Services\AdmissionWithdrawalService::REASONS))],
                'note' => ['nullable', 'string', 'max:500'],
            ]);

            $done = 0;
            $skipped = [];
            $students = User::whereIn('id', $data['ids'])->get();
            $missing = count(array_unique($data['ids'])) - $students->count();
            foreach ($students as $student) {
                if ($withdrawals->withdraw($student, $data['reason'], $data['note'] ?? null, Auth::user())) {
                    $done++;
                } else {
                    $skipped[] = $student->name;
                }
            }

            $notes = '';
            if ($skipped) {
                $notes .= ' Skipped (no longer a possible no-show): ' . implode(', ', $skipped) . '.';
            }
            if ($missing > 0) {
                $notes .= ' ' . $missing . ' record' . ($missing === 1 ? '' : 's') . ' could not be found.';
            }

            if ($done === 0) {
                return redirect()->route('registrar.dashboard')->with('error', 'No one was withdrawn.' . $notes);
            }

            $message = $done . ' student' . ($done === 1 ? '' : 's') . ' marked withdrawn; their slots are free again.' . $notes;

            return redirect()->route('registrar.dashboard')->with('success', $message);
        })->name('registrar.no-shows.withdraw');

        Route::post('/registrar/no-shows/threshold', function (Request $request) {
            $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);
            Setting::put('no_show_after_days', (string) $data['days']);

            return redirect()->route('registrar.dashboard')->with('success',
                'Students are now flagged as possible no-shows after ' . $data['days'] . ' ' . Str::plural('day', (int) $data['days']) . '.');
        })->name('registrar.no-shows.threshold');

        Route::post('/registrar/withdrawn/{user}/reinstate', function (User $user, \App\Services\AdmissionWithdrawalService $withdrawals) {
            try {
                $withdrawals->reinstate($user, Auth::user());
            } catch (\DomainException $e) {
                return redirect()->route('registrar.dashboard')->with('error', $e->getMessage());
            }

            return redirect()->route('registrar.dashboard')->with('success', $user->name . ' has been reinstated.');
        })->name('registrar.withdrawn.reinstate');
        /*
         * Manual year-level correction from Student Records (paper-era
         * students, wrong year at application, credited units). Guarded:
         * College programs only, capped at the program's length, a reason is
         * required and audited, and never while the student is mid-enrollment
         * this term (their sections were picked for their current year).
         */
        Route::post('/registrar/students/{user}/year-level', function (Request $request, User $user, \App\Services\EnrollmentService $enrollments) {
            abort_unless($user->role === 'student', 404);

            $data = $request->validate([
                'year_level' => ['required', Rule::in(['1st Year', '2nd Year', '3rd Year', '4th Year'])],
                'reason' => ['required', 'string', 'max:255'],
            ]);

            $program = Program::where('code', $user->major)->whereIn('level', ['associate', 'bachelor'])->first();
            if (! $program) {
                return response()->json(['message' => 'Year level only applies to College programs.'], 422);
            }

            $yearNumber = (int) $data['year_level'][0];
            if ($yearNumber > ($program->years ?? 4)) {
                return response()->json(['message' => $program->code . ' only runs for ' . $program->years . ' years.'], 422);
            }

            $active = $enrollments->activeEnrollment($user);
            if ($active && in_array($active->status, ['pending', 'enrolled'], true)) {
                return response()->json(['message' => 'This student has a ' . $active->status .
                    ' enrollment this term. Change the year level after this term, or reject their enrollment first.'], 422);
            }

            if ($user->year_level === $data['year_level']) {
                return response()->json(['message' => $user->name . ' is already ' . $data['year_level'] . '.'], 422);
            }

            $previous = $user->year_level ?? '—';
            $user->update(['year_level' => $data['year_level']]);

            AuditLog::record(
                'Year Level Changed',
                $user->name . ' (' . ($user->login_id ?? $user->email) . '): ' . $previous . ' → ' . $data['year_level'] .
                    ' by ' . Auth::user()->name . '. Reason: ' . $data['reason'],
                'User',
                $user->id
            );

            return response()->json([
                'yearLevel' => $user->year_level,
                'message' => $user->name . ' is now ' . $user->year_level . '.',
            ]);
        })->name('registrar.students.year-level')->middleware('password.confirm:,900');

        // Subject crediting (transferees/returnees only): the Registrar lists the
        // student's curriculum and submits subjects already passed elsewhere;
        // nothing counts until the Chair approves (approver.credits.*).
        Route::get('/registrar/students/{user}/credits', function (User $user, \App\Services\SubjectCreditService $credits) {
            return response()->json($credits->overview($user));
        })->name('registrar.students.credits');

        Route::post('/registrar/students/{user}/credits', function (Request $request, User $user, \App\Services\SubjectCreditService $credits) {
            $data = $request->validate([
                'subjects' => ['required', 'array', 'min:1', 'max:80'],
                'subjects.*.code' => ['required', 'string', 'max:20'],
                'subjects.*.grade' => ['nullable', 'string', 'max:10'],
                'note' => ['nullable', 'string', 'max:255'],
            ]);
            $request_ = $credits->submit($user, Auth::user(), $data['subjects'], $data['note'] ?? null);

            return response()->json([
                'message' => count($data['subjects']) . ' subject(s) sent to the Chair for approval.',
                'overview' => $credits->overview($user),
                'requestId' => $request_->id,
            ]);
        })->name('registrar.students.credits.submit');
        // Most units an irregular student (or any change of matriculation) may
        // carry in one term. Regular block loads are set by the curriculum.
        Route::post('/registrar/max-units', function (Request $request) {
            $data = $request->validate(['max_units' => ['required', 'integer', 'min:6', 'max:40']]);
            $previous = Setting::get('max_units_per_term', '26');
            Setting::put('max_units_per_term', (string) $data['max_units']);

            AuditLog::record(
                'Max Units Changed',
                Auth::user()->name . ' changed the maximum units per term from ' . $previous . ' to ' . $data['max_units'] . '.',
                'Setting',
                null
            );

            return redirect()->route('registrar.slots')->with('success',
                'Students can now carry up to ' . $data['max_units'] . ' units per term.');
        })->name('registrar.max-units');
    }); // end role:registrar (start-new-term, grant-provisional, admission withdrawals, year level, max units)
});