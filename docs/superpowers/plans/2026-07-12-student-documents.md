# Student Document Submissions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Students upload real proof documents (Form 137 etc.) from the clearance page; the registrar views each file and accepts/rejects it with remarks; students see the outcome.

**Architecture:** One new `document_submissions` table + `DocumentSubmission` model. The clearance modal's fake `setTimeout` upload becomes a real form submit into the existing `clearance.submitRequirement` route, which stores the file on the **private** local disk. A guarded `GET /documents/{submission}` route streams files to the owner or registrar/admission staff. The registrar dashboard gains a review panel (plain Blade, like the rest of that page); the student clearance page gains a submission-history list. Notif bell gets entries on both sides.

**Tech Stack:** Laravel 12, PHPUnit (RefreshDatabase, SQLite `:memory:`, `Storage::fake`), Blade + CDN Tailwind. No React needed.

## Global Constraints

- File rules: `mimes:pdf,jpg,jpeg,png`, `max:5120` (5 MB). Document types: `form137`, `form138`, `birth_cert`, `good_moral`, `other`.
- Statuses: `pending` (default) → `accepted` | `rejected`. Reject requires `remarks` (max 500). Reviewed rows are immutable (re-review redirects back with an error flash).
- Files stored via `->store('documents', 'local')` — the `local` disk root is `storage_path('app/private')` (see `config/filesystems.php:33-35`). Nothing under `public/`.
- Document review is independent of clearance signing: never touch `clearances.registrar_status` from these routes.
- Every state change is audit-logged via `AuditLog::record(action, description, targetType, targetId)`. Actions: `Document Submitted`, `Document Reviewed`.
- Brand colors: brandNavy `#0B3C5D`, brandGreen `#1D7A46`, brandGold `#E2A700`; dark-mode variants required. New notif fields flow through the existing `escNotif()` escaping automatically (it is applied at render time in `notif-script.blade.php`).
- Run tests with `php artisan test` (optionally `--filter Name`). Suite currently at 99 passed.
- TDD: write tests, see them fail, implement, see them pass. Commit at the end of each task.

---

### Task 1: Schema, model, factory

**Files:**
- Create: `database/migrations/2026_07_12_200001_create_document_submissions_table.php`
- Create: `app/Models/DocumentSubmission.php`
- Create: `database/factories/DocumentSubmissionFactory.php`
- Modify: `app/Models/User.php` (add `documentSubmissions()` after `taughtSections()`)
- Test: `tests/Unit/DocumentSubmissionTest.php`

**Interfaces:**
- Consumes: existing `User` model.
- Produces: `DocumentSubmission` model — `$fillable = ['user_id','document_type','notes','file_path','original_name','mime_type','size','status','remarks','reviewed_by','reviewed_at']`; `user(): BelongsTo`; `reviewer(): BelongsTo` (FK `reviewed_by`); `typeLabel(): string`; `TYPES` const map. `User::documentSubmissions(): HasMany`. Factory defaults: student user, `form137`, `pending`, fake pdf metadata.

- [ ] **Step 1: Write the failing test**

`tests/Unit/DocumentSubmissionTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_relations_defaults_and_type_label(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $sub = DocumentSubmission::factory()->create(['user_id' => $student->id, 'document_type' => 'form137']);

        $this->assertTrue($sub->user->is($student));
        $this->assertSame('pending', $sub->status);
        $this->assertSame('Form 137 — Permanent Record / Senior HS Report Card', $sub->typeLabel());
        $this->assertTrue($student->documentSubmissions()->whereKey($sub->id)->exists());

        $sub->update(['status' => 'accepted', 'reviewed_by' => $registrar->id, 'reviewed_at' => now()]);
        $this->assertTrue($sub->fresh()->reviewer->is($registrar));
        $this->assertNotNull($sub->fresh()->reviewed_at);
    }

    public function test_type_label_covers_all_modal_options(): void
    {
        $expected = [
            'form137' => 'Form 137 — Permanent Record / Senior HS Report Card',
            'form138' => 'Form 138 — Report Card',
            'birth_cert' => 'PSA Birth Certificate',
            'good_moral' => 'Certificate of Good Moral Character',
            'other' => 'Other Supporting Document',
        ];

        foreach ($expected as $key => $label) {
            $sub = DocumentSubmission::factory()->make(['document_type' => $key]);
            $this->assertSame($label, $sub->typeLabel());
        }
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DocumentSubmissionTest`
Expected: FAIL — `Class "App\Models\DocumentSubmission" not found`.

- [ ] **Step 3: Create migration, model, factory, User relation**

`database/migrations/2026_07_12_200001_create_document_submissions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 20);
            $table->string('notes', 1000)->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->string('status', 10)->default('pending'); // pending | accepted | rejected
            $table->string('remarks', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_submissions');
    }
};
```

`app/Models/DocumentSubmission.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSubmission extends Model
{
    use HasFactory;

    public const TYPES = [
        'form137' => 'Form 137 — Permanent Record / Senior HS Report Card',
        'form138' => 'Form 138 — Report Card',
        'birth_cert' => 'PSA Birth Certificate',
        'good_moral' => 'Certificate of Good Moral Character',
        'other' => 'Other Supporting Document',
    ];

    protected $fillable = [
        'user_id', 'document_type', 'notes', 'file_path', 'original_name',
        'mime_type', 'size', 'status', 'remarks', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->document_type] ?? $this->document_type;
    }
}
```

`database/factories/DocumentSubmissionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'document_type' => 'form137',
            'notes' => null,
            'file_path' => 'documents/'.$this->faker->uuid.'.pdf',
            'original_name' => 'form137-scan.pdf',
            'mime_type' => 'application/pdf',
            'size' => 204800,
            'status' => 'pending',
        ];
    }
}
```

In `app/Models/User.php`, add directly after the `taughtSections()` method:

```php
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter DocumentSubmissionTest`
Expected: PASS (2 tests). Then `php artisan test` — full suite green (101 passed).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_12_200001_create_document_submissions_table.php app/Models/DocumentSubmission.php database/factories/DocumentSubmissionFactory.php app/Models/User.php tests/Unit/DocumentSubmissionTest.php
git commit -m "feat: document submissions schema and model"
```

---

### Task 2: Real upload — storage logic in clearance.submitRequirement

**Files:**
- Modify: `routes/web.php` (imports; `GET /clearance` route ~line 84; `POST /clearance/submit-requirement` route ~lines 88–102)
- Test: `tests/Feature/DocumentUploadTest.php`

**Interfaces:**
- Consumes: Task 1 (`DocumentSubmission::create`, `typeLabel()`).
- Produces: `POST /clearance/submit-requirement` persists the file to the `local` disk under `documents/` and creates a row; `GET /clearance` passes `$submission` (latest or null) and `$submissions` (all of the student's rows, newest first) to the view.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/DocumentUploadTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'student']);
    }

    public function test_student_can_submit_a_document(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->student())->post('/clearance/submit-requirement', [
            'document' => UploadedFile::fake()->create('form137.pdf', 500, 'application/pdf'),
            'document_type' => 'form137',
            'notes' => 'Certified true copy attached.',
        ]);

        $response->assertRedirect(route('clearance'));
        $response->assertSessionHas('success');

        $sub = DocumentSubmission::first();
        $this->assertNotNull($sub);
        $this->assertSame('form137', $sub->document_type);
        $this->assertSame('form137.pdf', $sub->original_name);
        $this->assertSame('pending', $sub->status);
        Storage::disk('local')->assertExists($sub->file_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Document Submitted']);
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->student())->post('/clearance/submit-requirement', [
            'document' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
            'document_type' => 'form137',
        ])->assertSessionHasErrors('document');

        $this->assertSame(0, DocumentSubmission::count());
    }

    public function test_wrong_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->student())->post('/clearance/submit-requirement', [
            'document' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
            'document_type' => 'form137',
        ])->assertSessionHasErrors('document');
    }

    public function test_unknown_document_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->student())->post('/clearance/submit-requirement', [
            'document' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            'document_type' => 'diploma',
        ])->assertSessionHasErrors('document_type');
    }

    public function test_guest_cannot_submit(): void
    {
        Storage::fake('local');

        $this->post('/clearance/submit-requirement', [
            'document' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            'document_type' => 'form137',
        ])->assertRedirect();

        $this->assertSame(0, DocumentSubmission::count());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DocumentUploadTest`
Expected: FAIL — first test finds no `DocumentSubmission` row (route stores nothing yet); the `max:10240` rule also lets the 6000 KB file pass the oversize test.

- [ ] **Step 3: Implement the route changes**

In `routes/web.php`, add to the imports block (alphabetical, after `use App\Models\Clearance;`):

```php
use App\Models\DocumentSubmission;
```

and after `use Illuminate\Support\Facades\Route;`:

```php
use Illuminate\Support\Facades\Storage;
```

(`Storage` is used by Task 3's download route; adding it now keeps one import edit.)

In the `GET /clearance` route, replace:

```php
        $submission = null;
        return view('clearance', compact('clearance', 'submission'));
```

with:

```php
        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
        $submission = $submissions->first();
        return view('clearance', compact('clearance', 'submission', 'submissions'));
```

Replace the whole `POST /clearance/submit-requirement` route with:

```php
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter DocumentUploadTest`
Expected: PASS (5 tests). Then `php artisan test` — full suite green (106 passed).

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/DocumentUploadTest.php
git commit -m "feat: persist student document uploads to private storage"
```

---

### Task 3: Secure download/view route

**Files:**
- Modify: `routes/web.php` (new route after the `}); // end role:faculty` line)
- Test: `tests/Feature/DocumentDownloadTest.php`

**Interfaces:**
- Consumes: Task 1 model; `Storage` import from Task 2.
- Produces: `GET /documents/{submission}`, name `documents.show`, middleware `auth`. Allows the owning student or `registrar`/`admission` roles; streams inline with the original filename; 403 otherwise; 404 when the file is missing on disk.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/DocumentDownloadTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function submissionWithFile(): DocumentSubmission
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.pdf', '%PDF-1.4 fake');

        return DocumentSubmission::factory()->create([
            'file_path' => 'documents/test.pdf',
            'original_name' => 'form137-scan.pdf',
        ]);
    }

    public function test_owner_can_view_their_file(): void
    {
        $sub = $this->submissionWithFile();

        $response = $this->actingAs($sub->user)->get("/documents/{$sub->id}");

        $response->assertOk();
        $this->assertStringContainsString('form137-scan.pdf', $response->headers->get('content-disposition'));
    }

    public function test_registrar_can_view_any_file(): void
    {
        $sub = $this->submissionWithFile();
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->get("/documents/{$sub->id}")->assertOk();
    }

    public function test_other_students_are_forbidden(): void
    {
        $sub = $this->submissionWithFile();
        $other = User::factory()->create(['role' => 'student']);

        $this->actingAs($other)->get("/documents/{$sub->id}")->assertForbidden();
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::fake('local');
        $sub = DocumentSubmission::factory()->create(['file_path' => 'documents/gone.pdf']);

        $this->actingAs($sub->user)->get("/documents/{$sub->id}")->assertNotFound();
    }

    public function test_guest_is_redirected(): void
    {
        $sub = $this->submissionWithFile();

        $this->get("/documents/{$sub->id}")->assertRedirect();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DocumentDownloadTest`
Expected: FAIL — 404 on `/documents/{id}` (route missing).

- [ ] **Step 3: Add the route**

In `routes/web.php`, directly after the line `}); // end role:faculty`, add:

```php
    // Secure document view/download: owner or registrar/admission only.
    Route::get('/documents/{submission}', function (DocumentSubmission $submission) {
        $user = Auth::user();
        $allowed = $user->id === $submission->user_id || in_array($user->role, ['registrar', 'admission']);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->response($submission->file_path, $submission->original_name);
    })->middleware('auth')->name('documents.show');
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter DocumentDownloadTest`
Expected: PASS (5 tests). Then `php artisan test` — full suite green (111 passed).

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/DocumentDownloadTest.php
git commit -m "feat: authorized document view route"
```

---

### Task 4: Registrar review — routes and dashboard panel

**Files:**
- Modify: `routes/web.php` (registrar dashboard route ~line 151; new accept/reject routes inside the `role:registrar,admission` group after `registrar.decline-applicant`)
- Modify: `resources/views/registrar/dashboard.blade.php` (new panel after the clearances table panel, before the content wrapper closes)
- Test: `tests/Feature/RegistrarDocumentReviewTest.php`

**Interfaces:**
- Consumes: Tasks 1–3 (`DocumentSubmission`, `typeLabel()`, `documents.show`).
- Produces: routes `registrar.documents.accept` / `registrar.documents.reject` (`POST /registrar/documents/{submission}/accept|reject`); dashboard view receives `$documentSubmissions` (all rows, newest first, with `user` eager-loaded).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/RegistrarDocumentReviewTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarDocumentReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = User::factory()->create(['role' => 'registrar']);
    }

    public function test_dashboard_lists_submissions(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $response = $this->actingAs($this->registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $this->assertTrue($response->viewData('documentSubmissions')->contains('id', $sub->id));
        $response->assertSee('Student Document Submissions');
        $response->assertSee($sub->original_name);
    }

    public function test_registrar_can_accept(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/accept")
            ->assertRedirect(route('registrar.dashboard'));

        $sub->refresh();
        $this->assertSame('accepted', $sub->status);
        $this->assertSame($this->registrar->id, $sub->reviewed_by);
        $this->assertNotNull($sub->reviewed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Document Reviewed']);
    }

    public function test_reject_requires_remarks(): void
    {
        $sub = DocumentSubmission::factory()->create();

        $this->actingAs($this->registrar)
            ->from('/registrar/dashboard')
            ->post("/registrar/documents/{$sub->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/reject", ['remarks' => 'Scan is unreadable, please re-upload.'])
            ->assertRedirect(route('registrar.dashboard'));

        $sub->refresh();
        $this->assertSame('rejected', $sub->status);
        $this->assertSame('Scan is unreadable, please re-upload.', $sub->remarks);
    }

    public function test_reviewed_submission_cannot_be_re_reviewed(): void
    {
        $sub = DocumentSubmission::factory()->create(['status' => 'accepted']);

        $this->actingAs($this->registrar)
            ->post("/registrar/documents/{$sub->id}/reject", ['remarks' => 'Changed my mind.'])
            ->assertRedirect(route('registrar.dashboard'));

        $this->assertSame('accepted', $sub->fresh()->status);
    }

    public function test_students_cannot_review(): void
    {
        $sub = DocumentSubmission::factory()->create();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post("/registrar/documents/{$sub->id}/accept")
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter RegistrarDocumentReviewTest`
Expected: FAIL — `viewData('documentSubmissions')` is null; accept/reject routes 404.

- [ ] **Step 3: Add routes and dashboard data**

In `routes/web.php`, change the registrar dashboard route body from:

```php
    Route::get('/registrar/dashboard', function () {
```

so it also loads submissions — the route currently builds `$clearances` and `$applicants`; add the submissions query before the return and include it in `compact(...)`:

```php
        $documentSubmissions = DocumentSubmission::with('user')->latest()->get();
        return view('registrar.dashboard', compact('clearances', 'applicants', 'documentSubmissions'));
```

(Replace the existing `return view('registrar.dashboard', compact('clearances', 'applicants'));` line.)

Inside the same `role:registrar,admission` group, after the `registrar.decline-applicant` route, add:

```php
    // Document submission review
    Route::post('/registrar/documents/{submission}/accept', function (DocumentSubmission $submission) {
        if ($submission->status !== 'pending') {
            return redirect()->route('registrar.dashboard')->with('error', 'This document has already been reviewed.');
        }

        $submission->update(['status' => 'accepted', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        AuditLog::record('Document Reviewed', 'Registrar accepted ' . $submission->typeLabel() . ' from ' . ($submission->user->name ?? 'ID ' . $submission->user_id) . '.', 'DocumentSubmission', $submission->id);

        return redirect()->route('registrar.dashboard')->with('success', 'Document accepted.');
    })->name('registrar.documents.accept');

    Route::post('/registrar/documents/{submission}/reject', function (Request $request, DocumentSubmission $submission) {
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($submission->status !== 'pending') {
            return redirect()->route('registrar.dashboard')->with('error', 'This document has already been reviewed.');
        }

        $submission->update(['status' => 'rejected', 'remarks' => $request->input('remarks'), 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        AuditLog::record('Document Reviewed', 'Registrar rejected ' . $submission->typeLabel() . ' from ' . ($submission->user->name ?? 'ID ' . $submission->user_id) . '.', 'DocumentSubmission', $submission->id);

        return redirect()->route('registrar.dashboard')->with('success', 'Document rejected and returned to the student.');
    })->name('registrar.documents.reject');
```

- [ ] **Step 4: Add the dashboard panel**

In `resources/views/registrar/dashboard.blade.php`, the clearances panel ends with (lines ~270–275):

```blade
                        </table>
                    </div>
                </div>

            </div>
        </main>
```

Insert the new panel between the clearances panel's closing `</div>` and the wrapper's `</div>`, so the region becomes:

```blade
                        </table>
                    </div>
                </div>

                {{-- STUDENT DOCUMENT SUBMISSIONS --}}
                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-brandNavy dark:text-white tracking-wide">Student Document Submissions</h3>
                        @php $pendingDocCount = isset($documentSubmissions) ? $documentSubmissions->where('status', 'pending')->count() : 0; @endphp
                        @if($pendingDocCount > 0)
                            <span class="px-2 py-0.5 text-[9px] font-black bg-brandGold text-white rounded-full">{{ $pendingDocCount }} pending</span>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-4 px-6">Student</th>
                                    <th class="py-4 px-6">Document</th>
                                    <th class="py-4 px-6">Submitted</th>
                                    <th class="py-4 px-6 text-center">Status</th>
                                    <th class="py-4 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @forelse($documentSubmissions ?? [] as $doc)
                                <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors align-top">
                                    <td class="py-5 px-6">
                                        <span class="font-bold text-brandNavy dark:text-white block">{{ $doc->user->name ?? '—' }}</span>
                                        <span class="font-mono text-brandNavy/60 dark:text-slate-400">{{ $doc->user->login_id ?? '—' }}</span>
                                    </td>
                                    <td class="py-5 px-6">
                                        <span class="font-semibold text-brandNavy dark:text-slate-200 block">{{ $doc->typeLabel() }}</span>
                                        <a href="{{ route('documents.show', $doc) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                            <i class="fa-solid fa-paperclip mr-1"></i>{{ $doc->original_name }} ({{ number_format($doc->size / 1024, 0) }} KB)
                                        </a>
                                        @if($doc->notes)
                                            <p class="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1 italic">"{{ $doc->notes }}"</p>
                                        @endif
                                    </td>
                                    <td class="py-5 px-6 text-brandNavy/60 dark:text-slate-400">{{ $doc->created_at->format('M d, Y g:i A') }}</td>
                                    <td class="py-5 px-6 text-center">
                                        @if($doc->status === 'pending')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                                        @elseif($doc->status === 'accepted')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Accepted</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Rejected</span>
                                            @if($doc->remarks)
                                                <p class="text-[10px] text-red-500/80 mt-1 max-w-40 mx-auto">{{ $doc->remarks }}</p>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-5 px-6 text-right">
                                        @if($doc->status === 'pending')
                                            <div class="flex flex-col items-end gap-2">
                                                <form action="{{ route('registrar.documents.accept', $doc) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-brandGreen hover:bg-emerald-600 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        <i class="fa-solid fa-check mr-1"></i>Accept
                                                    </button>
                                                </form>
                                                <form action="{{ route('registrar.documents.reject', $doc) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for rejection"
                                                        class="w-44 bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-[11px] text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 px-3 py-2 rounded focus:outline-none focus:border-red-400">
                                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-brandNavy/40 dark:text-slate-500 uppercase tracking-wider">Reviewed</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-brandNavy/40 dark:text-slate-500 font-medium">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-folder-open text-2xl text-brandNavy/20 dark:text-slate-600"></i>
                                            <span>No document submissions yet.</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter RegistrarDocumentReviewTest`
Expected: PASS (5 tests). Then `php artisan test` — full suite green (116 passed).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/registrar/dashboard.blade.php tests/Feature/RegistrarDocumentReviewTest.php
git commit -m "feat: registrar document review queue"
```

---

### Task 5: Student clearance page — real submit and submission history

**Files:**
- Modify: `resources/views/clearance.blade.php`
- Test: `tests/Feature/ClearancePageDocumentsTest.php`

**Interfaces:**
- Consumes: Task 2's `$submission`/`$submissions` view data; Task 3's `documents.show` route; `DocumentSubmission::typeLabel()`.
- Produces: the modal actually submits; page shows the student's own submission history with status badges and rejection remarks.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ClearancePageDocumentsTest.php`:

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

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter ClearancePageDocumentsTest`
Expected: FAIL — page has no "My Submitted Documents" section.

- [ ] **Step 3: Update the Blade page**

All edits in `resources/views/clearance.blade.php`:

**(a) Status var uses lowercase `pending`** — replace:

```blade
                $submissionPending = $hasSubmission && ($submission->status ?? 'Pending') === 'Pending';
```

with:

```blade
                $submissionPending = $hasSubmission && ($submission->status ?? '') === 'pending';
```

**(b) Pending banner filename** — in the `submissionPendingBanner` block, replace:

```blade
                                            @if(isset($submission->file_name))
                                                <div class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 bg-blue-600/5 px-2 py-1 rounded-lg">
                                                    <i class="fa-solid fa-file-pdf"></i>{{ $submission->file_name }}
                                                </div>
                                            @endif
```

with:

```blade
                                            @if(isset($submission->original_name))
                                                <div class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 bg-blue-600/5 px-2 py-1 rounded-lg">
                                                    <i class="fa-solid fa-file-pdf"></i>{{ $submission->original_name }}
                                                </div>
                                            @endif
```

**(c) Validation error banner** — directly after the `@if(session('success')) ... @endif` block (~line 129), add:

```blade
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                    <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $errors->first() }}
                </div>
            @endif
```

**(d) Submission history list** — the main content region ends with (~lines 337–341):

```blade
                </div>

            </div>
        </div>
    </main>
```

Insert the history panel so the region becomes (new panel sits inside the scroll container, after the cards grid):

```blade
                </div>

            </div>

            {{-- MY SUBMITTED DOCUMENTS --}}
            @if(isset($submissions) && $submissions->isNotEmpty())
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                        <span class="font-bold text-brandNavy dark:text-slate-300"><i class="fa-solid fa-folder-open mr-2"></i>My Submitted Documents</span>
                        <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar Review</span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($submissions as $doc)
                            <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div class="min-w-0">
                                    <span class="font-bold text-brandNavy dark:text-slate-200 block">{{ $doc->typeLabel() }}</span>
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                        <i class="fa-solid fa-paperclip mr-1"></i>{{ $doc->original_name }}
                                    </a>
                                    <span class="text-brandNavy/50 dark:text-slate-500 ml-2">{{ $doc->created_at->format('M d, Y g:i A') }}</span>
                                    @if($doc->status === 'rejected' && $doc->remarks)
                                        <p class="text-[11px] text-red-500 mt-1"><i class="fa-solid fa-comment-dots mr-1"></i>Registrar: {{ $doc->remarks }}</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    @if($doc->status === 'pending')
                                        <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                                    @elseif($doc->status === 'accepted')
                                        <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Accepted</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>
```

**(e) Real submit** — in `openSubmitModal()`, delete these two lines (the success-state div is being removed):

```js
        const success = document.getElementById('successState');
```

and

```js
        success.classList.add('hidden');
```

Delete the whole success-state div (everything from `{{-- Success state (hidden initially) --}}` through its closing `</div>` — the block containing `id="successState"`).

In `handleSubmit()`, replace the fake upload — everything from the line `// Simulate upload (replace with actual form.submit() in production)` through the closing of the `setTimeout(...)` call (including its `}, <delay>);` line) — with a single line:

```js
        document.getElementById('submissionForm').submit();
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter ClearancePageDocumentsTest`
Expected: PASS (2 tests). Then `php artisan test` — full suite green (118 passed).
Run: `php artisan view:clear && php artisan view:cache` — no Blade syntax errors.

- [ ] **Step 5: Commit**

```bash
git add resources/views/clearance.blade.php tests/Feature/ClearancePageDocumentsTest.php
git commit -m "feat: real document submit and history on the clearance page"
```

---

### Task 6: Notif bell branches and final verification

**Files:**
- Modify: `resources/views/partials/notif-script.blade.php` (student branch + registrar branch)

**Interfaces:**
- Consumes: `DocumentSubmission` model, `typeLabel()`, existing `escNotif()` render-time escaping.
- Produces: student sees latest document status in the bell; registrar sees the pending count.

- [ ] **Step 1: Student branch**

In `resources/views/partials/notif-script.blade.php`, the student branch contains a `$latestMatriculationChange` block ending with:

```php
                } elseif ($latestMatriculationChange->status === 'rejected') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-xmark', 'color' => '#DC2626', 'title' => 'Change Request Returned', 'desc' => 'The Chair returned your change request: ' . \Illuminate\Support\Str::limit($latestMatriculationChange->remarks ?? 'See remarks.', 80), 'time' => 'Action needed'];
                }
            }
```

Directly after that closing `}` (still inside the `if ($cl)` block), add:

```php
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
```

- [ ] **Step 2: Registrar branch**

In the same file, the registrar branch has:

```php
        if ($pendingClearances > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-signature',  'color' => '#0B3C5D', 'title' => 'Clearances Need Signature',
                         'desc' => $pendingClearances . ' student clearance(s) are waiting for your sign-off.',     'time' => 'Action needed'];
        }
```

Directly after that block, add:

```php
        $pendingDocuments = \App\Models\DocumentSubmission::where('status', 'pending')->count();
        if ($pendingDocuments > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-folder-open', 'color' => '#E2A700', 'title' => 'Documents Awaiting Review',
                         'desc' => $pendingDocuments . ' document submission(s) awaiting your review.', 'time' => 'Action needed'];
        }
```

- [ ] **Step 3: Full verification**

Run: `php artisan view:clear && php artisan view:cache` — no Blade syntax errors.
Run: `php artisan test` — all PASS (118 passed).
Run: `php artisan migrate:fresh --seed` — no errors (resets local dev DB; expected).

- [ ] **Step 4: Commit**

```bash
git add resources/views/partials/notif-script.blade.php
git commit -m "feat: document submission notifications for students and registrar"
```

---

## Demo walkthrough (manual, after all tasks)

1. Log in as `2300410` (password `password`) → Clearance → "Submit Documents" → pick a small PDF → real upload; page reloads with the green flash and the "My Submitted Documents" list showing Pending.
2. Log in as a registrar account → dashboard shows "Student Document Submissions" with the file link (opens the PDF inline); Reject with remarks.
3. Back as the student → clearance page shows the red Rejected badge + registrar remarks; bell shows "Document Rejected". Resubmit from the modal.
4. As registrar, Accept the new submission → student's bell shows "Document Accepted".
