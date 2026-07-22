# Student Registry Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an admin-only "Student Registry" page that lists, searches, filters, and soft-deletes student accounts (closes capstone paper gap analysis finding #2 / Fig 62).

**Architecture:** Plain server-rendered Blade page (matching sibling admin pages), two new routes as inline closures in `routes/web.php` inside the existing `role:admin` group (same pattern as `admin.students.create`/`admin.departments`), a new `SoftDeletes`-backed `deleted_at` column on `users`.

**Tech Stack:** Laravel 12 routes/closures + Blade + Eloquent `SoftDeletes`, Tailwind (compiled, via `@vite`), PHPUnit feature tests with `RefreshDatabase`.

## Global Constraints

- Soft delete only — never hard-delete a student (`docs/superpowers/specs/2026-07-23-student-registry-design.md`, "Why soft delete, not hard delete").
- Registry lists `role = 'student'` accounts only.
- The destroy route must 404 (not delete) when targeting any non-student role — this is a guard against misuse, not a UI nicety.
- No edit page, no restore UI — out of scope per spec.
- Follow the codebase's existing convention of an `AuditLog::record(...)` call on every admin mutating action (seen on every other admin route in `routes/web.php`).
- All admin routes are `role:admin`-gated closures directly in `routes/web.php` — do not introduce a controller class, stay consistent with every sibling admin route.

---

### Task 1: Soft-delete support on the `users` table

**Files:**
- Create: `database/migrations/2026_07_23_100001_add_soft_deletes_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/UserSoftDeleteTest.php`

**Interfaces:**
- Produces: `User` gains Eloquent `SoftDeletes` — `$user->delete()` sets `deleted_at` instead of removing the row; `User::where(...)` and all default queries (including `AuthController::login`'s lookup) automatically exclude soft-deleted rows via Eloquent's global scope. Later tasks call `$user->delete()` directly — no new method name to remember.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleted_user_is_excluded_from_default_queries(): void
    {
        $student = User::factory()->create(['role' => 'student', 'login_id' => 'sr-del-01']);

        $student->delete();

        $this->assertNotNull($student->fresh()->deleted_at ?? null);
        $this->assertDatabaseHas('users', ['id' => $student->id]); // row still exists
        $this->assertNull(User::where('login_id', 'sr-del-01')->first()); // but hidden from default queries
    }

    public function test_soft_deleted_student_cannot_log_in(): void
    {
        $student = User::factory()->create(['role' => 'student', 'login_id' => 'sr-del-02']);
        $student->delete();

        $this->post('/login', ['login_id' => 'sr-del-02', 'password' => 'password'])
            ->assertSessionHasErrors('login_id');

        $this->assertGuest();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserSoftDeleteTest`
Expected: FAIL — `deleted_at` column doesn't exist / `delete()` hard-deletes so `fresh()` returns `null`.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
```

- [ ] **Step 4: Add the `SoftDeletes` trait to the `User` model**

In `app/Models/User.php`, add the import and use the trait:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
```

(add alongside the existing `use Database\Factories\UserFactory;` etc. imports at the top)

```php
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;
```

- [ ] **Step 5: Run migration and test**

Run: `php artisan migrate`
Expected: `2026_07_23_100001_add_soft_deletes_to_users_table` migrates successfully.

Run: `php artisan test --filter=UserSoftDeleteTest`
Expected: PASS (both tests).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_23_100001_add_soft_deletes_to_users_table.php app/Models/User.php tests/Feature/UserSoftDeleteTest.php
git commit -m "feat: add soft-delete support to User model"
```

---

### Task 2: Student Registry index page (list, search, filter)

**Files:**
- Modify: `routes/web.php:545` (insert new route immediately after the `admin.students.store` route, i.e. after line 618, before `admin.audit` at line 620)
- Create: `resources/views/admin/students/index.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php` (sidebar nav link)
- Modify: `resources/views/admin/create-student.blade.php` (sidebar nav link)
- Modify: `resources/views/admin/curriculum.blade.php` (sidebar nav link)
- Modify: `resources/views/admin/departments.blade.php` (sidebar nav link)
- Modify: `resources/views/admin/audit.blade.php` (sidebar nav link)
- Modify: `resources/views/admin/reports.blade.php` (sidebar nav link)
- Test: `tests/Feature/AdminStudentRegistryTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (from Task 1, now with `SoftDeletes`), `App\Models\Program` (existing, used identically to `admin.students.create`'s `$programs = Program::orderBy('level')->orderBy('code')->get();`).
- Produces: named route `admin.students.index` (`GET /admin/students`), rendering `admin.students.index` view. Task 3 adds the delete button/form to this same view and a new `admin.students.destroy` route below this one.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentRegistryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_index_lists_only_student_accounts(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'Alice Student']);
        User::factory()->create(['role' => 'chair', 'name' => 'Bob Chair']);
        User::factory()->create(['role' => 'admin', 'name' => 'Carol Admin']);

        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee('Alice Student');
        $response->assertDontSee('Bob Chair');
        $response->assertDontSee('Carol Admin');
    }

    public function test_index_excludes_soft_deleted_students(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Deleted Student']);
        $student->delete();

        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertDontSee('Deleted Student');
    }

    public function test_index_search_matches_name_or_login_id(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'Zed Zephyr', 'login_id' => '2026-99001']);
        User::factory()->create(['role' => 'student', 'name' => 'Other Person', 'login_id' => '2026-99002']);

        $byName = $this->actingAs($this->admin)->get('/admin/students?q=Zephyr');
        $byName->assertSee('Zed Zephyr');
        $byName->assertDontSee('Other Person');

        $byId = $this->actingAs($this->admin)->get('/admin/students?q=99002');
        $byId->assertSee('Other Person');
        $byId->assertDontSee('Zed Zephyr');
    }

    public function test_index_filters_by_program_and_year_level(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'BSOA First Year', 'major' => 'BSOA', 'year_level' => '1st Year']);
        User::factory()->create(['role' => 'student', 'name' => 'BSIT Second Year', 'major' => 'BSIT', 'year_level' => '2nd Year']);

        $response = $this->actingAs($this->admin)->get('/admin/students?program=BSOA');
        $response->assertSee('BSOA First Year');
        $response->assertDontSee('BSIT Second Year');

        $response = $this->actingAs($this->admin)->get('/admin/students?year_level=2nd+Year');
        $response->assertSee('BSIT Second Year');
        $response->assertDontSee('BSOA First Year');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/students')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminStudentRegistryTest`
Expected: FAIL — route `admin.students.index` / `/admin/students` doesn't exist (404).

- [ ] **Step 3: Add the index route**

In `routes/web.php`, insert immediately after the `admin.students.store` route (after the closing `})->name('admin.students.store');` line, before the `admin.audit` route):

```php
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
    })->name('admin.students.index');
```

- [ ] **Step 4: Create the view**

Create `resources/views/admin/students/index.blade.php` — copy the shell (head, sidebar, header, flash-message blocks) from `resources/views/admin/create-student.blade.php:1-84`, keeping its exact sidebar style, then replace the page body:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Student Registry</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA HQ</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Core Control</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>System Overview</span>
            </a>
            <a href="{{ route('admin.students.create') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Create Student Account</span>
            </a>
            <a href="{{ route('admin.students.index') }}" class="flex items-center space-x-3 px-4 py-3 bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 rounded-xl font-bold text-sm">
                <span>Student Registry</span>
            </a>
            <a href="{{ route('admin.curriculum') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Curriculum</span>
            </a>
            <a href="{{ route('admin.departments') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Departments</span>
            </a>
            <a href="{{ route('admin.audit') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Audit Trail</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Reports</span>
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                    <i class="fa-solid fa-chevron-left mr-1"></i>Dashboard
                </a>
                <span class="text-brandNavy/20 dark:text-slate-700">/</span>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Student Registry</h2>
            </div>
            <div class="flex items-center gap-4">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', ['roleLabel' => 'Administrator'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10">

            @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-sm">
                {{ session('success') }}
            </div>
            @endif

            <div class="max-w-6xl mx-auto space-y-6">

                <div>
                    <h1 class="text-2xl font-black text-brandNavy dark:text-white">Student Registry</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-1">Search, filter, and manage existing student accounts.</p>
                </div>

                {{-- Search + Filters --}}
                <form method="GET" action="{{ route('admin.students.index') }}" class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl p-6 flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or Student ID"
                            class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Program</label>
                        <select name="program" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                            <option value="">All Programs</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->code }}" {{ request('program') == $program->code ? 'selected' : '' }}>{{ $program->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[160px]">
                        <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Year Level</label>
                        <select name="year_level" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                            <option value="">All Years</option>
                            <option value="1st Year" {{ request('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                            <option value="2nd Year" {{ request('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3rd Year" {{ request('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4th Year" {{ request('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-2.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors">Filter</button>
                    @if(request('q') || request('program') || request('year_level'))
                        <a href="{{ route('admin.students.index') }}" class="px-6 py-2.5 text-brandNavy/50 dark:text-slate-400 text-sm font-bold">Clear</a>
                    @endif
                </form>

                {{-- Table --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-lightBg dark:bg-slate-900/40 border-b border-brandNavy/8 dark:border-slate-800">
                            <tr class="text-[10px] font-black text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">
                                <th class="text-left px-6 py-3">Student ID</th>
                                <th class="text-left px-6 py-3">Name</th>
                                <th class="text-left px-6 py-3">Program</th>
                                <th class="text-left px-6 py-3">Year Level</th>
                                <th class="text-left px-6 py-3">Education Level</th>
                                <th class="text-right px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-6 py-3 font-mono text-brandNavy dark:text-slate-200">{{ $student->login_id }}</td>
                                    <td class="px-6 py-3 font-semibold text-brandNavy dark:text-white">{{ $student->name }}</td>
                                    <td class="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{{ $student->major ?? '—' }}</td>
                                    <td class="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{{ $student->year_level ?? '—' }}</td>
                                    <td class="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{{ $student->program_level ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right">
                                        {{-- delete form added in Task 3 --}}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-brandNavy/40 dark:text-slate-500">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>{{ $students->links() }}</div>

            </div>
        </div>
    </main>
</div>

@include('partials.notif-script')
</body>
</html>
```

- [ ] **Step 5: Add the sidebar nav link to the 6 existing admin pages**

In each of `resources/views/admin/dashboard.blade.php`, `resources/views/admin/create-student.blade.php`, `resources/views/admin/curriculum.blade.php`, `resources/views/admin/departments.blade.php`, `resources/views/admin/audit.blade.php`, `resources/views/admin/reports.blade.php`, insert a "Student Registry" `<a>` link immediately after that file's existing "Create Student Account" link, matching that file's own existing style exactly (each file already has a slightly different sidebar style — don't unify them, just replicate the pattern already in that specific file).

For `create-student.blade.php` (style: `rounded-xl`, hardcoded active class on the current page only), insert after its "Create Student Account" `</a>` (line 28):
```blade
            <a href="{{ route('admin.students.index') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Student Registry</span>
            </a>
```

For `departments.blade.php` and `audit.blade.php` (style: `border-l-2`, hardcoded active class on the current page only), insert after their "Create Student Account" `</a>`:
```blade
                <a href="{{ route('admin.students.index') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Student Registry</span>
                </a>
```

For `curriculum.blade.php` and `reports.blade.php` (style: `rounded-xl` + `Route::is()` ternary — these two files don't currently have a "Create Student Account" link at all, so insert right after the "System Overview" link):
```blade
            <a href="{{ route('admin.students.index') }}" class="flex items-center space-x-3 px-4 py-3 {{ Route::is('admin.students.index') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }} rounded-xl text-sm transition-colors"><span>Student Registry</span>
            </a>
```

For `dashboard.blade.php` (style: `border-l-2` + `Route::is()` ternary), insert right after its "Create Student Account" link block (after line 30, before the "Curriculum" link):
```blade
                <a href="{{ route('admin.students.index') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent {{ Route::is('admin.students.index') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">
                    <span>Student Registry</span>
                </a>
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=AdminStudentRegistryTest`
Expected: PASS (all 5 tests).

Run full suite to check nothing broke: `php artisan test`
Expected: all tests pass.

- [ ] **Step 7: Commit**

```bash
git add routes/web.php resources/views/admin/students/index.blade.php resources/views/admin/dashboard.blade.php resources/views/admin/create-student.blade.php resources/views/admin/curriculum.blade.php resources/views/admin/departments.blade.php resources/views/admin/audit.blade.php resources/views/admin/reports.blade.php tests/Feature/AdminStudentRegistryTest.php
git commit -m "feat: add Student Registry admin page (list, search, filter)"
```

---

### Task 3: Delete (soft-delete) action

**Files:**
- Modify: `routes/web.php` (insert new route immediately after `admin.students.index`, added in Task 2)
- Modify: `resources/views/admin/students/index.blade.php` (fill in the Actions cell)
- Modify: `tests/Feature/AdminStudentRegistryTest.php` (add delete tests)

**Interfaces:**
- Consumes: `admin.students.index` route/view from Task 2, `$student->delete()` from Task 1's `SoftDeletes`.
- Produces: named route `admin.students.destroy` (`DELETE /admin/students/{user}`).

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/AdminStudentRegistryTest.php` (inside the class, after the existing test methods):

```php
    public function test_admin_can_soft_delete_a_student(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'To Be Deleted']);

        $this->actingAs($this->admin)
            ->delete("/admin/students/{$student->id}")
            ->assertRedirect(route('admin.students.index'));

        $this->assertNotNull($student->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Student Account Deleted']);
    }

    public function test_destroy_returns_404_for_non_student_account(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($this->admin)
            ->delete("/admin/students/{$chair->id}")
            ->assertNotFound();

        $this->assertNull($chair->fresh()->deleted_at);
    }

    public function test_non_admin_cannot_delete_a_student(): void
    {
        $requester = User::factory()->create(['role' => 'student']);
        $target = User::factory()->create(['role' => 'student']);

        $this->actingAs($requester)
            ->delete("/admin/students/{$target->id}")
            ->assertForbidden();

        $this->assertNull($target->fresh()->deleted_at);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminStudentRegistryTest`
Expected: FAIL — route `admin.students.destroy` doesn't exist (404 on the redirect-expecting test; the 404 test coincidentally "passes" for the wrong reason, so check the diff carefully — the audit log assertion will fail either way).

- [ ] **Step 3: Add the destroy route**

In `routes/web.php`, insert immediately after the `admin.students.index` route added in Task 2 (right before `admin.audit`):

```php
    Route::delete('/admin/students/{user}', function (User $user) {
        abort_unless($user->role === 'student', 404);

        $name = $user->name;
        $loginId = $user->login_id;
        $user->delete();

        AuditLog::record('Student Account Deleted', 'Admin deleted student account for ' . $name . ' (Login ID: ' . $loginId . ').', 'User', $user->id);

        return redirect()->route('admin.students.index')->with('success', 'Student account for ' . $name . ' was deleted.');
    })->name('admin.students.destroy');
```

Note: Laravel's implicit route-model binding on `{user}` uses `User::findOrFail`, which — like every other Eloquent query — is automatically scoped by `SoftDeletes` to exclude already-deleted rows, so this route naturally 404s if called twice on the same student.

- [ ] **Step 4: Fill in the Actions cell in the view**

In `resources/views/admin/students/index.blade.php`, replace the placeholder comment in the Actions `<td>` (added in Task 2 Step 4) with:

```blade
                                    <td class="px-6 py-3 text-right">
                                        <form action="{{ route('admin.students.destroy', $student) }}" method="POST"
                                            onsubmit="return confirm('Delete {{ $student->name }}\'s account? This cannot be undone from this page.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs">
                                                <i class="fa-solid fa-trash mr-1"></i>Delete
                                            </button>
                                        </form>
                                    </td>
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=AdminStudentRegistryTest`
Expected: PASS (all 8 tests).

Run full suite: `php artisan test`
Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/admin/students/index.blade.php tests/Feature/AdminStudentRegistryTest.php
git commit -m "feat: add soft-delete action to Student Registry"
```

---

## Manual Verification (after Task 3)

Since Chrome browser automation has been available in recent sessions for this project, verify in-browser before considering this done:
1. Log in as `admin@aitsa.edu.ph` / `password123`, navigate to Student Registry from the sidebar.
2. Confirm the demo students (`2300410`, `2300411`, `2300420`) appear with correct Program/Year Level/Education Level columns.
3. Search by a partial name and by a partial login ID; confirm both filter correctly.
4. Filter by Program and by Year Level independently; confirm results narrow correctly; confirm "Clear" link resets.
5. Delete one non-critical test student (not one of the three demo accounts used for the consultation) and confirm it disappears from the list and the flash message shows.
6. Check both light and dark mode render cleanly with no console errors.
