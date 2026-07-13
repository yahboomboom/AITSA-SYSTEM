<?php

use App\Http\Controllers\AuthController;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\DocumentSubmission;
use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Guest Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Open Student Application Sequence Endpoints
Route::get('/apply', [AuthController::class, 'showApplicationForm'])->name('apply');
Route::post('/apply', [AuthController::class, 'processApplication'])->name('apply.store');


/*
|--------------------------------------------------------------------------
| Protected Authenticated Systems
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    
    // 1. Student Dashboard Module
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Session validation failure.');
        }

        $clearance = Clearance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'admission_status'   => 'Pending',
                'chair_status'       => 'Pending',
                'cashier_status'     => 'Pending',
                'registrar_status'   => 'Pending',
                'library_status'     => 'Approved',
                'clinic_status'      => 'Approved',
            ]
        );

        return view('dashboard', compact('clearance'));
    })->name('dashboard');

    // 2. Student e-Clearance Routing Module
    Route::get('/clearance', function () {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $clearance = Clearance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'admission_status'   => 'Pending',
                'chair_status'       => 'Pending',
                'cashier_status'     => 'Pending',
                'registrar_status'   => 'Pending',
                'library_status'     => 'Approved',
                'clinic_status'      => 'Approved',
            ]
        );

        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
        $submission = $submissions->first();
        return view('clearance', compact('clearance', 'submission', 'submissions'));
    })->name('clearance');

    Route::post('/clearance/submit-requirement', function (Request $request) {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_type' => ['required', 'in:form137,form138,birth_cert,good_moral,other'],
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

    // 3. Enrollment Hub Module
    Route::get('/enrollment', function () {
        $user = Auth::user();
        $clearance = Clearance::where('user_id', $user?->id)->first();

        $grades      = StudentGrade::where('user_id', $user->id)->get();
        $passedCodes = $grades->where('status', 'Passed')->pluck('subject_code')->values()->toArray();
        $failedCodes = $grades->where('status', 'Failed')->pluck('subject_code')->values()->toArray();
        $isIrregular = count($failedCodes) > 0;

        $yearMap = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4];
        $yearNum = $yearMap[$user->year_level] ?? 1;

        return view('enrollment', compact('clearance', 'passedCodes', 'failedCodes', 'isIrregular', 'yearNum'));
    })->name('enrollment');

    // 5. Ledger Workspace Module (view renamed to `payment`)
    Route::get('/ledger', function () { 
        $user = Auth::user();
        $clearance = Clearance::where('user_id', $user?->id)->first();
        return view('payment', compact('clearance')); 
    })->name('ledger');

    Route::post('/ledger/mock-pay', function (Request $request) {
        $user = Auth::user();
        $clearance = Clearance::where('user_id', $user?->id)->first();
        
        if ($clearance && $request->input('payment_status') === 'success') {
            $clearance->update(['cashier_status' => 'Approved']);
            return redirect()->route('ledger')->with('success', 'Payment authorized successfully via Sandbox Gateway!');
        }
        
        return redirect()->route('ledger')->with('error', 'Transaction declined or canceled.');
    })->name('ledger.mockPay');

    // 5. Certificate of Registration (COR) View
    Route::get('/cor', [AuthController::class, 'showCor'])->name('cor');

    /*
    |--------------------------------------------------------------------------
    | Staff & Faculty Administration Core Layer
    |--------------------------------------------------------------------------
    | Note: These groups can be further isolated by custom middleware roles if needed
    */
    
    // --- CONSOLIDATED REGISTRAR & ADMISSION WORKSPACE ---
    Route::middleware('role:registrar,admission')->group(function () {
    Route::get('/registrar/dashboard', function () {
        $user       = Auth::user();
        $clearances = Clearance::has('user')->with('user')->get();
        $applicants = User::where('role', 'applicant')->orderByDesc('created_at')->get();

        return view('registrar.dashboard', compact('clearances', 'applicants'));
    })->name('registrar.dashboard');

    Route::get('/admission/dashboard', function () {
        return redirect()->route('registrar.dashboard');
    })->name('admission.dashboard');

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
            $clearance->update(['registrar_status' => 'Approved']);
            AuditLog::record('Clearance Signed', 'Registrar signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
        }
        return redirect()->route('registrar.dashboard')->with('success', 'Student credentials verified successfully.');
    })->name('registrar.sign');

    // Registrar verifies a new applicant → forwards to Admin for account creation
    Route::post('/registrar/verify-applicant/{id}', function ($id) {
        $applicant = User::where('id', $id)->where('role', 'applicant')->firstOrFail();
        $applicant->update(['role' => 'verified_applicant']);
        AuditLog::record('Applicant Verified', 'Registrar verified application for ' . $applicant->name . ' (' . $applicant->email . '), program: ' . ($applicant->major ?? 'N/A') . '. Forwarded to Admin for account creation.', 'User', $applicant->id);
        return redirect()->route('registrar.dashboard')->with('success', 'Application for ' . $applicant->name . ' verified and forwarded to Admin.');
    })->name('registrar.verify-applicant');

    Route::post('/registrar/decline-applicant/{id}', function ($id) {
        $applicant = User::where('id', $id)->where('role', 'applicant')->firstOrFail();
        AuditLog::record('Applicant Declined', 'Registrar declined and removed application for ' . $applicant->name . ' (' . $applicant->email . '), program: ' . ($applicant->major ?? 'N/A') . '.', 'User', $applicant->id);
        $name = $applicant->name;
        $applicant->delete();
        return redirect()->route('registrar.dashboard')->with('success', 'Application for ' . $name . ' has been declined and removed.');
    })->name('registrar.decline-applicant');
    }); // end role:registrar,admission


    // --- DEPARTMENT CHAIR HUB ENDPOINTS ---
    Route::middleware('role:chair')->group(function () {
    Route::get('/approver/dashboard', function () {
        $clearances = Clearance::has('user')->with('user')->get();
        $pendingEnrollments = Enrollment::with(['user', 'sections.subject'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        $pendingChanges = MatriculationChange::with(['user', 'items.section.subject', 'items.replacedSection.subject'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        return view('approver.dashboard', compact('clearances', 'pendingEnrollments', 'pendingChanges'));
    })->name('approver.dashboard');

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
            $clearance->update(['chair_status' => 'Approved']);
            AuditLog::record('Clearance Signed', 'Department Chair signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
            return redirect()->route('approver.dashboard')->with('success', 'Department structural sign-off written successfully.');
        }
        return redirect()->route('approver.dashboard')->with('error', 'Record not found.');
    })->name('approver.sign');
    }); // end role:chair

    Route::middleware('role:faculty')->group(function () {
        Route::get('/faculty/schedule', function () {
            $sections = auth()->user()->taughtSections()
                ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))
                ->with(['subject', 'roomEntity'])
                ->orderBy('start_time')
                ->get();

            return view('faculty.schedule', ['sections' => $sections]);
        })->name('faculty.schedule');
    }); // end role:faculty

    // Secure document view/download: owner or registrar/admission only.
    Route::get('/documents/{submission}', function (DocumentSubmission $submission) {
        $user = Auth::user();
        $allowed = $user->id === $submission->user_id || in_array($user->role, ['registrar', 'admission']);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->response($submission->file_path, $submission->original_name);
    })->middleware('auth')->name('documents.show');


    // --- CASHIER HUB ENDPOINTS ---
    Route::middleware('role:cashier')->group(function () {
    Route::get('/cashier/dashboard', [AuthController::class, 'showCashierDashboard'])->name('cashier.dashboard');
    Route::post('/cashier/approve', [AuthController::class, 'approveClearance'])->name('cashier.approve');
    Route::get('/cashier/transactions', [AuthController::class, 'showCashierTransactions'])->name('cashier.transactions');
    Route::get('/cashier/accounts', [AuthController::class, 'showCashierAccounts'])->name('cashier.accounts');
    }); // end role:cashier

    // --- MASTER SYSTEM ADMINISTRATIVE LAYER ---
    Route::middleware('role:admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        $verifiedApplicants = User::where('role', 'verified_applicant')->orderByDesc('created_at')->get();
        return view('admin.dashboard', compact('verifiedApplicants'));
    })->name('admin.dashboard');

    // Single create-student page — handles both walk-in and admission queue (pass ?from={id} for pre-fill)
    Route::get('/admin/students/create', function (Request $request) {
        $applicant = null;
        if ($request->query('from')) {
            $applicant = User::where('id', $request->query('from'))
                ->where('role', 'verified_applicant')->first();
        }
        $programs = Program::orderBy('level')->orderBy('code')->get();
        return view('admin.create-student', compact('applicant', 'programs'));
    })->name('admin.students.create');

    Route::post('/admin/students/create', function (Request $request) {
        $applicantId = $request->input('applicant_id');

        // If no explicit applicant_id but the email matches an existing applicant, auto-resolve it
        // so the walk-in form can still convert applicants without requiring the ?from= flow
        if (!$applicantId && $request->filled('email')) {
            $existing = User::where('email', $request->input('email'))
                ->whereIn('role', ['applicant', 'verified_applicant'])->first();
            if ($existing) {
                $applicantId = $existing->id;
            }
        }

        // Email/login_id uniqueness: if converting an existing applicant, exclude their own record
        $emailRule    = ['required', 'string', 'email', 'max:255', 'unique:users,email' . ($applicantId ? ',' . $applicantId : '')];
        $loginIdRule  = ['required', 'string', 'max:50',           'unique:users,login_id'];

        $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => $emailRule,
            'login_id'       => $loginIdRule,
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'major'          => ['required', 'string'],
            'year_level'     => ['required', 'string'],
            'section'        => ['nullable', 'string', 'max:50'],
            'sex'            => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth'  => ['nullable', 'date'],
            'address'        => ['nullable', 'string', 'max:500'],
            'applicant_type' => ['nullable', 'string'],
        ], [
            'email.unique'       => 'This email is already registered.',
            'login_id.unique'    => 'This Student ID is already taken.',
            'password.confirmed' => 'Password and confirmation do not match.',
        ]);

        $fields = [
            'login_id'       => $request->input('login_id'),
            'password'       => $request->input('password'),
            'role'           => 'student',
            'major'          => $request->input('major'),
            'year_level'     => $request->input('year_level'),
            'section'        => $request->input('section'),
            'sex'            => $request->input('sex'),
            'contact_number' => $request->input('contact_number'),
            'date_of_birth'  => $request->input('date_of_birth'),
            'address'        => $request->input('address'),
            'applicant_type' => $request->input('applicant_type'),
            'program_level'  => $request->input('program_level'),
        ];

        if ($applicantId) {
            // Converting a verified applicant — update in place
            $student = User::where('id', $applicantId)->where('role', 'verified_applicant')->firstOrFail();
            $student->update($fields);
        } else {
            // Walk-in / manual registration — create fresh
            $fields['name']  = $request->input('name');
            $fields['email'] = $request->input('email');
            $student = User::create($fields);
        }

        Clearance::firstOrCreate(
            ['user_id' => $student->id],
            ['admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending',
             'registrar_status' => 'Pending', 'library_status' => 'Approved', 'clinic_status' => 'Approved']
        );

        AuditLog::record('Account Created', 'Admin created student account for ' . $student->name . ' (Login ID: ' . $student->login_id . ', Program: ' . ($student->major ?? 'N/A') . ', Year: ' . ($student->year_level ?? 'N/A') . ').', 'User', $student->id);

        return redirect()->route('admin.students.create')
            ->with('success', 'Account created for ' . $student->name . '. Login ID: ' . $student->login_id . '.');
    })->name('admin.students.store');

    Route::get('/admin/audit', function () {
        $logs = AuditLog::orderByDesc('created_at')->paginate(50);
        return view('admin.audit', compact('logs'));
    })->name('admin.audit');

    Route::get('/admin/reports', function () {
        $clearances         = Clearance::has('user')->with('user')->get();
        $pendingApplicants  = User::where('role', 'applicant')->count();
        $verifiedApplicants = User::where('role', 'verified_applicant')->count();
        $totalStudents      = User::where('role', 'student')->count();
        $programBreakdown   = User::where('role', 'student')
            ->whereNotNull('major')->where('major', '!=', '')
            ->selectRaw('major, count(*) as count')
            ->groupBy('major')->orderByDesc('count')->get();
        return view('admin.reports', compact('clearances', 'pendingApplicants', 'verifiedApplicants', 'totalStudents', 'programBreakdown'));
    })->name('admin.reports');

    Route::get('/admin/curriculum', function () {
        return view('admin.curriculum');
    })->name('admin.curriculum');
    }); // end role:admin

    // Student Records — list all students + link to grade editor (Registrar)
    Route::middleware('role:registrar,admission')->group(function () {
    Route::get('/registrar/students', function () {
        $students = User::where('role', 'student')->orderBy('name')->get();
        return view('registrar.students', compact('students'));
    })->name('registrar.students');

    // Grade editor — GET: show, POST: save (Registrar)
    Route::get('/registrar/students/{id}/grades', function ($id) {
        $student = User::where('id', $id)->where('role', 'student')->firstOrFail();
        $grades  = StudentGrade::where('user_id', $id)->get()->keyBy('subject_code');
        return view('registrar.student-grades', compact('student', 'grades'));
    })->name('registrar.students.grades');

    Route::post('/registrar/students/{id}/grades', function (Request $request, $id) {
        $student = User::where('id', $id)->where('role', 'student')->firstOrFail();

        foreach ($request->input('grades', []) as $code => $rawGrade) {
            $grade = is_numeric($rawGrade) ? (int) $rawGrade : null;

            if ($grade === null || $grade === 0) {
                StudentGrade::where('user_id', $id)->where('subject_code', $code)->delete();
            } else {
                StudentGrade::updateOrCreate(
                    ['user_id' => $id, 'subject_code' => $code],
                    [
                        'status'      => $grade >= 75 ? 'Passed' : 'Failed',
                        'final_grade' => (string) $grade,
                    ]
                );
            }
        }

        AuditLog::record('Grades Updated', 'Registrar updated grade records for ' . $student->name . ' (' . $student->login_id . ').', 'User', $student->id);
        return redirect()->route('registrar.students.grades', $id)->with('success', 'Grades saved.');
    })->name('registrar.students.grades.store');

    Route::get('/registrar/reports', function () {
        $clearances        = Clearance::has('user')->with('user')->get();
        $pendingApplicants = User::where('role', 'applicant')->count();
        return view('registrar.reports', compact('clearances', 'pendingApplicants'));
    })->name('registrar.reports');
    }); // end role:registrar,admission
});