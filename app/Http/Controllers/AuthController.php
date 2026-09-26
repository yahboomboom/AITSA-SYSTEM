<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Exceptions\PaymentGatewayException;
use App\Models\User;
use App\Models\ShsStrand;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\TransactionLedger;
use App\Models\AdmissionSlotLimit;
use App\Models\Setting;
use App\Services\FeeAssessmentService;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    // 1. Render the login landing page matrix
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->handleRoleRedirection(Auth::user());
        }
        return view('login');
    }

    // Forgot-password: accept the same login_id-or-email identifier the
    // login form itself accepts, resolve it to a real email, then hand off
    // to Laravel's built-in password broker for the token/notification.
    public function showForgotPasswordForm()
    {
        if (Auth::check()) {
            return $this->handleRoleRedirection(Auth::user());
        }
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['login_id' => ['required', 'string']]);

        $identifier = $request->input('login_id');
        $user = User::where('login_id', $identifier)->orWhere('email', $identifier)->first();

        // Deliberately identical response whether or not the account exists,
        // so this form can't be used to enumerate valid student IDs/emails.
        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', 'If an account matches, a password reset link has been sent to the associated email address.');
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been reset. You may now log in.');
        }

        return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }

    // 2. Process secure credential authentication matching
    public function login(Request $request)
    {
        $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login    = $request->input('login_id');
        $password = $request->input('password');

        try {
            // Match the input against either the login_id column or the email column,
            // so students can use whichever credential the admin assigned them.
            $user = User::where('login_id', $login)->orWhere('email', $login)->first();

            if ($user && Hash::check($password, $user->password)) {
                Auth::login($user);
                $request->session()->regenerate();
                return $this->handleRoleRedirection($user);
            }
        } catch (\Illuminate\Database\QueryException | \PDOException $e) {
            return back()->withErrors([
                'login_id' => 'Unable to connect to the authentication database. Please make sure your database server is running and try again.',
            ])->onlyInput('login_id');
        }

        // Deliberately identical whether the account exists or the password was
        // wrong, so this form can't be used to enumerate valid login IDs/emails.
        return back()->withErrors([
            'login_id' => 'The provided credentials do not match our institutional database profiles.',
        ])->onlyInput('login_id');
    }

    // 3. Render the Student Application Form UI Workspace
        public function showApplicationForm()
    {
        if (Auth::check()) {
            return $this->handleRoleRedirection(Auth::user());
        }

        $strands = ShsStrand::all();
        // Reservation fee is now editable in Cashier > Billing Setup, so pull the live value
        // instead of hardcoding ₱500 in the form's checkbox label.
        $reservationFee = (int) \App\Models\Setting::get('reservation_fee', '500');

        // Slot limits per curriculum (total, divided into sections) — set by the
        // Registrar under Admission Slots. Shown to applicants so they know how
        // many slots are left before they pick a program.
        $schoolYear = Setting::get('school_year', '2026-2027');
        $slots = collect(config('curricula'))->mapWithKeys(function ($prog) use ($schoolYear) {
            $limit = AdmissionSlotLimit::forProgram($prog['id'], $prog['name'], $schoolYear);
            return [$prog['id'] => [
                'totalSlots'      => $limit->total_slots,
                'sections'        => $limit->sections,
                'perSection'      => $limit->slotsPerSection(),
                'slotsLeft'       => $limit->slotsLeft(),
                'isFull'          => $limit->isFull(),
            ]];
        });

        return view('auth.apply', compact('strands', 'reservationFee', 'slots', 'schoolYear'));
    }

    // 4. Store incoming application as pending — admin creates the account and emails credentials
        public function processApplication(Request $request, \App\Services\PaymentService $payments)
    {
        $request->validate([
            'last_name'      => ['required', 'string', 'max:100'],
            'first_name'     => ['required', 'string', 'max:100'],
            'middle_name'    => ['nullable', 'string', 'max:100'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'date_of_birth'  => ['required', 'date'],
            'sex'            => ['required', 'string', Rule::in(['Male', 'Female'])],
            'address'        => ['required', 'string', 'max:500'],
            'last_school'    => ['required', 'string', 'max:255'],
            'year_graduated' => ['required', 'string', 'max:10', 'regex:/^[0-9]+$/'],
            'applicant_type' => ['required', 'string', Rule::in(['NEW', 'TRANSFEREE', 'RETURNEE'])],
            'year_level'     => ['required_if:applicant_type,TRANSFEREE,RETURNEE', 'nullable', 'string', Rule::in(['1st Year', '2nd Year', '3rd Year', '4th Year'])],
            'program_key'    => ['required', 'string', Rule::in(collect(config('curricula'))->pluck('id')->all())],
            'program_name'   => ['required', 'string'],
            'program_level'  => ['required', 'string'],
            'remarks'        => ['nullable', 'string', 'max:1000'],
        ], [
            'email.unique' => 'An application with this email address already exists. If you need help, contact our admissions office.',
            'program_key.in' => 'That program could not be found. Please pick a program from the list.',
            'contact_number.regex' => 'Contact number may only contain digits.',
        ]);

        $name = trim($request->input('last_name')) . ', ' . trim($request->input('first_name'))
            . ($request->filled('middle_name') ? ' ' . trim($request->input('middle_name')) : '');

        // Enforce the curriculum slot limit set by the Registrar (Admission Slots).
        $schoolYear = Setting::get('school_year', '2026-2027');
        $slotLimit  = AdmissionSlotLimit::forProgram($request->input('program_key'), $request->input('program_name'), $schoolYear);
        if ($slotLimit->isFull()) {
            return redirect()->route('apply')->withInput()->with('error',
                'Sorry, ' . $request->input('program_name') . ' has reached its slot limit for ' . $schoolYear . '. ' .
                'Please choose another program or contact our admissions office.'
            );
        }

        // `major` must be the Program's `code` (e.g. "BSOA"), not the display
        // name from the form — User::program() resolves a student's program
        // by looking up that code, and the enrollment block/section lookup
        // (and therefore the whole Enrollment page) silently comes up empty
        // for anyone whose major doesn't match a real program code.
        $programCode = collect(config('curricula'))->firstWhere('id', $request->input('program_key'))['program_code']
            ?? $request->input('program_name');

        $applicant = User::create([
            'name'              => $name,
            'email'             => $request->input('email'),
            'login_id'          => 'APPL-' . strtoupper(Str::random(8)),
            'major'             => $programCode,
            'program_key'       => $request->input('program_key'),
            'role'              => 'applicant',
            'password'          => Hash::make(Str::random(32)),
            'contact_number'    => $request->input('contact_number'),
            'date_of_birth'     => $request->input('date_of_birth'),
            'sex'               => $request->input('sex'),
            'address'           => $request->input('address'),
            'last_school'       => $request->input('last_school'),
            'year_graduated'    => $request->input('year_graduated'),
            'applicant_type'    => $request->input('applicant_type'),
            'year_level'        => $request->input('year_level'),
            'program_level'     => $request->input('program_level'),
            'applicant_remarks' => $request->input('remarks'),
            'wants_reservation' => $request->boolean('wants_reservation'), // checkbox intent from the application form
        ]);

        // If the applicant opted to reserve their slot, send them straight to
        // the reservation-fee checkout right after they submit — they aren't
        // logged in yet, so this uses signed, applicant-scoped URLs instead of /ledger.
        if ($applicant->wants_reservation) {
            try {
                $checkoutUrl = $payments->startReservationCheckout(
                    $applicant,
                    URL::signedRoute('apply.reservation.return', ['user' => $applicant->id]),
                    URL::signedRoute('apply.reservation.cancel', ['user' => $applicant->id])
                );
            } catch (PaymentGatewayException $e) {
                // Gateway down / not configured — the application is still saved either way.
                $fee = (int) \App\Models\Setting::get('reservation_fee', '500');
                return redirect()->route('apply')->with('success',
                    'Your application for ' . $request->input('program_name') . ' has been received! ' .
                    'We could not open the online payment page just now (' . $e->getMessage() . '), ' .
                    'so please settle the ₱' . number_format($fee) . ' reservation fee at the cashier window. ' .
                    'Our admin team will send your login credentials to ' . $request->input('email') . ' within 1–3 business days.'
                );
            }

            // Redirect the applicant off-site to PayMongo's hosted checkout page.
            // Shown through a brief branded "pop out" screen first so the jump
            // from our form to PayMongo's page doesn't feel abrupt.
            return view('auth.redirecting-to-payment', [
                'checkoutUrl' => $checkoutUrl,
                'programName' => $request->input('program_name'),
                'reservationFee' => (int) \App\Models\Setting::get('reservation_fee', '500'),
            ]);
        }

        return redirect()->route('apply')
            ->with('success', 'Your application for ' . $request->input('program_name') . ' has been received! Our admin team will review it and send your login credentials to ' . $request->input('email') . ' within 1–3 business days.');
    }

    /**
     * Helper to allocate documentation tracking items dynamically derived from institutional enrollment categories.
     */
    private function generateDocumentRequirements(User $user, string $academicLevel, ?string $completerType)
    {
        if ($academicLevel === 'COLLEGE') {
            $collegeRequirements = ['Form 137 (Original)', 'Form 138/Card (Original)', 'PSA Birth Certificate (Original)'];
            foreach ($collegeRequirements as $doc) {
                // Custom log connections if database models are linked
            }
            return;
        }

        $requirementsMatrix = [
            'PUBLIC' => [
                'Form 137 (Original)', 
                'Form 138/Card (Original)', 
                'PSA Birth Certificate (Original)', 
                'JHS Completer Certificate (Photocopy)', 
                '2x2 Picture (2pcs)'
            ],
            'PRIVATE' => [
                'Form 137 (Original)', 
                'Form 138/Card (Original)', 
                'PSA Birth Certificate (Original)', 
                'ESC Certificate (Original)', 
                '2x2 Picture (2pcs)', 
                'QVA Certificate'
            ],
            'ALS' => [
                'Revalida/Portfolio (Original)', 
                'Certificate of Ratings (Original)', 
                'Good Moral (Original)', 
                'PSA Birth Certificate (Original)', 
                '2x2 Picture (2pcs)'
            ],
            'TRANSFEREE' => [
                'Form 137 (Original)', 
                'Form 138/Card (Original)', 
                'PSA Birth Certificate (Original)', 
                '2x2 Picture (2pcs)', 
                'Affidavit of Financial'
            ],
        ];

        $targetDocuments = $requirementsMatrix[$completerType] ?? [];

        foreach ($targetDocuments as $documentName) {
            // Track document records matching database expectations
        }
    }

    // 5. Helper function to manage structural role routing with fail-safe verification
    protected function handleRoleRedirection($user)
    {
        $role = $user->role ?? 'student';
        $targetRoute = 'dashboard';

        switch ($role) {
            case 'student':
                $targetRoute = 'dashboard';
                break;
            case 'admission':
            case 'registrar':
                $targetRoute = 'registrar.dashboard';
                break;
            case 'chair':
                $targetRoute = 'approver.dashboard';
                break;
            case 'faculty':
                $targetRoute = 'faculty.schedule';
                break;
            case 'admin':
                $targetRoute = 'admin.dashboard';
                break;
            case 'cashier':
                $targetRoute = 'cashier.dashboard';
                break;
            case 'department_officer':
                $targetRoute = 'department.dashboard';
                break;
            default:
                $targetRoute = 'dashboard';
                break;
        }

        if (Route::has($targetRoute)) {
            return redirect()->route($targetRoute);
        }

        return redirect('/');
    }

    // 6. Terminate secure tracking state sessions
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // 6.5 Render the Cashier Administrative Dashboard with SQLite compatibility
    public function showCashierDashboard(FeeAssessmentService $fees)
    {
        // Fixed: Swapped MySQL FIELD() function with a cross-platform conditional CASE block
        $clearances = Clearance::has('user')->with('user.discountType')
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->orderByRaw("CASE WHEN cashier_status = 'Pending' THEN 0 ELSE 1 END ASC")
            ->get();

        $totalOutstandingDocs = Clearance::where('cashier_status', 'Pending')
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->count();

        $latestSettledByUser = TransactionLedger::where('status', 'Settled')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->first());

        $rows = $clearances->map(function ($clearance) use ($fees, $latestSettledByUser) {
            $breakdown = $clearance->user ? $fees->breakdownFor($clearance->user) : ['balance' => 0.0, 'down_payment_met' => false];

            $latestSettled = $latestSettledByUser->get($clearance->user_id);

            return [
                'id' => $clearance->id,
                'userId' => $clearance->user_id,
                'studentName' => $clearance->user->name ?? 'Unknown Student',
                'studentEmail' => $clearance->user->email ?? 'N/A',
                'balance' => (float) $breakdown['balance'],
                'isApproved' => $clearance->cashier_status === 'Approved',
                'isDownPaymentMet' => (bool) $breakdown['down_payment_met'],
                'isDownPaymentWaived' => (bool) $clearance->down_payment_waived,
                'isHeld' => $clearance->cashier_status === 'Hold',
                'referenceNo' => $latestSettled->reference_no ?? null,
            ];
        })->values();

        $context = [
            'stats' => [
                'totalOutstanding' => (float) $rows->where('isApproved', false)->sum('balance'),
                'settledBase' => (float) TransactionLedger::where('status', 'Settled')->sum('amount'),
                'settledCount' => TransactionLedger::where('status', 'Settled')->count(),
                'pendingActions' => $totalOutstandingDocs,
            ],
            'rows' => $rows,
        ];

        return view('cashier.dashboard', compact('context'));
    }

    /**
     * Display cashier transactions ledger.
     */
    public function showCashierTransactions()
    {
        $transactions = TransactionLedger::with(['user', 'processor'])->orderBy('created_at', 'desc')->get();

        $context = [
            'rows' => $transactions->map(fn ($t) => [
                'id' => $t->id,
                'userId' => $t->user_id,
                'studentName' => $t->user->name ?? 'Unknown Student',
                'studentNo' => $t->user->login_id ?? 'N/A',
                'referenceNo' => $t->reference_no ?? 'N/A',
                'amount' => (float) ($t->amount ?? 3500),
                'status' => $t->status ?? 'Success',
                'processorName' => $t->processor->name ?? 'System Override',
                'timestamp' => $t->created_at ? $t->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
                'createdAt' => $t->created_at ? $t->created_at->toIso8601String() : now()->toIso8601String(),
            ])->values(),
        ];

        return view('cashier.transactions', compact('context'));
    }

    /**
     * Display cashier student accounts / clearances.
     */
    public function showCashierAccounts()
    {
        $accounts = Clearance::has('user')->with('user')
            ->where('school_year', Setting::get('school_year', '2026-2027'))
            ->where('semester', (int) Setting::get('semester', '1'))
            ->where('cashier_status', '!=', 'Approved')
            ->orderBy('created_at', 'desc')
            ->get();

        // All settled payments per student, most recent first — used to build
        // the per-student "Review Transaction History" dropdown so cashiers can
        // see what was paid (reservation, tuition, etc.) without leaving this page.
        $settledByUser = TransactionLedger::where('status', 'Settled')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        $context = [
            'rows' => $accounts->map(function ($a) use ($settledByUser) {
                $payments = $settledByUser->get($a->user_id, collect());
                $latestSettled = $payments->first();

                return [
                    'id' => $a->id,
                    'profileId' => '#' . sprintf('%04d', $a->id),
                    'studentName' => $a->user->name ?? 'Unknown Student',
                    'studentEmail' => $a->user->email ?? 'N/A',
                    'studentNo' => $a->user->login_id ?? 'N/A',
                    'referenceNo' => $latestSettled->reference_no ?? null,
                    'lastPaymentAmount' => $latestSettled ? (float) $latestSettled->amount : null,
                    'lastPaymentDate' => $latestSettled && $latestSettled->created_at
                        ? $latestSettled->created_at->format('Y-m-d')
                        : null,
                    'cashierStatus' => $a->cashier_status,
                    'payments' => $payments->map(fn ($p) => [
                        'feeType' => $p->fee_type,
                        'amount' => (float) $p->amount,
                        'date' => $p->created_at ? $p->created_at->format('Y-m-d') : null,
                        'referenceNo' => $p->reference_no,
                    ])->values(),
                ];
            })->values(),
            'reviewUrl' => route('cashier.dashboard'),
            'historyUrl' => route('cashier.transactions'),
        ];

        return view('cashier.accounts', compact('context'));
    }

    /**
     * Display the Certificate of Registration (COR) for the authenticated user.
     */
    public function showCor()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $schoolYear = Setting::get('school_year', '2026-2027');
        $semester = (int) Setting::get('semester', '1');

        $palette = ['bg-blue-600', 'bg-emerald-600', 'bg-violet-600', 'bg-orange-500', 'bg-cyan-600', 'bg-teal-600', 'bg-rose-500', 'bg-amber-500', 'bg-brandGreen'];

        $enrollment = Enrollment::with('sections.subject')
            ->where('user_id', $user->id)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereIn('status', ['pending', 'enrolled'])
            ->latest('id')
            ->first();

        $subjects = $enrollment
            ? $enrollment->sections->values()->map(fn ($section, $i) => [
                'code' => $section->subject->code,
                'desc' => $section->subject->title,
                'units' => $section->subject->units,
                'days' => implode('/', $section->days),
                'time' => $section->start_time . '–' . $section->end_time,
                'room' => $section->room,
                'type' => $section->subject->mode,
                'color' => $palette[$i % count($palette)],
            ])->all()
            : [];

        $schoolYear = $enrollment->school_year ?? Setting::get('school_year', '2026-2027');
        $semester = $enrollment->semester ?? (int) Setting::get('semester', '1');

        return view('schedule', compact('user', 'subjects', 'schoolYear', 'semester'));
    }

    // 7. Process administrative clearance override and write long-term transaction ledger records
    public function approveClearance(Request $request, FeeAssessmentService $fees)
    {
        $request->validate([
            'user_id'      => ['required', 'integer'],
            'reference_no' => ['required', 'string'],
            'amount'       => ['required', 'string']
            ]);
            
            $cleanAmount = (float) str_replace(['₱', ',', ' '], '', $request->input('amount'));
            $user = User::findOrFail($request->input('user_id'));
            $clearance = Clearance::currentFor($user);
            abort_if(! $clearance, 404, 'No current-term clearance found for this student.');
            DB::beginTransaction();

            try {
                // First, record this payment as a Settled transaction.
                TransactionLedger::create([
                    'user_id'      => $request->input('user_id'),
                    'reference_no' => $request->input('reference_no'),
                    'amount'       => $cleanAmount,
                    'status'       => 'Settled',
                    'processed_by' => Auth::id() ?? 1,
                    'remarks'      => 'Recorded via Cashier — Administrative Console Queue.'
                ]);

                // Now recompute the student's full assessment, including this
                // new payment, to check whether they are actually fully paid.
                $breakdown = $fees->breakdownFor($user);

                if ($breakdown['fully_paid']) {
                    // Balance is fully settled — safe to approve clearance.
                    $clearance->update([
                        'cashier_status' => 'Approved',
                        'cashier_signed_by' => Auth::id(),
                        'cashier_signed_at' => now(),
                        'remarks' => null,
                    ]);
                    $message = 'Payment recorded — balance fully settled. Clearance approved.';
                } else {
                    // There's still a remaining balance — should NOT be marked
                    // "Approved" yet.
                    $clearance->update([
                        'cashier_status' => 'Pending',
                        'remarks' => 'Partial payment received. Remaining balance: ₱' . number_format($breakdown['balance'], 2) . '.',
                    ]);
                    $message = 'Payment recorded — but ₱' . number_format($breakdown['balance'], 2) . ' balance remains. Clearance still Pending.';
                }

                DB::commit();

                \App\Models\AuditLog::record(
                    'Payment Recorded',
                    'Cashier recorded payment for student ID ' . $request->input('user_id') . '. Ref #' . $request->input('reference_no') . ', Amount: ₱' . number_format($cleanAmount, 2) . '. Balance after: ₱' . number_format($breakdown['balance'], 2) . '.',
                    'Clearance',
                    $clearance->id
                );

                // Only notify the student once the cashier clearance is actually Approved —
                // a partial payment (still Pending) doesn't warrant a "cleared" email.
                if ($breakdown['fully_paid']) {
                    \App\Support\SafeNotify::send($user, new \App\Notifications\ClearanceStatusUpdatedNotification('Cashier', 'Approved'));
                }

                return redirect()->back()->with('success', $message);

            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Execution Error: ' . $e->getMessage());
            }
        }
}