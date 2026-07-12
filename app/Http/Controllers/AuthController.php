<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Student;
use App\Models\ShsStrand;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\TransactionLedger;

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

        // Give precise feedback: account found but wrong password vs. account not found at all
        $userExists = User::where('login_id', $login)->orWhere('email', $login)->exists();

        if ($userExists) {
            return back()->withErrors([
                'login_id' => 'The password you entered is incorrect. Please verify your credentials and try again.',
            ])->onlyInput('login_id');
        }

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
        return view('auth.apply', compact('strands'));
    }

    // 4. Store incoming application as pending — admin creates the account and emails credentials
    public function processApplication(Request $request)
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['required', 'string', 'max:20'],
            'date_of_birth'  => ['required', 'date'],
            'sex'            => ['required', 'string', Rule::in(['Male', 'Female'])],
            'address'        => ['required', 'string', 'max:500'],
            'last_school'    => ['required', 'string', 'max:255'],
            'year_graduated' => ['required', 'string', 'max:10'],
            'applicant_type' => ['required', 'string', Rule::in(['NEW', 'TRANSFEREE', 'RETURNEE'])],
            'program_key'    => ['required', 'string'],
            'program_name'   => ['required', 'string'],
            'program_level'  => ['required', 'string'],
            'remarks'        => ['nullable', 'string', 'max:1000'],
        ], [
            'email.unique' => 'An application with this email address already exists. If you need help, contact our admissions office.',
        ]);

        User::create([
            'name'              => $request->input('name'),
            'email'             => $request->input('email'),
            'login_id'          => 'APPL-' . strtoupper(Str::random(8)),
            'major'             => $request->input('program_name'),
            'role'              => 'applicant',
            'password'          => Hash::make(Str::random(32)),
            'contact_number'    => $request->input('contact_number'),
            'date_of_birth'     => $request->input('date_of_birth'),
            'sex'               => $request->input('sex'),
            'address'           => $request->input('address'),
            'last_school'       => $request->input('last_school'),
            'year_graduated'    => $request->input('year_graduated'),
            'applicant_type'    => $request->input('applicant_type'),
            'program_level'     => $request->input('program_level'),
            'applicant_remarks' => $request->input('remarks'),
        ]);

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
    public function showCashierDashboard()
    {
        // Fixed: Swapped MySQL FIELD() function with a cross-platform conditional CASE block
        $clearances = Clearance::with('user')
            ->orderByRaw("CASE WHEN cashier_status = 'Pending' THEN 0 ELSE 1 END ASC")
            ->get();

        $totalOutstandingDocs = Clearance::where('cashier_status', 'Pending')->count();
        
        return view('cashier.dashboard', compact('clearances', 'totalOutstandingDocs'));
    }

    /**
     * Display cashier transactions ledger.
     */
    public function showCashierTransactions()
    {
        $transactions = TransactionLedger::with(['user', 'processor'])->orderBy('created_at', 'desc')->get();
        return view('cashier.transactions', compact('transactions'));
    }

    /**
     * Display cashier student accounts / clearances.
     */
    public function showCashierAccounts()
    {
        $accounts = Clearance::with('user')->orderBy('created_at', 'desc')->get();
        return view('cashier.accounts', compact('accounts'));
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
        $student = Student::where('user_id', $user->id)->first();

        $palette = ['bg-blue-600', 'bg-emerald-600', 'bg-violet-600', 'bg-orange-500', 'bg-cyan-600', 'bg-teal-600', 'bg-rose-500', 'bg-amber-500', 'bg-brandGreen'];

        $enrollment = Enrollment::with('sections.subject')
            ->where('user_id', $user->id)
            ->where('status', 'enrolled')
            ->latest()
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

        return view('schedule', compact('user', 'student', 'subjects'));
    }

    // 7. Process administrative clearance override and write long-term transaction ledger records
    public function approveClearance(Request $request)
    {
        $request->validate([
            'user_id'      => ['required', 'integer'],
            'reference_no' => ['required', 'string'],
            'amount'       => ['required', 'string']
        ]);

        $cleanAmount = (float) str_replace(['₱', ',', ' '], '', $request->input('amount'));
        $clearance = Clearance::where('user_id', $request->input('user_id'))->firstOrFail();

        DB::beginTransaction();

        try {
            $clearance->update([
                'cashier_status' => 'Approved',
            ]);

            TransactionLedger::create([
                'user_id'      => $request->input('user_id'),
                'reference_no' => $request->input('reference_no'),
                'amount'       => $cleanAmount,
                'status'       => 'Settled',
                'processed_by' => Auth::id() ?? 1, 
                'remarks'      => 'Cleared and signed off via Administrative Console Queue.'
            ]);

            DB::commit();
            \App\Models\AuditLog::record('Payment Approved', 'Cashier approved payment for student ID ' . $request->input('user_id') . '. Ref #' . $request->input('reference_no') . ', Amount: ₱' . number_format($cleanAmount, 2) . '.', 'Clearance', $clearance->id);
            return redirect()->back()->with('success', 'Clearance finalized! Transaction successfully archived.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Execution Error: ' . $e->getMessage());
        }
    }
}