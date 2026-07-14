# Multi-Department Clearance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Clearance routes to an admin-managed list of departments (not just Chair/Cashier/Registrar), each with its own officer account and Approve/Hold-with-remarks workflow that gates enrollment exactly like the existing three offices, closing the capstone paper's promise of routing to "all concerned departments."

**Architecture:** New `departments` and `clearance_items` tables sit alongside the existing `clearances` columns (Approach A — hybrid, no rewrite of chair/cashier/registrar). `Clearance::initializeFor()` centralizes clearance-row creation so `clearance_items` rows are lazily created — once, at clearance-creation time, one row per then-active department — never retroactively. `EnrollmentService::clearanceComplete()` gates on both the three legacy columns and every `clearance_items` row. A new `department_officer` role (data-driven via `users.department_id`, not a fixed role name) gets its own dashboard. Chair/Cashier/Registrar gain a parallel Hold-with-remarks action alongside their existing Approve.

**Tech Stack:** Laravel 12, PHPUnit (RefreshDatabase, SQLite `:memory:`), Blade + CDN Tailwind. No external services — this feature is pure first-party CRUD and workflow, unlike the PayMongo work.

## Global Constraints

- Design doc: `docs/superpowers/specs/2026-07-14-multi-department-clearance-design.md` — read it if anything below is ambiguous.
- Status values: `clearance_items.status` and `clearances.{chair,cashier,registrar}_status` are one of `Pending` / `Approved` / `Hold` (string columns, no DB enum, matching existing convention).
- Departments ship **empty** — no seeded rows. `is_active` gates lazy row creation only; existing `clearance_items` rows for a deactivated department are left untouched.
- `clearance_items` rows are created **only** at the moment a student's `clearances` row is first created (via `Clearance::initializeFor()`), one row per then-`is_active` department. Adding a department later never retroactively creates rows for existing clearances.
- Officer account passwords default to `password123` (existing dev convention, matches faculty/chair/cashier/registrar/admin seeded accounts).
- Audit actions used: `Department Created`, `Department Updated`, `Department Officer Created`, `Clearance Signed`, `Clearance Held`.
- Brand colors: brandNavy `#0B3C5D`, brandGreen `#1D7A46`, brandGold `#E2A700`, red-600 for Hold/danger states; dark-mode variants required on every new view (this codebase has no shared layout — each Blade page is self-contained, copy the sidebar/header markup from an existing sibling page in the same section).
- Notif dropdown (`partials/notif-script.blade.php`) renders via client-side `innerHTML`; any remark text flowing into a notif's `desc` goes through the existing `escNotif()` JS helper automatically — no new escaping code needed, just put the raw string in the PHP `$notifs` array as already done elsewhere in that file.
- Run tests with `php artisan test` (optionally `--filter Name`). Suite is currently at 152 passed / 506 assertions — note the exact count each task reports so drift is visible.
- TDD: write the test, watch it fail for the right reason, implement, watch it pass, commit at the end of each task.
- Routes in this codebase are plain closures directly in `routes/web.php` inside `Route::middleware('role:...')->group(...)` blocks (not controllers), except where an existing controller method is being edited. Follow that convention for every new route in this plan.

---

### Task 1: Schema — departments, clearance_items, drop dead columns

**Files:**
- Create: `database/migrations/2026_07_14_100001_create_departments_table.php`
- Create: `database/migrations/2026_07_14_100002_create_clearance_items_table.php`
- Create: `database/migrations/2026_07_14_100003_add_department_id_to_users_table.php`
- Create: `database/migrations/2026_07_14_100004_add_remarks_drop_library_clinic_from_clearances.php`
- Create: `app/Models/Department.php`
- Create: `app/Models/ClearanceItem.php`
- Create: `database/factories/DepartmentFactory.php`
- Modify: `app/Models/Clearance.php`
- Modify: `app/Models/User.php`
- Modify: `resources/views/admin/reports.blade.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `routes/web.php` (only the two dead-column keys stripped from the 3 `Clearance::firstOrCreate` call sites — the `initializeFor` swap is Task 2)
- Modify: `tests/Feature/PaymentReturnTest.php`, `tests/Feature/EnrollRegularTest.php`, `tests/Feature/EnrollmentBlockTest.php`, `tests/Feature/EnrollIrregularTest.php`, `tests/Feature/Api/EnrollmentApiTest.php`
- Test: `tests/Unit/DepartmentClearanceItemTest.php`

**Interfaces:**
- Consumes: existing `User`, `Clearance` models.
- Produces: `Department` model (`$fillable = ['name', 'code', 'is_active']`, cast `is_active` boolean, `officers(): HasMany` → User via `department_id`, `clearanceItems(): HasMany` → ClearanceItem); `ClearanceItem` model (`$fillable = ['clearance_id', 'department_id', 'status', 'remarks', 'signed_by', 'signed_at']`, cast `signed_at` datetime, `clearance()`/`department()`/`signedBy(): BelongsTo`); `Clearance::items(): HasMany` → ClearanceItem; `Clearance::allItemsApproved(): bool`; `User::department(): BelongsTo` + fillable `department_id`; `DepartmentFactory` (unique name + slug code, `is_active` true).

- [ ] **Step 1: Write the failing test**

`tests/Unit/DepartmentClearanceItemTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\ClearanceItem;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentClearanceItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_factory_and_officer_relation(): void
    {
        $department = Department::factory()->create(['name' => 'Library']);
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $this->assertTrue($officer->department->is($department));
        $this->assertTrue($department->officers()->whereKey($officer->id)->exists());
    }

    public function test_clearance_item_belongs_to_clearance_and_department(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $department = Department::factory()->create();

        $item = ClearanceItem::create([
            'clearance_id' => $clearance->id,
            'department_id' => $department->id,
            'status' => 'Pending',
        ]);

        $this->assertTrue($item->clearance->is($clearance));
        $this->assertTrue($item->department->is($department));
        $this->assertTrue($clearance->items->first()->is($item));
    }

    public function test_all_items_approved_true_when_no_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);

        $this->assertTrue($clearance->allItemsApproved());
    }

    public function test_all_items_approved_false_until_every_item_is_approved(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();
        $clearance->items()->create(['department_id' => $deptA->id, 'status' => 'Approved']);
        $clearance->items()->create(['department_id' => $deptB->id, 'status' => 'Pending']);

        $this->assertFalse($clearance->fresh('items')->allItemsApproved());

        $clearance->items()->where('department_id', $deptB->id)->update(['status' => 'Approved']);

        $this->assertTrue($clearance->fresh('items')->allItemsApproved());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DepartmentClearanceItemTest`
Expected: FAIL — `Class "App\Models\Department" not found`.

- [ ] **Step 3: Create migrations, models, factory**

`database/migrations/2026_07_14_100001_create_departments_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
```

`database/migrations/2026_07_14_100002_create_clearance_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('Pending');
            $table->string('remarks', 500)->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['clearance_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_items');
    }
};
```

`database/migrations/2026_07_14_100003_add_department_id_to_users_table.php`:

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
            $table->foreignId('department_id')->nullable()->after('discount_type_id')
                ->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
```

`database/migrations/2026_07_14_100004_add_remarks_drop_library_clinic_from_clearances.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->string('remarks', 500)->nullable()->after('registrar_status');
            $table->dropColumn(['library_status', 'clinic_status']);
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->string('library_status')->default('Approved');
            $table->string('clinic_status')->default('Approved');
        });
    }
};
```

`app/Models/Department.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function officers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clearanceItems(): HasMany
    {
        return $this->hasMany(ClearanceItem::class);
    }
}
```

`app/Models/ClearanceItem.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceItem extends Model
{
    use HasFactory;

    protected $fillable = ['clearance_id', 'department_id', 'status', 'remarks', 'signed_by', 'signed_at'];

    protected $casts = ['signed_at' => 'datetime'];

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
```

`database/factories/DepartmentFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company() . ' Office';

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
```

In `app/Models/Clearance.php`, replace the whole file with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clearance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'admission_status',
        'chair_status',
        'cashier_status',
        'registrar_status',
        'remarks',
    ];

    /**
     * Connect back to the student user.
     * Establishes the inverse 1-to-1 relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClearanceItem::class);
    }

    public function allItemsApproved(): bool
    {
        return $this->items->isEmpty() || $this->items->every(fn (ClearanceItem $item) => $item->status === 'Approved');
    }
}
```

In `app/Models/User.php`:
1. In `$fillable`, directly after the line `'discount_type_id',` add a line `'department_id',`.
2. Directly after the `discountType()` method, add:

```php
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
```

In `resources/views/admin/reports.blade.php`, remove the two dead columns. Replace:

```blade
                                <th class="py-3 px-4 text-center">Registrar</th>
                                <th class="py-3 px-4 text-center">Library</th>
                                <th class="py-3 px-4 text-center">Clinic</th>
                                <th class="py-3 px-4 text-center">Overall</th>
```

with:

```blade
                                <th class="py-3 px-4 text-center">Registrar</th>
                                <th class="py-3 px-4 text-center">Overall</th>
```

Replace:

```blade
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->registrar_status])</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->library_status])</td>
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->clinic_status])</td>
```

with:

```blade
                                <td class="py-2.5 px-4 text-center">@include('partials.status-badge', ['status' => $c->registrar_status])</td>
```

Replace `<td colspan="10" class="py-10 text-center text-sm text-brandNavy/40 dark:text-slate-500">No student records found.</td>` with `<td colspan="8" class="py-10 text-center text-sm text-brandNavy/40 dark:text-slate-500">No student records found.</td>`.

Replace:

```js
    const headers = ['#', 'Student Name', 'Student No.', 'Email', 'Chair', 'Cashier', 'Registrar', 'Library', 'Clinic', 'Overall'];
```

with:

```js
    const headers = ['#', 'Student Name', 'Student No.', 'Email', 'Chair', 'Cashier', 'Registrar', 'Overall'];
```

Replace:

```js
            cells[5]?.textContent.trim() ?? '',
            cells[6]?.textContent.trim() ?? '',
            cells[7]?.textContent.trim() ?? '',
            cells[8]?.textContent.trim() ?? '',
        ];
```

with:

```js
            cells[5]?.textContent.trim() ?? '',
            cells[6]?.textContent.trim() ?? '',
        ];
```

In `database/seeders/DatabaseSeeder.php`, replace:

```php
            Clearance::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'chair_status' => 'Approved',
                    'cashier_status' => 'Pending',
                    'registrar_status' => 'Pending',
                    'library_status' => 'Approved',
                    'clinic_status' => 'Approved',
                ]
            );
```

with:

```php
            Clearance::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'chair_status' => 'Approved',
                    'cashier_status' => 'Pending',
                    'registrar_status' => 'Pending',
                ]
            );
```

Replace:

```php
        Clearance::firstOrCreate(
            ['user_id' => $regular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
             'library_status' => 'Approved', 'clinic_status' => 'Approved']
        );
```

with:

```php
        Clearance::firstOrCreate(
            ['user_id' => $regular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']
        );
```

Replace:

```php
        Clearance::firstOrCreate(
            ['user_id' => $irregular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
             'library_status' => 'Approved', 'clinic_status' => 'Approved']
        );
```

with:

```php
        Clearance::firstOrCreate(
            ['user_id' => $irregular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']
        );
```

In `routes/web.php`, there are three occurrences of a `library_status`/`clinic_status` pair to strip. First, inside the `/dashboard` route closure, replace:

```php
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
```

with:

```php
        $clearance = Clearance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'admission_status'   => 'Pending',
                'chair_status'       => 'Pending',
                'cashier_status'     => 'Pending',
                'registrar_status'   => 'Pending',
            ]
        );

        return view('dashboard', compact('clearance'));
    })->name('dashboard');
```

Second, inside the `/clearance` route closure, replace:

```php
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
```

with:

```php
        $clearance = Clearance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'admission_status'   => 'Pending',
                'chair_status'       => 'Pending',
                'cashier_status'     => 'Pending',
                'registrar_status'   => 'Pending',
            ]
        );

        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
```

Third, inside the `admin.students.store` handler, replace:

```php
        Clearance::firstOrCreate(
            ['user_id' => $student->id],
            ['admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending',
             'registrar_status' => 'Pending', 'library_status' => 'Approved', 'clinic_status' => 'Approved']
        );
```

with:

```php
        Clearance::firstOrCreate(
            ['user_id' => $student->id],
            ['admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending',
             'registrar_status' => 'Pending']
        );
```

In each of `tests/Feature/EnrollRegularTest.php`, `tests/Feature/EnrollmentBlockTest.php`, `tests/Feature/EnrollIrregularTest.php`, `tests/Feature/Api/EnrollmentApiTest.php`, delete the line:

```php
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
```

In `tests/Feature/PaymentReturnTest.php`, delete the line:

```php
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
```

- [ ] **Step 4: Run migrations and tests to verify they pass**

Run: `php artisan migrate:fresh`
Expected: all migrations run clean, no errors.

Run: `php artisan test --filter DepartmentClearanceItemTest`
Expected: PASS (4 tests). Then `php artisan test` — full suite green (156 passed).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_14_100001_create_departments_table.php database/migrations/2026_07_14_100002_create_clearance_items_table.php database/migrations/2026_07_14_100003_add_department_id_to_users_table.php database/migrations/2026_07_14_100004_add_remarks_drop_library_clinic_from_clearances.php app/Models/Department.php app/Models/ClearanceItem.php database/factories/DepartmentFactory.php app/Models/Clearance.php app/Models/User.php resources/views/admin/reports.blade.php database/seeders/DatabaseSeeder.php routes/web.php tests/Feature/PaymentReturnTest.php tests/Feature/EnrollRegularTest.php tests/Feature/EnrollmentBlockTest.php tests/Feature/EnrollIrregularTest.php tests/Feature/Api/EnrollmentApiTest.php tests/Unit/DepartmentClearanceItemTest.php
git commit -m "feat: departments and clearance_items schema, drop dead library/clinic columns"
```

---

### Task 2: Lazy clearance_items creation + enrollment gate

**Files:**
- Modify: `app/Models/Clearance.php`
- Modify: `app/Services/EnrollmentService.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/ClearanceInitializeForTest.php`
- Test: `tests/Unit/EnrollmentServiceClearanceGateTest.php`

**Interfaces:**
- Consumes: Task 1's `Department`, `ClearanceItem`, `Clearance::items()`/`allItemsApproved()`.
- Produces: `Clearance::initializeFor(int $userId, array $attributes = []): self` — returns the existing clearance for `$userId` unchanged if one exists (no items created), otherwise creates it with `$attributes` and one `Pending` `clearance_items` row per currently-`is_active` department. `EnrollmentService::clearanceComplete()` now also requires `allItemsApproved()`.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/ClearanceInitializeForTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceInitializeForTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_clearance_item_for_every_active_department(): void
    {
        $active = Department::factory()->create(['is_active' => true]);
        $inactive = Department::factory()->create(['is_active' => false]);
        $student = User::factory()->create(['role' => 'student']);

        $clearance = Clearance::initializeFor($student->id, ['chair_status' => 'Pending']);

        $this->assertSame(1, $clearance->items()->count());
        $this->assertTrue($clearance->items()->where('department_id', $active->id)->exists());
        $this->assertFalse($clearance->items()->where('department_id', $inactive->id)->exists());
    }

    public function test_returns_existing_clearance_without_creating_new_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $first = Clearance::initializeFor($student->id);

        Department::factory()->create(['is_active' => true]);
        $second = Clearance::initializeFor($student->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(0, $second->items()->count());
    }
}
```

`tests/Unit/EnrollmentServiceClearanceGateTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentServiceClearanceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_while_a_clearance_item_is_pending_or_on_hold(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);
        $department = Department::factory()->create();
        $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);

        $service = app(EnrollmentService::class);
        $this->assertFalse($service->clearanceComplete($student));

        $item->update(['status' => 'Hold']);
        $this->assertFalse($service->clearanceComplete($student->fresh()));

        $item->update(['status' => 'Approved']);
        $this->assertTrue($service->clearanceComplete($student->fresh()));
    }

    public function test_unaffected_when_student_has_zero_clearance_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $this->assertTrue(app(EnrollmentService::class)->clearanceComplete($student));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter ClearanceInitializeForTest`
Expected: FAIL — `Call to undefined method App\Models\Clearance::initializeFor()`.

Run: `php artisan test --filter EnrollmentServiceClearanceGateTest`
Expected: FAIL — the Pending-item case incorrectly returns `true` (gate doesn't check items yet).

- [ ] **Step 3: Implement**

In `app/Models/Clearance.php`, directly after the `allItemsApproved()` method, add:

```php

    public static function initializeFor(int $userId, array $attributes = []): self
    {
        $existing = static::where('user_id', $userId)->first();
        if ($existing) {
            return $existing;
        }

        $clearance = static::create(array_merge(['user_id' => $userId], $attributes));

        foreach (Department::where('is_active', true)->get() as $department) {
            $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);
        }

        return $clearance;
    }
```

In `app/Services/EnrollmentService.php`, replace:

```php
    public function clearanceComplete(User $user): bool
    {
        $clearance = Clearance::where('user_id', $user->id)->first();

        return $clearance !== null
            && $clearance->chair_status === 'Approved'
            && $clearance->cashier_status === 'Approved'
            && $clearance->registrar_status === 'Approved';
    }
```

with:

```php
    public function clearanceComplete(User $user): bool
    {
        $clearance = Clearance::with('items')->where('user_id', $user->id)->first();

        return $clearance !== null
            && $clearance->chair_status === 'Approved'
            && $clearance->cashier_status === 'Approved'
            && $clearance->registrar_status === 'Approved'
            && $clearance->allItemsApproved();
    }
```

In `routes/web.php`, replace (inside the `/dashboard` route closure):

```php
        $clearance = Clearance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'admission_status'   => 'Pending',
                'chair_status'       => 'Pending',
                'cashier_status'     => 'Pending',
                'registrar_status'   => 'Pending',
            ]
        );

        return view('dashboard', compact('clearance'));
    })->name('dashboard');
```

with:

```php
        $clearance = Clearance::initializeFor($user->id, [
            'admission_status'   => 'Pending',
            'chair_status'       => 'Pending',
            'cashier_status'     => 'Pending',
            'registrar_status'   => 'Pending',
        ]);

        return view('dashboard', compact('clearance'));
    })->name('dashboard');
```

Replace (inside the `/clearance` route closure):

```php
        $clearance = Clearance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'admission_status'   => 'Pending',
                'chair_status'       => 'Pending',
                'cashier_status'     => 'Pending',
                'registrar_status'   => 'Pending',
            ]
        );

        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
```

with:

```php
        $clearance = Clearance::initializeFor($user->id, [
            'admission_status'   => 'Pending',
            'chair_status'       => 'Pending',
            'cashier_status'     => 'Pending',
            'registrar_status'   => 'Pending',
        ]);

        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
```

Replace (inside the `admin.students.store` handler):

```php
        Clearance::firstOrCreate(
            ['user_id' => $student->id],
            ['admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending',
             'registrar_status' => 'Pending']
        );
```

with:

```php
        Clearance::initializeFor($student->id, [
            'admission_status' => 'Approved', 'chair_status' => 'Pending', 'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter ClearanceInitializeForTest`
Expected: PASS (2 tests).

Run: `php artisan test --filter EnrollmentServiceClearanceGateTest`
Expected: PASS (2 tests). Then `php artisan test` — full suite green (160 passed).

- [ ] **Step 5: Commit**

```bash
git add app/Models/Clearance.php app/Services/EnrollmentService.php routes/web.php tests/Unit/ClearanceInitializeForTest.php tests/Unit/EnrollmentServiceClearanceGateTest.php
git commit -m "feat: lazy clearance_items creation and enrollment gate"
```

---

### Task 3: Admin Departments panel

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/admin/departments.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php`, `resources/views/admin/create-student.blade.php`, `resources/views/admin/curriculum.blade.php`, `resources/views/admin/audit.blade.php`, `resources/views/admin/reports.blade.php` (nav link)
- Test: `tests/Feature/AdminDepartmentsTest.php`

**Interfaces:**
- Consumes: Task 1's `Department` model.
- Produces: routes `admin.departments` (GET), `admin.departments.store` (POST), `admin.departments.toggle` (POST), `admin.departments.officers.store` (POST). View data: `$departments` (with `officers_count`).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/AdminDepartmentsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_a_department(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/departments', ['name' => 'Library'])
            ->assertRedirect(route('admin.departments'));

        $this->assertDatabaseHas('departments', ['name' => 'Library', 'code' => 'library', 'is_active' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Department Created']);
    }

    public function test_duplicate_department_name_is_rejected(): void
    {
        Department::factory()->create(['name' => 'Library']);

        $this->actingAs($this->admin)->from('/admin/departments')
            ->post('/admin/departments', ['name' => 'Library'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_toggle_department_active_state(): void
    {
        $department = Department::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->post("/admin/departments/{$department->id}/toggle")
            ->assertRedirect(route('admin.departments'));

        $this->assertFalse((bool) $department->fresh()->is_active);
    }

    public function test_admin_can_create_a_department_officer_account(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->admin)
            ->post("/admin/departments/{$department->id}/officers", ['name' => 'Lib Officer', 'login_id' => 'lib01'])
            ->assertRedirect(route('admin.departments'));

        $officer = User::where('login_id', 'lib01')->first();
        $this->assertNotNull($officer);
        $this->assertSame('department_officer', $officer->role);
        $this->assertSame($department->id, $officer->department_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Department Officer Created']);
    }

    public function test_cannot_create_officer_for_inactive_department(): void
    {
        $department = Department::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin)
            ->post("/admin/departments/{$department->id}/officers", ['name' => 'X', 'login_id' => 'x01'])
            ->assertRedirect(route('admin.departments'));

        $this->assertNull(User::where('login_id', 'x01')->first());
    }

    public function test_non_admin_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/departments')->assertForbidden();
        $this->actingAs($student)->post('/admin/departments', ['name' => 'X'])->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter AdminDepartmentsTest`
Expected: FAIL — `GET /admin/departments` 404.

- [ ] **Step 3: Add imports and routes**

In `routes/web.php`, replace the imports block:

```php
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\AuthController;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\DiscountType;
use App\Models\DocumentSubmission;
```

with:

```php
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\AuthController;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\ClearanceItem;
use App\Models\Department;
use App\Models\DiscountType;
use App\Models\DocumentSubmission;
```

Replace:

```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
```

with:

```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
```

Inside the `role:admin` group, directly after the line

```php
    Route::get('/admin/curriculum', function () {
        return view('admin.curriculum');
    })->name('admin.curriculum');
```

add:

```php

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
```

- [ ] **Step 4: Create the view and nav links**

`resources/views/admin/departments.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Departments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
            } } }
        }
    </script>
    <script>
        if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); }
        function updateThemeIcon() {
            const icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
                <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA HQ</h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Core Control</p>

                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>System Overview</span>
                </a>
                <a href="{{ route('admin.students.create') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Create Student Account</span>
                </a>
                <a href="{{ route('admin.curriculum') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Curriculum</span>
                </a>
                <a href="{{ route('admin.departments') }}" class="flex items-center px-3 py-2.5 border-l-2 border-brandGreen text-brandGreen dark:text-emerald-400 font-bold text-sm transition-colors">
                    <span>Departments</span>
                </a>
                <a href="{{ route('admin.audit') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Audit Trail</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium text-sm transition-colors">
                    <span>Reports</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Departments</h2>
                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel'   => 'Root Access Mode',
                        'roleClass'   => 'text-red-500 uppercase tracking-wider',
                        'avatarClass' => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

                @if(session('success'))
                    <div class="p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">{{ $errors->first() }}</div>
                @endif

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl p-6">
                    <h3 class="text-sm font-bold text-brandNavy dark:text-white mb-4">Add Department</h3>
                    <form action="{{ route('admin.departments.store') }}" method="POST" class="flex gap-3">
                        @csrf
                        <input type="text" name="name" required maxlength="100" placeholder="e.g. Library"
                               class="flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40">
                        <button type="submit" class="px-5 py-2.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold text-xs rounded uppercase tracking-wider">Add</button>
                    </form>
                </div>

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="p-4">Department</th>
                                    <th class="p-4">Officers</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4">Add Officer</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($departments as $department)
                                    <tr>
                                        <td class="p-4 font-bold text-brandNavy dark:text-white">{{ $department->name }}</td>
                                        <td class="p-4 text-brandNavy/60 dark:text-slate-400">{{ $department->officers_count }}</td>
                                        <td class="p-4">
                                            @if($department->is_active)
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Active</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandNavy/10 text-brandNavy/50 dark:text-slate-500 border border-brandNavy/10 rounded">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="p-4">
                                            <form action="{{ route('admin.departments.officers.store', $department) }}" method="POST" class="flex gap-2">
                                                @csrf
                                                <input type="text" name="name" required maxlength="100" placeholder="Officer name" class="w-32 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                <input type="text" name="login_id" required maxlength="50" placeholder="Login ID" class="w-24 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                <button type="submit" class="px-3 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-bold rounded">Create</button>
                                            </form>
                                        </td>
                                        <td class="p-4 text-right">
                                            <form action="{{ route('admin.departments.toggle', $department) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/10 text-brandNavy dark:text-slate-300 text-[11px] font-bold rounded border border-brandNavy/10 dark:border-slate-700">
                                                    {{ $department->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">No departments yet. Add one above.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
@include('partials.notif-script')
</body>
</html>
```

In each of `resources/views/admin/dashboard.blade.php`, `resources/views/admin/create-student.blade.php`, `resources/views/admin/curriculum.blade.php`, `resources/views/admin/audit.blade.php`, `resources/views/admin/reports.blade.php`, find the sidebar `<a>` link to `admin.curriculum` (it looks like `<a href="{{ route('admin.curriculum') }}" class="...">\n    <span>Curriculum</span>\n</a>`, possibly with an active-state ternary in the class attribute) and add directly after its closing `</a>`:

```blade
                <a href="{{ route('admin.departments') }}" class="flex items-center px-3 py-2.5 border-l-2 border-transparent {{ Route::is('admin.departments') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">
                    <span>Departments</span>
                </a>
```

(Match each file's existing indentation and active-state class pattern rather than pasting verbatim — copy the same ternary style already used by that file's `admin.curriculum` link.)

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter AdminDepartmentsTest`
Expected: PASS (6 tests). Then `php artisan test` — full suite green (166 passed).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/admin/departments.blade.php resources/views/admin/dashboard.blade.php resources/views/admin/create-student.blade.php resources/views/admin/curriculum.blade.php resources/views/admin/audit.blade.php resources/views/admin/reports.blade.php tests/Feature/AdminDepartmentsTest.php
git commit -m "feat: admin departments panel and officer account creation"
```

---

### Task 4: Officer dashboard

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/department/dashboard.blade.php`
- Modify: `app/Http/Controllers/AuthController.php`
- Modify: `resources/views/partials/notif-script.blade.php`
- Test: `tests/Feature/DepartmentOfficerDashboardTest.php`

**Interfaces:**
- Consumes: Task 1's `ClearanceItem`, `Department`.
- Produces: routes `department.dashboard` (GET), `department.items.approve` (POST), `department.items.hold` (POST). `AuthController::handleRoleRedirection` routes `department_officer` to `department.dashboard`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/DepartmentOfficerDashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentOfficerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_sees_only_their_own_department_queue(): void
    {
        $deptA = Department::factory()->create(['name' => 'Library']);
        $deptB = Department::factory()->create(['name' => 'Clinic']);
        $officerA = User::factory()->create(['role' => 'department_officer', 'department_id' => $deptA->id]);

        $studentA = User::factory()->create(['role' => 'student', 'name' => 'Student A']);
        $clearanceA = Clearance::create(['user_id' => $studentA->id]);
        $clearanceA->items()->create(['department_id' => $deptA->id, 'status' => 'Pending']);

        $studentB = User::factory()->create(['role' => 'student', 'name' => 'Student B']);
        $clearanceB = Clearance::create(['user_id' => $studentB->id]);
        $clearanceB->items()->create(['department_id' => $deptB->id, 'status' => 'Pending']);

        $response = $this->actingAs($officerA)->get('/department/dashboard');

        $response->assertOk()->assertSee('Student A')->assertDontSee('Student B');
    }

    public function test_officer_can_approve_an_item(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending', 'remarks' => 'old note']);

        $this->actingAs($officer)
            ->post("/department/items/{$item->id}/approve")
            ->assertRedirect(route('department.dashboard'));

        $item->refresh();
        $this->assertSame('Approved', $item->status);
        $this->assertNull($item->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clearance Signed']);
    }

    public function test_officer_hold_requires_remarks(): void
    {
        $department = Department::factory()->create();
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);

        $this->actingAs($officer)->from('/department/dashboard')
            ->post("/department/items/{$item->id}/hold", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($officer)
            ->post("/department/items/{$item->id}/hold", ['remarks' => 'Missing borrowed book.'])
            ->assertRedirect(route('department.dashboard'));

        $item->refresh();
        $this->assertSame('Hold', $item->status);
        $this->assertSame('Missing borrowed book.', $item->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clearance Held']);
    }

    public function test_officer_cannot_act_on_another_departments_item(): void
    {
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();
        $officerA = User::factory()->create(['role' => 'department_officer', 'department_id' => $deptA->id]);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create(['user_id' => $student->id]);
        $item = $clearance->items()->create(['department_id' => $deptB->id, 'status' => 'Pending']);

        $this->actingAs($officerA)->post("/department/items/{$item->id}/approve")->assertForbidden();

        $this->assertSame('Pending', $item->fresh()->status);
    }

    public function test_non_officer_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/department/dashboard')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DepartmentOfficerDashboardTest`
Expected: FAIL — `GET /department/dashboard` 404.

- [ ] **Step 3: Add routes**

In `routes/web.php`, directly after the line

```php
    }); // end role:faculty
```

add:

```php

    // --- DEPARTMENT OFFICER QUEUE ---
    Route::middleware('role:department_officer')->group(function () {
    Route::get('/department/dashboard', function () {
        $officer = Auth::user();
        $items = ClearanceItem::where('department_id', $officer->department_id)
            ->with('clearance.user')
            ->get();

        return view('department.dashboard', compact('items'));
    })->name('department.dashboard');

    Route::post('/department/items/{item}/approve', function (ClearanceItem $item) {
        abort_unless($item->department_id === Auth::user()->department_id, 403);

        $item->update(['status' => 'Approved', 'remarks' => null, 'signed_by' => Auth::id(), 'signed_at' => now()]);
        AuditLog::record('Clearance Signed', Auth::user()->name . ' approved ' . $item->department->name . ' clearance for ' . ($item->clearance->user->name ?? 'ID ' . $item->clearance->user_id) . '.', 'ClearanceItem', $item->id);

        return redirect()->route('department.dashboard')->with('success', 'Clearance item approved.');
    })->name('department.items.approve');

    Route::post('/department/items/{item}/hold', function (Request $request, ClearanceItem $item) {
        abort_unless($item->department_id === Auth::user()->department_id, 403);
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        $item->update(['status' => 'Hold', 'remarks' => $data['remarks'], 'signed_by' => Auth::id(), 'signed_at' => now()]);
        AuditLog::record('Clearance Held', Auth::user()->name . ' held ' . $item->department->name . ' clearance for ' . ($item->clearance->user->name ?? 'ID ' . $item->clearance->user_id) . ': ' . $data['remarks'], 'ClearanceItem', $item->id);

        return redirect()->route('department.dashboard')->with('success', 'Clearance item held with remarks.');
    })->name('department.items.hold');
    }); // end role:department_officer
```

- [ ] **Step 4: Create the view, role redirection, and notif branch**

`resources/views/department/dashboard.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Department Clearance Queue</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
            } } }
        }
    </script>
    <script>
        if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); }
        function updateThemeIcon() {
            const icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">
        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
                <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
            </div>
            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">{{ Auth::user()->department->name ?? 'Department' }}</p>
                <a href="{{ route('department.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 border-brandGreen text-brandGreen dark:text-emerald-400 font-bold text-sm">
                    <span>Clearance Queue</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">{{ Auth::user()->department->name ?? 'Department' }} Clearance Queue</h2>
                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Department Officer'])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">
                @if(session('success'))
                    <div class="p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">{{ $errors->first() }}</div>
                @endif

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="p-4">Student</th>
                                    <th class="p-4">Student No.</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($items as $item)
                                    <tr>
                                        <td class="p-4 font-bold text-brandNavy dark:text-white">{{ $item->clearance->user->name ?? 'Unknown' }}</td>
                                        <td class="p-4 font-mono text-brandNavy/60 dark:text-slate-400">{{ $item->clearance->user->login_id ?? 'N/A' }}</td>
                                        <td class="p-4">
                                            @if($item->status === 'Approved')
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Approved</span>
                                            @elseif($item->status === 'Hold')
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-600 border border-red-500/20 rounded">Hold: {{ $item->remarks }}</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGold/10 text-brandGold border border-brandGold/20 rounded">Pending</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-right">
                                            @if($item->status === 'Approved')
                                                <button disabled class="px-3 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 rounded text-[11px] font-bold border border-brandNavy/8 dark:border-slate-700 cursor-not-allowed">Approved</button>
                                            @else
                                                <div class="flex items-center justify-end gap-2">
                                                    <form action="{{ route('department.items.approve', $item) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-brandGreen hover:bg-emerald-600 text-white text-[11px] font-bold rounded">Approve</button>
                                                    </form>
                                                    <form action="{{ route('department.items.hold', $item) }}" method="POST" class="flex items-center gap-2">
                                                        @csrf
                                                        <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold" class="w-40 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                        <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold rounded">Hold</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">No students in your queue.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
@include('partials.notif-script')
</body>
</html>
```

In `app/Http/Controllers/AuthController.php`, replace:

```php
            case 'cashier':
                $targetRoute = 'cashier.dashboard';
                break;
```

with:

```php
            case 'cashier':
                $targetRoute = 'cashier.dashboard';
                break;
            case 'department_officer':
                $targetRoute = 'department.dashboard';
                break;
```

In `resources/views/partials/notif-script.blade.php`, replace:

```php
    // ── FACULTY ───────────────────────────────────────────────────────────────
    } elseif ($authRole === 'faculty') {
        $loadCount = \App\Models\Section::where('faculty_id', $authId)
            ->where('school_year', \App\Models\Setting::get('school_year', '2026-2027'))->count();

        if ($loadCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-chalkboard-user', 'color' => '#0B3C5D', 'title' => 'Teaching Load', 'desc' => 'You are loaded with ' . $loadCount . ' section(s) this A.Y.', 'time' => 'Summary'];
        } else {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-chalkboard-user', 'color' => '#E2A700', 'title' => 'No Teaching Load', 'desc' => 'No teaching load assigned yet.', 'time' => 'System'];
        }
    }
```

with:

```php
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
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter DepartmentOfficerDashboardTest`
Expected: PASS (5 tests). Then `php artisan test` — full suite green (171 passed).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/department/dashboard.blade.php app/Http/Controllers/AuthController.php resources/views/partials/notif-script.blade.php tests/Feature/DepartmentOfficerDashboardTest.php
git commit -m "feat: department officer clearance queue dashboard"
```

---

### Task 5: Student clearance page

**Files:**
- Modify: `routes/web.php`
- Modify: `resources/views/clearance.blade.php`
- Modify: `resources/views/partials/notif-script.blade.php`
- Test: `tests/Feature/DepartmentClearancePageTest.php`

**Interfaces:**
- Consumes: Task 1's `Clearance::items()`/`allItemsApproved()`, Task 2's `Clearance::initializeFor()`.
- Produces: `/clearance` page renders one card per `clearance_items` row; `$isCleared` now also requires `allItemsApproved()`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/DepartmentClearancePageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentClearancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearance_page_shows_a_card_per_active_department(): void
    {
        Department::factory()->create(['name' => 'Library']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('Library');

        $this->assertDatabaseHas('clearance_items', [
            'department_id' => Department::where('name', 'Library')->value('id'),
            'status' => 'Pending',
        ]);
    }

    public function test_held_item_shows_remarks_on_the_page(): void
    {
        $department = Department::factory()->create(['name' => 'Clinic']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        $clearance = $student->clearance;
        $clearance->items()->where('department_id', $department->id)->update(['status' => 'Hold', 'remarks' => 'Visit the clinic for a checkup.']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('Visit the clinic for a checkup.');
    }

    public function test_department_added_after_clearance_exists_does_not_retroactively_appear(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/clearance');

        Department::factory()->create(['name' => 'Guidance Office']);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertDontSee('Guidance Office');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DepartmentClearancePageTest`
Expected: FAIL — `assertSee('Library')` fails (page has no department loop yet).

- [ ] **Step 3: Implement**

In `routes/web.php`, inside the `/clearance` route closure, replace:

```php
        $clearance = Clearance::initializeFor($user->id, [
            'admission_status'   => 'Pending',
            'chair_status'       => 'Pending',
            'cashier_status'     => 'Pending',
            'registrar_status'   => 'Pending',
        ]);

        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
```

with:

```php
        $clearance = Clearance::initializeFor($user->id, [
            'admission_status'   => 'Pending',
            'chair_status'       => 'Pending',
            'cashier_status'     => 'Pending',
            'registrar_status'   => 'Pending',
        ])->load('items.department');

        $submissions = DocumentSubmission::where('user_id', $user->id)->latest()->get();
```

In `resources/views/clearance.blade.php`, replace the `@php` block:

```blade
            @php
                $isCleared = isset($clearance) && (
                    $clearance->cashier_status === 'Approved' &&
                    $clearance->registrar_status === 'Approved' &&
                    $clearance->admission_status === 'Approved' &&
                    $clearance->chair_status === 'Approved'
                );
```

with:

```blade
            @php
                $isCleared = isset($clearance) && (
                    $clearance->cashier_status === 'Approved' &&
                    $clearance->registrar_status === 'Approved' &&
                    $clearance->admission_status === 'Approved' &&
                    $clearance->chair_status === 'Approved' &&
                    $clearance->allItemsApproved()
                );
```

Replace:

```blade
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-circle-check text-brandGreen mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Library — No pending borrowed items on record.</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i id="checkIconRegistrar" class="fa-solid @if($registrarCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-red-500 @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Registrar — On-hold administrative document verification.</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i id="checkIconChair" class="fa-solid @if($chairCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-brandGold @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Department Head — Curriculum evaluation sign-off.</p>
                    </div>
                </div>
```

with:

```blade
                    <div class="flex items-start space-x-2">
                        <i id="checkIconRegistrar" class="fa-solid @if($registrarCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-red-500 @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Registrar — On-hold administrative document verification.</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i id="checkIconChair" class="fa-solid @if($chairCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-brandGold @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Department Head — Curriculum evaluation sign-off.</p>
                    </div>
                    @foreach($clearance->items as $item)
                        <div class="flex items-start space-x-2">
                            <i class="fa-solid @if($item->status === 'Approved') fa-circle-check text-brandGreen @elseif($item->status === 'Hold') fa-circle-xmark text-red-500 @else fa-circle-xmark text-brandGold @endif mt-0.5"></i>
                            <p class="text-brandNavy/70 dark:text-slate-400">
                                {{ $item->department->name }} —
                                @if($item->status === 'Approved') Cleared.
                                @elseif($item->status === 'Hold') On hold: {{ $item->remarks }}
                                @else Pending review.
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
```

In `resources/views/partials/notif-script.blade.php`, replace:

```php
            $allCleared = $cl->chair_status === 'Approved'
                       && $cl->registrar_status === 'Approved'
                       && $cl->cashier_status === 'Approved';

            if ($allCleared) {
```

with:

```php
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
```

Also replace, a few lines above (in the same student branch), the line that fetches `$cl`:

```php
        $cl = $clearance ?? Clearance::where('user_id', $authId)->first();
```

with:

```php
        $cl = ($clearance ?? Clearance::where('user_id', $authId)->first())?->loadMissing('items.department');
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter DepartmentClearancePageTest`
Expected: PASS (3 tests). Then `php artisan test` — full suite green (174 passed).

- [ ] **Step 5: Commit**

```bash
git add routes/web.php resources/views/clearance.blade.php resources/views/partials/notif-script.blade.php tests/Feature/DepartmentClearancePageTest.php
git commit -m "feat: render dynamic department clearance items on the student page"
```

---

### Task 6: Hold-with-remarks for Chair, Cashier, Registrar

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/AuthController.php`
- Modify: `resources/views/approver/dashboard.blade.php`
- Modify: `resources/views/registrar/dashboard.blade.php`
- Modify: `resources/views/cashier/dashboard.blade.php`
- Modify: `resources/views/clearance.blade.php`
- Modify: `resources/views/partials/notif-script.blade.php`
- Test: `tests/Feature/ClearanceHoldTest.php`

**Interfaces:**
- Consumes: `clearances.remarks` (Task 1).
- Produces: routes `approver.hold`, `registrar.hold`, `cashier.hold` (POST, each `remarks: required|string|max:500`); existing `approver.sign`/`registrar.sign`/`AuthController::approveClearance` now clear `remarks` on Approve.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ClearanceHoldTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceHoldTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearance(): Clearance
    {
        $student = User::factory()->create(['role' => 'student']);

        return Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);
    }

    public function test_chair_hold_requires_remarks_and_sets_hold_status(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $clearance = $this->makeClearance();

        $this->actingAs($chair)->from('/approver/dashboard')
            ->post("/approver/hold/{$clearance->id}", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($chair)
            ->post("/approver/hold/{$clearance->id}", ['remarks' => 'Missing lab clearance.'])
            ->assertRedirect(route('approver.dashboard'));

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->chair_status);
        $this->assertSame('Missing lab clearance.', $clearance->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clearance Held']);
    }

    public function test_chair_approve_clears_remarks(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $clearance = $this->makeClearance();
        $clearance->update(['chair_status' => 'Hold', 'remarks' => 'Old remark']);

        $this->actingAs($chair)->post("/approver/sign/{$clearance->id}");

        $clearance->refresh();
        $this->assertSame('Approved', $clearance->chair_status);
        $this->assertNull($clearance->remarks);
    }

    public function test_registrar_hold_requires_remarks(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $clearance = $this->makeClearance();

        $this->actingAs($registrar)->from('/registrar/dashboard')
            ->post("/registrar/hold/{$clearance->id}", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($registrar)
            ->post("/registrar/hold/{$clearance->id}", ['remarks' => 'Missing Form 137.'])
            ->assertRedirect(route('registrar.dashboard'));

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->registrar_status);
        $this->assertSame('Missing Form 137.', $clearance->remarks);
    }

    public function test_cashier_hold_requires_remarks(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $clearance = $this->makeClearance();

        $this->actingAs($cashier)->from('/cashier/dashboard')
            ->post('/cashier/hold', ['user_id' => $clearance->user_id])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($cashier)
            ->post('/cashier/hold', ['user_id' => $clearance->user_id, 'remarks' => 'Balance dispute.'])
            ->assertRedirect();

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->cashier_status);
        $this->assertSame('Balance dispute.', $clearance->remarks);
    }

    public function test_cashier_approve_clears_remarks(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $clearance = $this->makeClearance();
        $clearance->update(['cashier_status' => 'Hold', 'remarks' => 'Old remark']);

        $this->actingAs($cashier)->post('/cashier/approve', [
            'user_id' => $clearance->user_id, 'reference_no' => 'TXN-1', 'amount' => '1000',
        ]);

        $clearance->refresh();
        $this->assertSame('Approved', $clearance->cashier_status);
        $this->assertNull($clearance->remarks);
    }

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
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter ClearanceHoldTest`
Expected: FAIL — `POST /approver/hold/{id}` 404.

- [ ] **Step 3: Add Hold routes and clear remarks on Approve**

In `routes/web.php`, replace (inside the `role:registrar,admission` group):

```php
    Route::post('/registrar/sign/{id}', function ($id) {
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update(['registrar_status' => 'Approved']);
            AuditLog::record('Clearance Signed', 'Registrar signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
        }
        return redirect()->route('registrar.dashboard')->with('success', 'Student credentials verified successfully.');
    })->name('registrar.sign');
```

with:

```php
    Route::post('/registrar/sign/{id}', function ($id) {
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update(['registrar_status' => 'Approved', 'remarks' => null]);
            AuditLog::record('Clearance Signed', 'Registrar signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
        }
        return redirect()->route('registrar.dashboard')->with('success', 'Student credentials verified successfully.');
    })->name('registrar.sign');

    Route::post('/registrar/hold/{id}', function (Request $request, $id) {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']]);
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update(['registrar_status' => 'Hold', 'remarks' => $data['remarks']]);
            AuditLog::record('Clearance Held', 'Registrar held clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ': ' . $data['remarks'], 'Clearance', $clearance->id);
            return redirect()->route('registrar.dashboard')->with('success', 'Clearance held with remarks.');
        }
        return redirect()->route('registrar.dashboard')->with('error', 'Record not found.');
    })->name('registrar.hold');
```

Replace (inside the `role:chair` group):

```php
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
```

with:

```php
    Route::post('/approver/sign/{id}', function ($id) {
        $clearance = Clearance::find($id);
        if ($clearance) {
            $clearance->update(['chair_status' => 'Approved', 'remarks' => null]);
            AuditLog::record('Clearance Signed', 'Department Chair signed clearance for student ' . ($clearance->user->name ?? 'ID ' . $clearance->user_id) . ' (' . ($clearance->user->login_id ?? 'N/A') . ').', 'Clearance', $clearance->id);
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
            return redirect()->route('approver.dashboard')->with('success', 'Clearance held with remarks.');
        }
        return redirect()->route('approver.dashboard')->with('error', 'Record not found.');
    })->name('approver.hold');
    }); // end role:chair
```

Replace (inside the `role:cashier` group):

```php
    Route::get('/cashier/dashboard', [AuthController::class, 'showCashierDashboard'])->name('cashier.dashboard');
    Route::post('/cashier/approve', [AuthController::class, 'approveClearance'])->name('cashier.approve');
```

with:

```php
    Route::get('/cashier/dashboard', [AuthController::class, 'showCashierDashboard'])->name('cashier.dashboard');
    Route::post('/cashier/approve', [AuthController::class, 'approveClearance'])->name('cashier.approve');

    Route::post('/cashier/hold', function (Request $request) {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);
        $clearance = Clearance::where('user_id', $data['user_id'])->firstOrFail();
        $clearance->update(['cashier_status' => 'Hold', 'remarks' => $data['remarks']]);
        AuditLog::record('Clearance Held', 'Cashier held clearance for student ID ' . $data['user_id'] . ': ' . $data['remarks'], 'Clearance', $clearance->id);

        return redirect()->back()->with('success', 'Clearance held with remarks.');
    })->name('cashier.hold');
```

In `app/Http/Controllers/AuthController.php`, replace:

```php
        DB::beginTransaction();

        try {
            $clearance->update([
                'cashier_status' => 'Approved',
            ]);
```

with:

```php
        DB::beginTransaction();

        try {
            $clearance->update([
                'cashier_status' => 'Approved',
                'remarks' => null,
            ]);
```

- [ ] **Step 4: Update the three dashboards and the student page**

In `resources/views/approver/dashboard.blade.php`, replace:

```blade
                                        @else
                                            <form action="{{ route('registrar.sign', isset($row->id) ? $row->id : 1) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-3.5 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors">
                                                    Approve Enrollment
                                                </button>
                                            </form>
                                        @endif
```

with:

```blade
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('approver.sign', isset($row->id) ? $row->id : 1) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-3.5 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('approver.hold', isset($row->id) ? $row->id : 1) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold" class="w-36 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                    <button type="submit" class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors">
                                                        Hold
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
```

(This also fixes a pre-existing bug: the button previously posted to `registrar.sign` inside the Chair's own clearance queue, so clicking it updated `registrar_status` instead of `chair_status`. It now correctly calls `approver.sign`.)

In `resources/views/registrar/dashboard.blade.php`, replace:

```blade
                                        @else
                                            <form action="{{ route('registrar.sign', isset($row->id) ? $row->id : 1) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                    Sign Clearance
                                                </button>
                                            </form>
                                        @endif
```

with:

```blade
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('registrar.sign', isset($row->id) ? $row->id : 1) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Sign Clearance
                                                    </button>
                                                </form>
                                                <form action="{{ route('registrar.hold', isset($row->id) ? $row->id : 1) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold" class="w-36 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Hold
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
```

In `resources/views/cashier/dashboard.blade.php`, replace:

```blade
                <form id="overrideForm" action="{{ route('cashier.approve') }}" method="POST" class="grid grid-cols-1 gap-2">
                    @csrf
                    <input type="hidden" name="user_id" id="modalStudentId" value="">
                    <input type="hidden" name="reference_no" id="modalFormRef" value="">
                    <input type="hidden" name="amount" id="modalFormAmount" value="">

                    <button type="submit" class="w-full py-3 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded text-xs uppercase tracking-wider transition-colors">
                        <i class="fa-solid fa-circle-check mr-2"></i>Approve & Sign Off
                    </button>
                    <button type="button" onclick="closeReviewModal()" class="w-full py-3 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/5 text-brandNavy/60 dark:text-slate-400 text-xs font-bold rounded transition-colors">
                        Cancel
                    </button>
                </form>
```

with:

```blade
                <form id="overrideForm" action="{{ route('cashier.approve') }}" method="POST" class="grid grid-cols-1 gap-2">
                    @csrf
                    <input type="hidden" name="user_id" id="modalStudentId" value="">
                    <input type="hidden" name="reference_no" id="modalFormRef" value="">
                    <input type="hidden" name="amount" id="modalFormAmount" value="">

                    <button type="submit" class="w-full py-3 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded text-xs uppercase tracking-wider transition-colors">
                        <i class="fa-solid fa-circle-check mr-2"></i>Approve & Sign Off
                    </button>
                </form>

                <form id="holdForm" action="{{ route('cashier.hold') }}" method="POST" class="grid grid-cols-1 gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="user_id" id="modalHoldStudentId" value="">
                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold"
                           class="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-xs text-brandNavy dark:text-slate-200 outline-none">
                    <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded text-xs uppercase tracking-wider transition-colors">
                        <i class="fa-solid fa-circle-pause mr-2"></i>Hold with Remarks
                    </button>
                </form>

                <button type="button" onclick="closeReviewModal()" class="w-full py-3 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/5 text-brandNavy/60 dark:text-slate-400 text-xs font-bold rounded transition-colors mt-2">
                    Cancel
                </button>
```

Replace:

```js
        function openReviewModal(userId, name, balance, ref) {
            document.getElementById('modalStudentName').innerText = name;
            document.getElementById('modalBalance').innerText = balance;
            document.getElementById('modalRef').innerText = ref;
            document.getElementById('modalStudentId').value = userId;
            document.getElementById('modalFormRef').value = ref;
            document.getElementById('modalFormAmount').value = balance;
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }
```

with:

```js
        function openReviewModal(userId, name, balance, ref) {
            document.getElementById('modalStudentName').innerText = name;
            document.getElementById('modalBalance').innerText = balance;
            document.getElementById('modalRef').innerText = ref;
            document.getElementById('modalStudentId').value = userId;
            document.getElementById('modalFormRef').value = ref;
            document.getElementById('modalFormAmount').value = balance;
            document.getElementById('modalHoldStudentId').value = userId;
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }
```

In `resources/views/clearance.blade.php`, replace:

```blade
                </div>
            </div>

            {{-- CLEARANCE DETAILS --}}
```

with:

```blade
                </div>
            </div>

            @if($clearance->remarks)
                <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 text-xs">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i><strong>Remarks:</strong> {{ $clearance->remarks }}
                </div>
            @endif

            {{-- CLEARANCE DETAILS --}}
```

In `resources/views/partials/notif-script.blade.php`, replace:

```php
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
```

with:

```php
            if ($cl->chair_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-circle-check',  'color' => '#1D7A46', 'title' => 'Dept. Chair Approved',      'desc' => 'Your clearance has been signed by the Department Chair.',          'time' => 'Clearance update'];
            } elseif ($cl->chair_status === 'Hold') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => 'Chair Clearance On Hold', 'desc' => $cl->remarks ?? 'Contact the Department Chair for details.', 'time' => 'Action needed'];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half','color' => '#E2A700', 'title' => 'Awaiting Chair Signature',   'desc' => 'Your clearance is pending the Department Chair\'s sign-off.',     'time' => 'Action needed'];
            }

            if ($cl->registrar_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-file-signature', 'color' => '#0B3C5D', 'title' => 'Registrar Cleared',          'desc' => 'The Registrar has verified and signed your clearance slip.',       'time' => 'Clearance update'];
            } elseif ($cl->registrar_status === 'Hold') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => 'Registrar Clearance On Hold', 'desc' => $cl->remarks ?? 'Contact the Registrar for details.', 'time' => 'Action needed'];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half','color' => '#E2A700', 'title' => 'Registrar Pending',           'desc' => 'Waiting for the Registrar to process your clearance.',             'time' => 'Action needed'];
            }

            if ($cl->cashier_status === 'Approved') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-wallet',         'color' => '#F97316', 'title' => 'Payment Verified',            'desc' => 'Your payment has been received and verified by the Cashier.',      'time' => 'Finance update'];
            } elseif ($cl->cashier_status === 'Hold') {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-triangle-exclamation', 'color' => '#DC2626', 'title' => 'Cashier Clearance On Hold', 'desc' => $cl->remarks ?? 'Contact the Cashier for details.', 'time' => 'Action needed'];
            } else {
                $notifs[] = ['id' => $nid++, 'icon' => 'fa-credit-card',    'color' => '#F97316', 'title' => 'Payment Required',            'desc' => 'Please settle your balance to proceed with clearance.',            'time' => 'Action needed'];
            }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter ClearanceHoldTest`
Expected: PASS (6 tests). Then `php artisan test` — full suite green (180 passed).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/AuthController.php resources/views/approver/dashboard.blade.php resources/views/registrar/dashboard.blade.php resources/views/cashier/dashboard.blade.php resources/views/clearance.blade.php resources/views/partials/notif-script.blade.php tests/Feature/ClearanceHoldTest.php
git commit -m "feat: hold-with-remarks for chair, cashier, and registrar clearance"
```
