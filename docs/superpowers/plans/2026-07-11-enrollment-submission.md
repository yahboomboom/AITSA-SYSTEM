# Real Enrollment Submission Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the read-only, hardcoded enrollment page with a database-backed enrollment workflow: regular students auto-commit a block schedule, irregular students pick sections that a Department Chair approves, and admins manage the real curriculum.

**Architecture:** Laravel 12 monolith gains JSON API routes (Sanctum SPA cookie auth) and two React 18 islands (student enrollment page, admin curriculum editor) mounted from Blade shells via the existing Vite pipeline. New schema: `programs`, `subjects`, `subject_prerequisites`, `sections`, `enrollments`, `enrollment_subjects`, `settings`. Business logic lives in `App\Services\EnrollmentService`; controllers stay thin.

**Tech Stack:** PHP 8.2 / Laravel 12, MySQL (SQLite in-memory for tests), React 18 + Vite 7 + `@vitejs/plugin-react`, Axios, Tailwind via CDN (existing pattern — the Play CDN observes DOM mutations so React-rendered classes are styled).

**Spec:** `docs/superpowers/specs/2026-07-11-enrollment-submission-design.md`

## Global Constraints

- Roles are exactly: `student`, `registrar`, `admission`, `chair`, `cashier`, `admin` — guarded by the existing `role:` middleware alias (`EnsureUserHasRole`).
- Programs seeded: TESDA NC III `BKNC3` (Bookkeeping), `EMNC3` (Events Management), `FBNC3` (Food & Beverages) with `is_enrollable = false`; Associate `BOM` (Business Office Management, 2 yrs), `FSM` (Food Service Management, 2 yrs); Bachelor `BSOA` (BS Office Administration, 4 yrs), `BTVTED` (Bachelor in Technical-Vocational Teacher Education, 4 yrs). All enrollable except TESDA.
- `users.major` stores the **program code** (e.g. `BSOA`) from now on.
- Student is *irregular* iff they have any `student_grades` row with `status = 'Failed'` (existing rule — keep it).
- Clearance gate: `chair_status`, `cashier_status`, `registrar_status` on the student's `clearances` row must all equal `'Approved'` (same rule the notif bell uses).
- Enrollment statuses: `pending` → `enrolled` | `rejected`. Rejected rows are updated in place on resubmit (unique index on `user_id + school_year + semester`).
- Business-rule failures return HTTP 409 with `{"message": "..."}`; validation failures return Laravel-standard 422.
- Every state change (enrollment commit, approve, reject, curriculum write) calls `AuditLog::record(string $action, string $description, string $targetType = null, int $targetId = null)`.
- Blade pages keep the CDN Tailwind + brand colors (`brandNavy #0B3C5D`, `brandGreen #1D7A46`, `brandGold #E2A700`) and dark-mode conventions.
- Tests: PHPUnit, `RefreshDatabase`, SQLite `:memory:` (already configured in `phpunit.xml`). Run with `php artisan test`.
- Out of scope: TESDA self-service enrollment, anything SHS, payments/DocuSign, email, migrating other Blade pages to React.

---

### Task 1: React toolchain on the existing Vite setup

**Files:**
- Modify: `package.json` (deps)
- Modify: `vite.config.js`
- Create: `resources/js/enrollment-app.jsx`
- Create: `resources/js/curriculum-app.jsx`

**Interfaces:**
- Produces: Vite entry points `resources/js/enrollment-app.jsx` (mounts into `#enrollment-root`) and `resources/js/curriculum-app.jsx` (mounts into `#curriculum-root`). Later tasks replace the placeholder components; the entry file names and mount ids must not change.

- [ ] **Step 1: Install React packages**

Run: `npm install react react-dom && npm install -D @vitejs/plugin-react`
Expected: packages added to `package.json` without errors.

- [ ] **Step 2: Register the plugin and entries in Vite**

Replace `vite.config.js` with:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/enrollment-app.jsx',
                'resources/js/curriculum-app.jsx',
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

- [ ] **Step 3: Create placeholder entry files**

`resources/js/enrollment-app.jsx`:

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';

function EnrollmentApp() {
    return <p className="text-sm text-slate-500">Enrollment module loading…</p>;
}

const el = document.getElementById('enrollment-root');
if (el) createRoot(el).render(<EnrollmentApp />);
```

`resources/js/curriculum-app.jsx`:

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';

function CurriculumApp() {
    return <p className="text-sm text-slate-500">Curriculum editor loading…</p>;
}

const el = document.getElementById('curriculum-root');
if (el) createRoot(el).render(<CurriculumApp />);
```

- [ ] **Step 4: Verify the build**

Run: `npm run build`
Expected: build succeeds; `public/build/manifest.json` lists both `.jsx` entries.

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json vite.config.js resources/js/enrollment-app.jsx resources/js/curriculum-app.jsx
git commit -m "feat: add React 18 toolchain with enrollment and curriculum entry points"
```

---

### Task 2: API routing with Sanctum SPA auth

**Files:**
- Modify: `bootstrap/app.php`
- Create: `routes/api.php` (via artisan)
- Test: `tests/Feature/Api/ApiAuthSmokeTest.php`

**Interfaces:**
- Produces: `routes/api.php` with `/api` prefix; `auth:sanctum` guard works with the existing session login (stateful SPA mode). Later tasks add routes to this file.

- [ ] **Step 1: Install the API scaffolding**

Run: `php artisan install:api --no-interaction`
Expected: `laravel/sanctum` added via composer, `routes/api.php` created, `bootstrap/app.php` updated with `api:` routing. If prompted to run migrations, decline (tests use RefreshDatabase; local DB migrated in Task 15).

- [ ] **Step 2: Enable stateful SPA mode**

In `bootstrap/app.php`, inside `->withMiddleware(...)`, add `statefulApi()` so the block reads:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
    $middleware->statefulApi();
})
```

- [ ] **Step 3: Write the failing smoke test**

Replace the contents of `routes/api.php` with:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/ping', function (Request $request) {
    return response()->json(['user' => $request->user()->name]);
});
```

Create `tests/Feature/Api/ApiAuthSmokeTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/ping')->assertUnauthorized();
    }

    public function test_session_user_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->getJson('/api/ping')
            ->assertOk()
            ->assertJson(['user' => $user->name]);
    }
}
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --filter ApiAuthSmokeTest`
Expected: both tests PASS (Sanctum falls back to the `web` session guard in stateful mode).

- [ ] **Step 5: Commit**

```bash
git add bootstrap/app.php routes/api.php composer.json composer.lock config/sanctum.php tests/Feature/Api/ApiAuthSmokeTest.php
git commit -m "feat: add API routing with Sanctum stateful SPA auth"
```

---

### Task 3: Programs and subjects schema

**Files:**
- Create: `database/migrations/2026_07_11_000001_create_programs_table.php`
- Create: `database/migrations/2026_07_11_000002_create_subjects_table.php`
- Create: `database/migrations/2026_07_11_000003_create_subject_prerequisites_table.php`
- Create: `app/Models/Program.php`
- Create: `app/Models/Subject.php`
- Create: `database/factories/ProgramFactory.php`
- Create: `database/factories/SubjectFactory.php`
- Test: `tests/Unit/SubjectPrerequisiteTest.php`

**Interfaces:**
- Produces: `Program` (fillable: `code`, `name`, `level`, `years`, `is_enrollable`; `subjects()` HasMany), `Subject` (fillable: `program_id`, `code`, `title`, `units`, `year_level`, `semester`, `mode`; `program()` BelongsTo, `prerequisites()` BelongsToMany self via `subject_prerequisites`, `sections()` HasMany added in Task 4), factories for both.

- [ ] **Step 1: Write the failing test**

`tests/Unit/SubjectPrerequisiteTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Program;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPrerequisiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_belongs_to_program_and_has_prerequisites(): void
    {
        $program = Program::factory()->create(['code' => 'BSOA']);
        $intro   = Subject::factory()->for($program)->create(['code' => 'OA101', 'year_level' => 1]);
        $advance = Subject::factory()->for($program)->create(['code' => 'OA201', 'year_level' => 2]);

        $advance->prerequisites()->attach($intro->id);

        $this->assertTrue($advance->program->is($program));
        $this->assertTrue($advance->prerequisites->first()->is($intro));
        $this->assertCount(0, $intro->prerequisites);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter SubjectPrerequisiteTest`
Expected: FAIL — `Class "App\Models\Program" not found`.

- [ ] **Step 3: Create migrations**

`database/migrations/2026_07_11_000001_create_programs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('level', ['bachelor', 'associate', 'tesda']);
            $table->unsignedTinyInteger('years')->nullable();
            $table->boolean('is_enrollable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
```

`database/migrations/2026_07_11_000002_create_subjects_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('title');
            $table->unsignedTinyInteger('units');
            $table->unsignedTinyInteger('year_level');
            $table->unsignedTinyInteger('semester');
            $table->enum('mode', ['F2F', 'Online'])->default('F2F');
            $table->timestamps();
            $table->unique(['program_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
```

`database/migrations/2026_07_11_000003_create_subject_prerequisites_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('prerequisite_id')->constrained('subjects')->cascadeOnDelete();
            $table->unique(['subject_id', 'prerequisite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_prerequisites');
    }
};
```

- [ ] **Step 4: Create models and factories**

`app/Models/Program.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'level', 'years', 'is_enrollable'];

    protected $casts = ['is_enrollable' => 'boolean'];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
```

`app/Models/Subject.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['program_id', 'code', 'title', 'units', 'year_level', 'semester', 'mode'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'subject_id', 'prerequisite_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
```

`database/factories/ProgramFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->words(3, true),
            'level' => 'bachelor',
            'years' => 4,
            'is_enrollable' => true,
        ];
    }
}
```

`database/factories/SubjectFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'title' => fake()->words(4, true),
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'mode' => 'F2F',
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter SubjectPrerequisiteTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_11_0000* app/Models/Program.php app/Models/Subject.php database/factories/ProgramFactory.php database/factories/SubjectFactory.php tests/Unit/SubjectPrerequisiteTest.php
git commit -m "feat: add programs, subjects, and prerequisite schema"
```

---

### Task 4: Sections schema with overlap detection

**Files:**
- Create: `database/migrations/2026_07_11_000004_create_sections_table.php`
- Create: `app/Models/Section.php`
- Create: `database/factories/SectionFactory.php`
- Test: `tests/Unit/SectionOverlapTest.php`

**Interfaces:**
- Consumes: `Subject` from Task 3.
- Produces: `Section` (fillable: `subject_id`, `block_label`, `days`, `start_time`, `end_time`, `room`, `professor`, `capacity`, `school_year`; cast `days` → array). Methods: `overlaps(Section $other): bool`, `subject()` BelongsTo, `enrollments()` BelongsToMany (works after Task 5 tables exist), `enrolledCount(): int`, `seatsLeft(): int`, `hasSeats(): bool`. `days` values are strings among `M,T,W,Th,F,Sat,Sun`; times are `HH:MM` 24-hour strings.

- [ ] **Step 1: Write the failing test**

`tests/Unit/SectionOverlapTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_on_same_day_with_intersecting_times_overlap(): void
    {
        $a = Section::factory()->create(['days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $b = Section::factory()->create(['days' => ['W'], 'start_time' => '09:00', 'end_time' => '10:00']);

        $this->assertTrue($a->overlaps($b));
        $this->assertTrue($b->overlaps($a));
    }

    public function test_sections_on_different_days_do_not_overlap(): void
    {
        $a = Section::factory()->create(['days' => ['M'], 'start_time' => '08:00', 'end_time' => '09:00']);
        $b = Section::factory()->create(['days' => ['T'], 'start_time' => '08:00', 'end_time' => '09:00']);

        $this->assertFalse($a->overlaps($b));
    }

    public function test_back_to_back_sections_do_not_overlap(): void
    {
        $a = Section::factory()->create(['days' => ['M'], 'start_time' => '08:00', 'end_time' => '09:00']);
        $b = Section::factory()->create(['days' => ['M'], 'start_time' => '09:00', 'end_time' => '10:00']);

        $this->assertFalse($a->overlaps($b));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter SectionOverlapTest`
Expected: FAIL — `Class "App\Models\Section" not found`.

- [ ] **Step 3: Create migration, model, factory**

`database/migrations/2026_07_11_000004_create_sections_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('block_label', 10);
            $table->json('days');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('room', 50);
            $table->string('professor', 100);
            $table->unsignedSmallInteger('capacity');
            $table->string('school_year', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
```

`app/Models/Section.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'block_label', 'days', 'start_time', 'end_time',
        'room', 'professor', 'capacity', 'school_year',
    ];

    protected $casts = ['days' => 'array'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_subjects');
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->where('enrollments.status', '!=', 'rejected')->count();
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->enrolledCount());
    }

    public function hasSeats(): bool
    {
        return $this->seatsLeft() > 0;
    }

    public function overlaps(Section $other): bool
    {
        if (count(array_intersect($this->days, $other->days)) === 0) {
            return false;
        }

        return $this->start_time < $other->end_time && $other->start_time < $this->end_time;
    }
}
```

`database/factories/SectionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'block_label' => 'A',
            'days' => ['M', 'W'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'Rm 101',
            'professor' => fake()->name(),
            'capacity' => 40,
            'school_year' => '2026-2027',
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter SectionOverlapTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_11_000004_create_sections_table.php app/Models/Section.php database/factories/SectionFactory.php tests/Unit/SectionOverlapTest.php
git commit -m "feat: add sections schema with schedule overlap detection"
```

---

### Task 5: Enrollments, settings schema, and User helpers

**Files:**
- Create: `database/migrations/2026_07_11_000005_create_enrollments_table.php`
- Create: `database/migrations/2026_07_11_000006_create_enrollment_subjects_table.php`
- Create: `database/migrations/2026_07_11_000007_create_settings_table.php`
- Create: `app/Models/Enrollment.php`
- Create: `app/Models/Setting.php`
- Create: `database/factories/EnrollmentFactory.php`
- Modify: `app/Models/User.php` (add helpers)
- Test: `tests/Unit/EnrollmentSchemaTest.php`

**Interfaces:**
- Consumes: `Section` from Task 4, `Program` from Task 3, existing `StudentGrade`.
- Produces:
  - `Enrollment` (fillable: `user_id`, `school_year`, `semester`, `type`, `status`, `block_label`, `remarks`; `user()` BelongsTo, `sections()` BelongsToMany via `enrollment_subjects`).
  - `Setting::get(string $key, ?string $default = null): ?string` and `Setting::put(string $key, string $value): void`.
  - `User::grades(): HasMany`, `User::isIrregularStudent(): bool`, `User::yearNumber(): int`, `User::program(): ?Program`, `User::passedSubjectCodes(): array`.

- [ ] **Step 1: Write the failing test**

`tests/Unit/EnrollmentSchemaTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_enrollment_per_student_per_term(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Enrollment::factory()->create(['user_id' => $user->id, 'school_year' => '2026-2027', 'semester' => 1]);

        $this->expectException(QueryException::class);
        Enrollment::factory()->create(['user_id' => $user->id, 'school_year' => '2026-2027', 'semester' => 1]);
    }

    public function test_rejected_enrollments_do_not_consume_seats(): void
    {
        $section = Section::factory()->create(['capacity' => 10]);
        $active = Enrollment::factory()->create(['status' => 'enrolled']);
        $rejected = Enrollment::factory()->create(['status' => 'rejected']);
        $active->sections()->attach($section->id);
        $rejected->sections()->attach($section->id);

        $this->assertSame(1, $section->enrolledCount());
        $this->assertSame(9, $section->seatsLeft());
    }

    public function test_setting_get_and_put(): void
    {
        $this->assertNull(Setting::get('school_year'));
        Setting::put('school_year', '2026-2027');
        Setting::put('school_year', '2027-2028');
        $this->assertSame('2027-2028', Setting::get('school_year'));
    }

    public function test_user_student_helpers(): void
    {
        $program = Program::factory()->create(['code' => 'BSOA']);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '2nd Year']);
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'OA101', 'status' => 'Passed']);
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'OA102', 'status' => 'Failed']);

        $this->assertTrue($user->isIrregularStudent());
        $this->assertSame(2, $user->yearNumber());
        $this->assertTrue($user->program()->is($program));
        $this->assertSame(['OA101'], $user->passedSubjectCodes());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter EnrollmentSchemaTest`
Expected: FAIL — `Class "App\Models\Enrollment" not found`.

- [ ] **Step 3: Create migrations**

`database/migrations/2026_07_11_000005_create_enrollments_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('school_year', 20);
            $table->unsignedTinyInteger('semester');
            $table->enum('type', ['regular', 'irregular']);
            $table->enum('status', ['pending', 'enrolled', 'rejected'])->default('pending');
            $table->string('block_label', 10)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'school_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
```

`database/migrations/2026_07_11_000006_create_enrollment_subjects_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->unique(['enrollment_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_subjects');
    }
};
```

`database/migrations/2026_07_11_000007_create_settings_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

- [ ] **Step 4: Create models and factory**

`app/Models/Enrollment.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'school_year', 'semester', 'type', 'status', 'block_label', 'remarks',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'enrollment_subjects');
    }
}
```

`app/Models/Setting.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

`database/factories/EnrollmentFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'school_year' => '2026-2027',
            'semester' => 1,
            'type' => 'regular',
            'status' => 'enrolled',
        ];
    }
}
```

- [ ] **Step 5: Add User helpers**

In `app/Models/User.php`, add imports `use Illuminate\Database\Eloquent\Relations\HasMany;` and these methods at the end of the class:

```php
    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isIrregularStudent(): bool
    {
        return $this->grades()->where('status', 'Failed')->exists();
    }

    public function yearNumber(): int
    {
        return ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4][$this->year_level] ?? 1;
    }

    public function program(): ?Program
    {
        return Program::where('code', $this->major)->first();
    }

    /** @return string[] */
    public function passedSubjectCodes(): array
    {
        return $this->grades()->where('status', 'Passed')->pluck('subject_code')->all();
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter EnrollmentSchemaTest`
Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_11_00000{5,6,7}* app/Models/Enrollment.php app/Models/Setting.php database/factories/EnrollmentFactory.php app/Models/User.php tests/Unit/EnrollmentSchemaTest.php
git commit -m "feat: add enrollments, settings schema and student helpers"
```

---

### Task 6: Seeders for programs, curricula, and term settings

**Files:**
- Create: `database/seeders/ProgramSeeder.php`
- Create: `database/seeders/CurriculumSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/SeederTest.php`

**Interfaces:**
- Consumes: all models from Tasks 3–5.
- Produces: seeded DB with 7 programs, per-enrollable-program subjects (each year 1..years × semesters 1–2, 4 subjects each, subject N of year Y has subject N of year Y−1 as prerequisite), 2 blocks (A, B) of sections per year/semester group for the current term, and settings `school_year = 2026-2027`, `semester = 1`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/SeederTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_build_a_demoable_catalogue(): void
    {
        $this->seed([\Database\Seeders\ProgramSeeder::class, \Database\Seeders\CurriculumSeeder::class]);

        $this->assertSame(7, Program::count());
        $this->assertSame(4, Program::where('is_enrollable', true)->count());
        $this->assertSame('2026-2027', Setting::get('school_year'));
        $this->assertSame('1', Setting::get('semester'));

        $bsoa = Program::where('code', 'BSOA')->first();
        // 4 years x 2 semesters x 4 subjects
        $this->assertSame(32, $bsoa->subjects()->count());

        // Year 1 semester 1 has two full blocks of sections
        $y1s1 = Subject::where('program_id', $bsoa->id)->where('year_level', 1)->where('semester', 1)->pluck('id');
        $labels = Section::whereIn('subject_id', $y1s1)->pluck('block_label')->unique()->sort()->values();
        $this->assertSame(['A', 'B'], $labels->all());

        // A year-2 subject requires its year-1 counterpart
        $y2subject = Subject::where('program_id', $bsoa->id)->where('year_level', 2)->where('semester', 1)->first();
        $this->assertCount(1, $y2subject->prerequisites);
        $this->assertSame(1, $y2subject->prerequisites->first()->year_level);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter SeederTest`
Expected: FAIL — `Class "Database\Seeders\ProgramSeeder" does not exist`.

- [ ] **Step 3: Create the seeders**

`database/seeders/ProgramSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['code' => 'BKNC3',  'name' => 'Bookkeeping NC III',                                   'level' => 'tesda',     'years' => null, 'is_enrollable' => false],
            ['code' => 'EMNC3',  'name' => 'Events Management NC III',                             'level' => 'tesda',     'years' => null, 'is_enrollable' => false],
            ['code' => 'FBNC3',  'name' => 'Food & Beverages NC III',                              'level' => 'tesda',     'years' => null, 'is_enrollable' => false],
            ['code' => 'BOM',    'name' => 'Business Office Management',                           'level' => 'associate', 'years' => 2,    'is_enrollable' => true],
            ['code' => 'FSM',    'name' => 'Food Service Management',                              'level' => 'associate', 'years' => 2,    'is_enrollable' => true],
            ['code' => 'BSOA',   'name' => 'Bachelor of Science in Office Administration',         'level' => 'bachelor',  'years' => 4,    'is_enrollable' => true],
            ['code' => 'BTVTED', 'name' => 'Bachelor in Technical-Vocational Teacher Education',   'level' => 'bachelor',  'years' => 4,    'is_enrollable' => true],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(['code' => $program['code']], $program);
        }

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
    }
}
```

`database/seeders/CurriculumSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Draft curricula: 4 subjects per year/semester per enrollable program,
     * two section blocks (A, B) each, chained prerequisites across years.
     * The AITSA team replaces titles/schedules with the real curriculum
     * via the Curriculum Editor.
     */
    public function run(): void
    {
        $schoolYear = Setting::get('school_year', '2026-2027');
        $slots = [
            ['days' => ['M', 'W'],  'start' => '08:00', 'end' => '09:30'],
            ['days' => ['M', 'W'],  'start' => '10:00', 'end' => '11:30'],
            ['days' => ['T', 'Th'], 'start' => '08:00', 'end' => '09:30'],
            ['days' => ['T', 'Th'], 'start' => '10:00', 'end' => '11:30'],
        ];

        foreach (Program::where('is_enrollable', true)->get() as $program) {
            for ($year = 1; $year <= $program->years; $year++) {
                foreach ([1, 2] as $semester) {
                    for ($n = 1; $n <= 4; $n++) {
                        $subject = Subject::updateOrCreate(
                            ['program_id' => $program->id, 'code' => sprintf('%s%d%d%d', $program->code, $year, $semester, $n)],
                            [
                                'title' => sprintf('%s Course %d (Year %d, Sem %d)', $program->code, $n, $year, $semester),
                                'units' => 3,
                                'year_level' => $year,
                                'semester' => $semester,
                                'mode' => $n === 4 ? 'Online' : 'F2F',
                            ]
                        );

                        // Subject N of year Y requires subject N of year Y-1 (same semester).
                        if ($year > 1) {
                            $prereq = Subject::where('program_id', $program->id)
                                ->where('code', sprintf('%s%d%d%d', $program->code, $year - 1, $semester, $n))
                                ->first();
                            if ($prereq) {
                                $subject->prerequisites()->syncWithoutDetaching([$prereq->id]);
                            }
                        }

                        foreach (['A', 'B'] as $block) {
                            Section::updateOrCreate(
                                ['subject_id' => $subject->id, 'block_label' => $block, 'school_year' => $schoolYear],
                                [
                                    'days' => $slots[$n - 1]['days'],
                                    // Block B runs the same slots in the afternoon.
                                    'start_time' => $block === 'A' ? $slots[$n - 1]['start'] : '13:00',
                                    'end_time' => $block === 'A' ? $slots[$n - 1]['end'] : '14:30',
                                    'room' => sprintf('Rm %d0%d', $year, $n),
                                    'professor' => 'TBA Faculty',
                                    'capacity' => 40,
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
}
```

In `database/seeders/DatabaseSeeder.php`, add to the `run()` method (keep whatever is already there):

```php
        $this->call([
            ProgramSeeder::class,
            CurriculumSeeder::class,
        ]);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter SeederTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProgramSeeder.php database/seeders/CurriculumSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/SeederTest.php
git commit -m "feat: seed programs, draft curricula, and term settings"
```

---

### Task 7: EnrollmentService — gate and block computation

**Files:**
- Create: `app/Exceptions/EnrollmentException.php`
- Create: `app/Services/EnrollmentService.php`
- Test: `tests/Feature/EnrollmentBlockTest.php`

**Interfaces:**
- Consumes: models from Tasks 3–5, existing `Clearance` model.
- Produces:
  - `App\Exceptions\EnrollmentException` — `new EnrollmentException(string $message, int $status = 409)`, exposes `->status`.
  - `EnrollmentService::currentTerm(): array` → `['school_year' => string, 'semester' => int]`.
  - `EnrollmentService::clearanceComplete(User $user): bool`.
  - `EnrollmentService::blockFor(User $user): ?array` → `['label' => string, 'sections' => Collection<Section>]` or null.
  - `EnrollmentService::assertCanEnroll(User $user): void` (throws `EnrollmentException` when clearance incomplete or an active — pending/enrolled — enrollment exists for the current term).

- [ ] **Step 1: Write the failing test**

`tests/Feature/EnrollmentBlockTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentBlockTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearedStudent(string $program = 'BSOA', string $year = '1st Year'): User
    {
        $user = User::factory()->create(['role' => 'student', 'major' => $program, 'year_level' => $year]);
        Clearance::create([
            'user_id' => $user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);

        return $user;
    }

    private function makeSection(string $programCode, string $block, array $overrides = []): Section
    {
        $program = Program::firstOrCreate(['code' => $programCode], ['name' => $programCode, 'level' => 'bachelor', 'years' => 4]);
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1]);

        return Section::factory()->for($subject)->create(array_merge(['block_label' => $block, 'school_year' => '2026-2027'], $overrides));
    }

    public function test_block_picks_first_label_with_open_seats_everywhere(): void
    {
        $this->seed(ProgramSeeder::class); // sets school_year/semester settings
        $user = $this->makeClearedStudent();

        $fullA = $this->makeSection('BSOA', 'A', ['capacity' => 1]);
        $openB = $this->makeSection('BSOA', 'B');
        // Fill block A's only seat.
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($fullA->id);

        $block = app(EnrollmentService::class)->blockFor($user);

        $this->assertSame('B', $block['label']);
        $this->assertTrue($block['sections']->first()->is($openB));
    }

    public function test_assert_can_enroll_requires_complete_clearance(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create(['user_id' => $user->id, 'chair_status' => 'Pending', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->assertCanEnroll($user);
    }

    public function test_assert_can_enroll_rejects_duplicate_active_enrollment(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->assertCanEnroll($user);
    }

    public function test_assert_can_enroll_allows_resubmit_after_rejection(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'rejected']);

        app(EnrollmentService::class)->assertCanEnroll($user);
        $this->assertTrue(true); // no exception thrown
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter EnrollmentBlockTest`
Expected: FAIL — `Class "App\Services\EnrollmentService" not found`.

- [ ] **Step 3: Create the exception and service**

`app/Exceptions/EnrollmentException.php`:

```php
<?php

namespace App\Exceptions;

class EnrollmentException extends \Exception
{
    public function __construct(string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }
}
```

`app/Services/EnrollmentService.php`:

```php
<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;

class EnrollmentService
{
    /** @return array{school_year: string, semester: int} */
    public function currentTerm(): array
    {
        return [
            'school_year' => Setting::get('school_year', '2026-2027'),
            'semester' => (int) Setting::get('semester', '1'),
        ];
    }

    public function clearanceComplete(User $user): bool
    {
        $clearance = Clearance::where('user_id', $user->id)->first();

        return $clearance !== null
            && $clearance->chair_status === 'Approved'
            && $clearance->cashier_status === 'Approved'
            && $clearance->registrar_status === 'Approved';
    }

    public function activeEnrollment(User $user): ?Enrollment
    {
        $term = $this->currentTerm();

        return Enrollment::where('user_id', $user->id)
            ->where('school_year', $term['school_year'])
            ->where('semester', $term['semester'])
            ->first();
    }

    public function assertCanEnroll(User $user): void
    {
        if (! $this->clearanceComplete($user)) {
            throw new EnrollmentException('Your clearance is not yet complete. Settle all departments before enrolling.');
        }

        $existing = $this->activeEnrollment($user);
        if ($existing && $existing->status !== 'rejected') {
            throw new EnrollmentException('You already have an enrollment for this term (status: ' . $existing->status . ').');
        }

        $program = $user->program();
        if (! $program || ! $program->is_enrollable) {
            throw new EnrollmentException('Your program does not support self-service enrollment. Please visit the Registrar.');
        }
    }

    /** @return array{label: string, sections: \Illuminate\Support\Collection}|null */
    public function blockFor(User $user): ?array
    {
        $term = $this->currentTerm();
        $program = $user->program();
        if (! $program) {
            return null;
        }

        $grouped = Section::where('school_year', $term['school_year'])
            ->whereHas('subject', fn ($q) => $q
                ->where('program_id', $program->id)
                ->where('year_level', $user->yearNumber())
                ->where('semester', $term['semester']))
            ->with('subject')
            ->get()
            ->groupBy('block_label')
            ->sortKeys();

        foreach ($grouped as $label => $sections) {
            if ($sections->every(fn (Section $s) => $s->hasSeats())) {
                return ['label' => $label, 'sections' => $sections->values()];
            }
        }

        return null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter EnrollmentBlockTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Exceptions/EnrollmentException.php app/Services/EnrollmentService.php tests/Feature/EnrollmentBlockTest.php
git commit -m "feat: enrollment service with clearance gate and block computation"
```

---

### Task 8: EnrollmentService — regular commit with seat locking

**Files:**
- Modify: `app/Services/EnrollmentService.php`
- Test: `tests/Feature/EnrollRegularTest.php`

**Interfaces:**
- Consumes: Task 7 service methods.
- Produces: `EnrollmentService::enrollRegular(User $user): Enrollment` — commits `status = 'enrolled'` with `type = 'regular'` and the block's sections inside a DB transaction with `lockForUpdate`; reuses (updates) a rejected row when present; writes an `AuditLog`. Throws `EnrollmentException` on gate failure, missing block, or seat race.

- [ ] **Step 1: Write the failing test**

`tests/Feature/EnrollRegularTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollRegularTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearedStudent(): User
    {
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create([
            'user_id' => $user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);

        return $user;
    }

    private function makeBlockSection(array $overrides = []): Section
    {
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1]);

        return Section::factory()->for($subject)->create(array_merge(['block_label' => 'A', 'school_year' => '2026-2027'], $overrides));
    }

    public function test_regular_enrollment_commits_block_and_audits(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $section = $this->makeBlockSection();

        $enrollment = app(EnrollmentService::class)->enrollRegular($user);

        $this->assertSame('enrolled', $enrollment->status);
        $this->assertSame('regular', $enrollment->type);
        $this->assertSame('A', $enrollment->block_label);
        $this->assertTrue($enrollment->sections->first()->is($section));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Enrollment Committed', 'target_id' => $enrollment->id]);
    }

    public function test_no_available_block_is_a_clean_conflict(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $section = $this->makeBlockSection(['capacity' => 1]);
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        try {
            app(EnrollmentService::class)->enrollRegular($user);
            $this->fail('Expected EnrollmentException');
        } catch (EnrollmentException $e) {
            $this->assertSame(409, $e->status);
            $this->assertDatabaseMissing('enrollments', ['user_id' => $user->id]);
        }
    }

    public function test_rejected_row_is_reused_on_resubmit(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $this->makeBlockSection();
        $rejected = Enrollment::factory()->create([
            'user_id' => $user->id, 'type' => 'irregular', 'status' => 'rejected', 'remarks' => 'Overloaded',
        ]);

        $enrollment = app(EnrollmentService::class)->enrollRegular($user);

        $this->assertSame($rejected->id, $enrollment->id);
        $this->assertSame('enrolled', $enrollment->status);
        $this->assertNull($enrollment->remarks);
        $this->assertSame(1, Enrollment::where('user_id', $user->id)->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter EnrollRegularTest`
Expected: FAIL — `Call to undefined method App\Services\EnrollmentService::enrollRegular()`.

- [ ] **Step 3: Implement enrollRegular**

Add to `app/Services/EnrollmentService.php` (plus imports `use App\Models\AuditLog;` and `use Illuminate\Support\Facades\DB;`):

```php
    public function enrollRegular(User $user): Enrollment
    {
        $this->assertCanEnroll($user);
        $term = $this->currentTerm();

        return DB::transaction(function () use ($user, $term) {
            $block = $this->blockFor($user);
            if (! $block) {
                throw new EnrollmentException('No block schedule with open seats is available for your program and year level. Please contact the Registrar.');
            }

            $locked = Section::whereIn('id', $block['sections']->pluck('id'))->lockForUpdate()->get();
            foreach ($locked as $section) {
                if (! $section->hasSeats()) {
                    throw new EnrollmentException('A section in your block just filled up. Please try again.');
                }
            }

            $enrollment = $this->upsertEnrollment($user, $term, [
                'type' => 'regular', 'status' => 'enrolled', 'block_label' => $block['label'], 'remarks' => null,
            ]);
            $enrollment->sections()->sync($locked->pluck('id'));

            AuditLog::record(
                'Enrollment Committed',
                sprintf('Regular enrollment committed for %s (%s), block %s, %s sem %d.', $user->name, $user->login_id, $block['label'], $term['school_year'], $term['semester']),
                'Enrollment',
                $enrollment->id
            );

            return $enrollment->load('sections.subject');
        });
    }

    /** @param array{school_year: string, semester: int} $term */
    private function upsertEnrollment(User $user, array $term, array $attributes): Enrollment
    {
        return Enrollment::updateOrCreate(
            ['user_id' => $user->id, 'school_year' => $term['school_year'], 'semester' => $term['semester']],
            $attributes
        );
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter EnrollRegularTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/EnrollmentService.php tests/Feature/EnrollRegularTest.php
git commit -m "feat: regular enrollment commit with seat locking and audit trail"
```

---

### Task 9: EnrollmentService — irregular eligibility, conflicts, and pending commit

**Files:**
- Modify: `app/Services/EnrollmentService.php`
- Test: `tests/Feature/EnrollIrregularTest.php`

**Interfaces:**
- Consumes: Tasks 7–8.
- Produces:
  - `EnrollmentService::catalogueFor(User $user): \Illuminate\Support\Collection` — the student's program subjects for the current semester (all year levels), each item: `['id', 'code', 'title', 'units', 'year_level', 'semester', 'mode', 'eligible' => bool, 'reason' => ?string, 'sections' => [...each with 'id','block_label','days','start_time','end_time','room','professor','seats_left']]`. `eligible=false` reasons: `'Already passed'` or `'Missing prerequisite: <codes>'`.
  - `EnrollmentService::enrollIrregular(User $user, array $sectionIds): Enrollment` — validates ownership/term/eligibility/conflicts/seats, commits `status = 'pending'`, `type = 'irregular'`, audits. Throws `EnrollmentException`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/EnrollIrregularTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollIrregularTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProgramSeeder::class);
        $this->program = Program::where('code', 'BSOA')->first();
        $this->user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '2nd Year']);
        Clearance::create([
            'user_id' => $this->user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);
        // A failed grade makes the student irregular.
        StudentGrade::create(['user_id' => $this->user->id, 'subject_code' => 'ZZ999', 'status' => 'Failed']);
    }

    private function makeSubjectWithSection(string $code, array $sectionOverrides = []): array
    {
        $subject = Subject::factory()->for($this->program)->create(['code' => $code, 'year_level' => 1, 'semester' => 1]);
        $section = Section::factory()->for($subject)->create(array_merge(['school_year' => '2026-2027'], $sectionOverrides));

        return [$subject, $section];
    }

    public function test_catalogue_flags_missing_prerequisites_and_passed_subjects(): void
    {
        [$intro] = $this->makeSubjectWithSection('OA101');
        [$advance] = $this->makeSubjectWithSection('OA201');
        $advance->prerequisites()->attach($intro->id);
        [$done] = $this->makeSubjectWithSection('OA100');
        StudentGrade::create(['user_id' => $this->user->id, 'subject_code' => 'OA100', 'status' => 'Passed']);

        $catalogue = app(EnrollmentService::class)->catalogueFor($this->user)->keyBy('code');

        $this->assertTrue($catalogue['OA101']['eligible']);
        $this->assertFalse($catalogue['OA201']['eligible']);
        $this->assertStringContainsString('OA101', $catalogue['OA201']['reason']);
        $this->assertFalse($catalogue['OA100']['eligible']);
        $this->assertSame('Already passed', $catalogue['OA100']['reason']);
    }

    public function test_conflicting_sections_are_rejected(): void
    {
        [, $a] = $this->makeSubjectWithSection('OA101', ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '10:00']);
        [, $b] = $this->makeSubjectWithSection('OA102', ['days' => ['M'], 'start_time' => '09:00', 'end_time' => '11:00']);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->enrollIrregular($this->user, [$a->id, $b->id]);
    }

    public function test_missing_prerequisite_is_rejected(): void
    {
        [$intro] = $this->makeSubjectWithSection('OA101');
        [$advance, $advSection] = $this->makeSubjectWithSection('OA201');
        $advance->prerequisites()->attach($intro->id);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->enrollIrregular($this->user, [$advSection->id]);
    }

    public function test_full_section_is_rejected(): void
    {
        [, $section] = $this->makeSubjectWithSection('OA101', ['capacity' => 1]);
        \App\Models\Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->enrollIrregular($this->user, [$section->id]);
    }

    public function test_valid_picks_commit_as_pending(): void
    {
        [, $a] = $this->makeSubjectWithSection('OA101', ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '09:00']);
        [, $b] = $this->makeSubjectWithSection('OA102', ['days' => ['T'], 'start_time' => '08:00', 'end_time' => '09:00']);

        $enrollment = app(EnrollmentService::class)->enrollIrregular($this->user, [$a->id, $b->id]);

        $this->assertSame('pending', $enrollment->status);
        $this->assertSame('irregular', $enrollment->type);
        $this->assertCount(2, $enrollment->sections);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Enrollment Submitted', 'target_id' => $enrollment->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter EnrollIrregularTest`
Expected: FAIL — `Call to undefined method ... catalogueFor()`.

- [ ] **Step 3: Implement catalogueFor and enrollIrregular**

Add to `app/Services/EnrollmentService.php` (plus import `use App\Models\Subject;`):

```php
    public function catalogueFor(User $user): \Illuminate\Support\Collection
    {
        $term = $this->currentTerm();
        $program = $user->program();
        if (! $program) {
            return collect();
        }

        $passed = $user->passedSubjectCodes();

        return Subject::where('program_id', $program->id)
            ->where('semester', $term['semester'])
            ->with(['prerequisites', 'sections' => fn ($q) => $q->where('school_year', $term['school_year'])])
            ->orderBy('year_level')->orderBy('code')
            ->get()
            ->map(function (Subject $subject) use ($passed) {
                $missing = $subject->prerequisites->pluck('code')->diff($passed);
                [$eligible, $reason] = match (true) {
                    in_array($subject->code, $passed, true) => [false, 'Already passed'],
                    $missing->isNotEmpty() => [false, 'Missing prerequisite: ' . $missing->implode(', ')],
                    default => [true, null],
                };

                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'title' => $subject->title,
                    'units' => $subject->units,
                    'year_level' => $subject->year_level,
                    'semester' => $subject->semester,
                    'mode' => $subject->mode,
                    'eligible' => $eligible,
                    'reason' => $reason,
                    'sections' => $subject->sections->map(fn (Section $s) => [
                        'id' => $s->id,
                        'block_label' => $s->block_label,
                        'days' => $s->days,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'room' => $s->room,
                        'professor' => $s->professor,
                        'seats_left' => $s->seatsLeft(),
                    ])->values()->all(),
                ];
            })
            ->values();
    }

    /** @param int[] $sectionIds */
    public function enrollIrregular(User $user, array $sectionIds): Enrollment
    {
        $this->assertCanEnroll($user);
        $term = $this->currentTerm();

        if (empty($sectionIds)) {
            throw new EnrollmentException('Select at least one subject to enroll.', 422);
        }

        return DB::transaction(function () use ($user, $term, $sectionIds) {
            $sections = Section::whereIn('id', $sectionIds)->lockForUpdate()->with('subject.prerequisites')->get();

            if ($sections->count() !== count(array_unique($sectionIds))) {
                throw new EnrollmentException('One or more selected sections no longer exist.', 422);
            }

            $program = $user->program();
            $passed = $user->passedSubjectCodes();

            if ($sections->pluck('subject_id')->duplicates()->isNotEmpty()) {
                throw new EnrollmentException('You selected more than one section of the same subject.', 422);
            }

            foreach ($sections as $section) {
                $subject = $section->subject;
                if ($subject->program_id !== $program->id || $subject->semester !== $term['semester'] || $section->school_year !== $term['school_year']) {
                    throw new EnrollmentException("Section for {$subject->code} is not offered to your program this term.", 422);
                }
                if (in_array($subject->code, $passed, true)) {
                    throw new EnrollmentException("You have already passed {$subject->code}.");
                }
                $missing = $subject->prerequisites->pluck('code')->diff($passed);
                if ($missing->isNotEmpty()) {
                    throw new EnrollmentException("{$subject->code} requires: " . $missing->implode(', ') . '.');
                }
                if (! $section->hasSeats()) {
                    throw new EnrollmentException("The {$subject->code} section you picked just filled up. Choose another section.");
                }
            }

            foreach ($sections as $i => $a) {
                foreach ($sections->slice($i + 1) as $b) {
                    if ($a->overlaps($b)) {
                        throw new EnrollmentException("Schedule conflict: {$a->subject->code} overlaps with {$b->subject->code}.");
                    }
                }
            }

            $enrollment = $this->upsertEnrollment($user, $term, [
                'type' => 'irregular', 'status' => 'pending', 'block_label' => null, 'remarks' => null,
            ]);
            $enrollment->sections()->sync($sections->pluck('id'));

            AuditLog::record(
                'Enrollment Submitted',
                sprintf('Irregular enrollment submitted for %s (%s) with %d subject(s); awaiting Department Chair approval.', $user->name, $user->login_id, $sections->count()),
                'Enrollment',
                $enrollment->id
            );

            return $enrollment->load('sections.subject');
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter EnrollIrregularTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Run the whole suite to catch regressions**

Run: `php artisan test`
Expected: all tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/EnrollmentService.php tests/Feature/EnrollIrregularTest.php
git commit -m "feat: irregular enrollment with eligibility, conflict, and capacity checks"
```

---

### Task 10: Student enrollment API endpoints

**Files:**
- Create: `app/Http/Controllers/Api/EnrollmentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/EnrollmentApiTest.php`

**Interfaces:**
- Consumes: `EnrollmentService` (Tasks 7–9).
- Produces:
  - `GET /api/enrollment/context` (auth:sanctum + role:student) → JSON: `{term: {school_year, semester}, student: {name, login_id, program, program_name, year_level, type}, clearance_complete: bool, enrollment: null|{id, status, type, block_label, remarks, sections: [{code, title, units, block_label, days, start_time, end_time, room, professor}]}, block: null|{label, sections: [...same shape]}, catalogue: null|[Task 9 catalogue items]}`. `block` set only for regular students without an active enrollment; `catalogue` only for irregular ones.
  - `POST /api/enrollment` with body `{}` (regular) or `{section_ids: int[]}` (irregular) → 201 with `{enrollment: {...}}`; `EnrollmentException` mapped to its status code with `{message}`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Api/EnrollmentApiTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearedStudent(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(
            ['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year'],
            $overrides
        ));
        Clearance::create([
            'user_id' => $user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);

        return $user;
    }

    private function makeBlockSection(): Section
    {
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1]);

        return Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
    }

    public function test_context_for_regular_student_includes_block(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $this->makeBlockSection();

        $this->actingAs($user)->getJson('/api/enrollment/context')
            ->assertOk()
            ->assertJsonPath('student.type', 'regular')
            ->assertJsonPath('clearance_complete', true)
            ->assertJsonPath('block.label', 'A')
            ->assertJsonPath('catalogue', null)
            ->assertJsonPath('enrollment', null);
    }

    public function test_context_for_irregular_student_includes_catalogue(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => 'ZZ999', 'status' => 'Failed']);
        $this->makeBlockSection();

        $this->actingAs($user)->getJson('/api/enrollment/context')
            ->assertOk()
            ->assertJsonPath('student.type', 'irregular')
            ->assertJsonPath('block', null)
            ->assertJsonCount(1, 'catalogue');
    }

    public function test_regular_store_enrolls_immediately(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $this->makeBlockSection();

        $this->actingAs($user)->postJson('/api/enrollment', [])
            ->assertCreated()
            ->assertJsonPath('enrollment.status', 'enrolled')
            ->assertJsonPath('enrollment.block_label', 'A');
    }

    public function test_incomplete_clearance_is_a_409_with_message(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create(['user_id' => $user->id, 'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending']);

        $this->actingAs($user)->postJson('/api/enrollment', [])
            ->assertStatus(409)
            ->assertJsonStructure(['message']);
    }

    public function test_staff_roles_are_forbidden(): void
    {
        $this->seed(ProgramSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson('/api/enrollment/context')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter EnrollmentApiTest`
Expected: FAIL — 404s (routes not defined).

- [ ] **Step 3: Create the controller**

`app/Http/Controllers/Api/EnrollmentController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $service)
    {
    }

    public function context(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $user->isIrregularStudent() ? 'irregular' : 'regular';
        $enrollment = $this->service->activeEnrollment($user);
        $hasActive = $enrollment && $enrollment->status !== 'rejected';

        $block = null;
        if ($type === 'regular' && ! $hasActive) {
            $found = $this->service->blockFor($user);
            $block = $found ? [
                'label' => $found['label'],
                'sections' => $found['sections']->map(fn ($s) => $this->sectionPayload($s))->values(),
            ] : null;
        }

        return response()->json([
            'term' => $this->service->currentTerm(),
            'student' => [
                'name' => $user->name,
                'login_id' => $user->login_id,
                'program' => $user->major,
                'program_name' => $user->program()?->name,
                'year_level' => $user->year_level,
                'type' => $type,
            ],
            'clearance_complete' => $this->service->clearanceComplete($user),
            'enrollment' => $enrollment ? $this->enrollmentPayload($enrollment) : null,
            'block' => $block,
            'catalogue' => $type === 'irregular' && ! $hasActive ? $this->service->catalogueFor($user) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $enrollment = $user->isIrregularStudent()
                ? $this->service->enrollIrregular($user, $request->validate(['section_ids' => ['required', 'array'], 'section_ids.*' => ['integer']])['section_ids'])
                : $this->service->enrollRegular($user);
        } catch (EnrollmentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['enrollment' => $this->enrollmentPayload($enrollment)], 201);
    }

    private function enrollmentPayload(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('sections.subject');

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->status,
            'type' => $enrollment->type,
            'block_label' => $enrollment->block_label,
            'remarks' => $enrollment->remarks,
            'sections' => $enrollment->sections->map(fn ($s) => $this->sectionPayload($s))->values(),
        ];
    }

    private function sectionPayload($section): array
    {
        return [
            'code' => $section->subject->code,
            'title' => $section->subject->title,
            'units' => $section->subject->units,
            'block_label' => $section->block_label,
            'days' => $section->days,
            'start_time' => $section->start_time,
            'end_time' => $section->end_time,
            'room' => $section->room,
            'professor' => $section->professor,
        ];
    }
}
```

- [ ] **Step 4: Register routes**

Replace the contents of `routes/api.php` with:

```php
<?php

use App\Http\Controllers\Api\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/enrollment/context', [EnrollmentController::class, 'context']);
    Route::post('/enrollment', [EnrollmentController::class, 'store']);
});
```

(The temporary `/ping` route from Task 2 is removed, which makes `tests/Feature/Api/ApiAuthSmokeTest.php` obsolete — the enrollment tests now cover API auth. The `git rm` in the commit step deletes it.)

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter EnrollmentApiTest`
Expected: PASS (5 tests). Then run `php artisan test` — all PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/EnrollmentController.php routes/api.php tests/Feature/Api/EnrollmentApiTest.php
git rm tests/Feature/Api/ApiAuthSmokeTest.php
git commit -m "feat: student enrollment context and submission API"
```

---

### Task 11: Department Chair approval queue (Blade)

**Files:**
- Modify: `routes/web.php` (inside the existing `Route::middleware('role:chair')` group, around line 199)
- Modify: `resources/views/approver/dashboard.blade.php`
- Test: `tests/Feature/ChairApprovalTest.php`

**Interfaces:**
- Consumes: `Enrollment`, `Section`, `AuditLog`.
- Produces: web routes `POST /approver/enrollments/{enrollment}/approve` (name `approver.enrollments.approve`) and `POST /approver/enrollments/{enrollment}/reject` (name `approver.enrollments.reject`, requires `remarks` string ≤ 500). The chair dashboard route passes `$pendingEnrollments` (Enrollment with `user` and `sections.subject`, status `pending`, newest first) to the view.

- [ ] **Step 1: Write the failing test**

`tests/Feature/ChairApprovalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChairApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingEnrollment(): Enrollment
    {
        $enrollment = Enrollment::factory()->create(['type' => 'irregular', 'status' => 'pending']);
        $enrollment->sections()->attach(Section::factory()->create(['capacity' => 5])->id);

        return $enrollment;
    }

    public function test_chair_can_approve_pending_enrollment(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $enrollment = $this->makePendingEnrollment();

        $this->actingAs($chair)
            ->post("/approver/enrollments/{$enrollment->id}/approve")
            ->assertRedirect();

        $this->assertSame('enrolled', $enrollment->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Enrollment Approved', 'target_id' => $enrollment->id]);
    }

    public function test_chair_rejection_requires_remarks(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $enrollment = $this->makePendingEnrollment();

        $this->actingAs($chair)
            ->from('/approver/dashboard')
            ->post("/approver/enrollments/{$enrollment->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($chair)
            ->post("/approver/enrollments/{$enrollment->id}/reject", ['remarks' => 'Unit overload'])
            ->assertRedirect();

        $fresh = $enrollment->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Unit overload', $fresh->remarks);
    }

    public function test_approving_when_a_seat_vanished_fails_cleanly(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $enrollment = $this->makePendingEnrollment();
        $section = $enrollment->sections->first();
        $section->update(['capacity' => 1]);
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        $this->actingAs($chair)
            ->post("/approver/enrollments/{$enrollment->id}/approve")
            ->assertSessionHas('error');

        $this->assertSame('pending', $enrollment->fresh()->status);
    }

    public function test_students_cannot_touch_the_queue(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $enrollment = $this->makePendingEnrollment();

        $this->actingAs($student)
            ->post("/approver/enrollments/{$enrollment->id}/approve")
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ChairApprovalTest`
Expected: FAIL — 404 (routes missing).

- [ ] **Step 3: Add routes**

In `routes/web.php`, add `use App\Models\Enrollment;` and `use App\Models\Section;` to the imports, then inside the existing `Route::middleware('role:chair')->group(...)` add:

```php
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
```

- [ ] **Step 4: Update the chair dashboard route and view**

In the `GET /approver/dashboard` route closure (inside the same `role:chair` group), add before `return view(...)`:

```php
        $pendingEnrollments = Enrollment::with(['user', 'sections.subject'])
            ->where('status', 'pending')
            ->latest()
            ->get();
```

and pass it to the view (add `'pendingEnrollments' => $pendingEnrollments` to the view data / `compact()`).

In `resources/views/approver/dashboard.blade.php`, add this panel after the existing clearance queue markup (match the page's existing card/table classes — copy the wrapper classes of the clearance table already in the file):

```blade
        {{-- Irregular Enrollment Approval Queue --}}
        <div class="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6 mt-8">
            <h2 class="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i class="fa-solid fa-user-graduate mr-2 text-brandGreen"></i>Pending Irregular Enrollments
            </h2>

            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif

            @forelse (($pendingEnrollments ?? []) as $pending)
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <p class="font-semibold text-brandNavy dark:text-slate-100">{{ $pending->user->name }} ({{ $pending->user->login_id }})</p>
                            <p class="text-xs text-slate-500">{{ $pending->user->major }} — {{ $pending->user->year_level }} — submitted {{ $pending->updated_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('approver.enrollments.approve', $pending) }}">
                                @csrf
                                <button class="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('approver.enrollments.reject', $pending) }}" class="flex gap-2">
                                @csrf
                                <input name="remarks" required maxlength="500" placeholder="Reason for rejection"
                                       class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm" />
                                <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                            </form>
                        </div>
                    </div>
                    <table class="w-full mt-3 text-sm">
                        <thead class="text-left text-xs uppercase text-slate-400">
                            <tr><th class="py-1">Code</th><th>Title</th><th>Schedule</th><th>Room</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($pending->sections as $section)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="py-1 font-mono">{{ $section->subject->code }}</td>
                                    <td>{{ $section->subject->title }}</td>
                                    <td>{{ implode('/', $section->days) }} {{ $section->start_time }}–{{ $section->end_time }}</td>
                                    <td>{{ $section->room }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-sm text-slate-400">No enrollments awaiting approval.</p>
            @endforelse
        </div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter ChairApprovalTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/approver/dashboard.blade.php tests/Feature/ChairApprovalTest.php
git commit -m "feat: department chair approval queue for irregular enrollments"
```

---

### Task 12: Curriculum CRUD API for admins

**Files:**
- Create: `app/Http/Controllers/Api/Admin/ProgramController.php`
- Create: `app/Http/Controllers/Api/Admin/SubjectController.php`
- Create: `app/Http/Controllers/Api/Admin/SectionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/CurriculumApiTest.php`

**Interfaces:**
- Consumes: models from Tasks 3–5.
- Produces (all under `auth:sanctum` + `role:admin`, prefix `/api/admin`):
  - `GET /api/admin/programs` → `{programs: [{id, code, name, level, years, is_enrollable, subjects_count}]}`
  - `GET /api/admin/programs/{program}/subjects` → `{subjects: [{id, code, title, units, year_level, semester, mode, prerequisite_ids: int[], sections: [{id, block_label, days, start_time, end_time, room, professor, capacity, school_year, enrolled_count}]}]}`
  - `POST /api/admin/subjects`, `PUT /api/admin/subjects/{subject}`, `DELETE /api/admin/subjects/{subject}`
  - `POST /api/admin/sections`, `PUT /api/admin/sections/{section}`, `DELETE /api/admin/sections/{section}`
  - Subject payload fields: `program_id, code, title, units, year_level, semester, mode, prerequisite_ids[]`. Section payload fields: `subject_id, block_label, days[], start_time, end_time, room, professor, capacity, school_year`.
  - Deleting a subject/section with any non-rejected enrollment attached → 409 `{message}`. Every write audits.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Api/CurriculumApiTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_students_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->getJson('/api/admin/programs')->assertForbidden();
    }

    public function test_admin_lists_programs_and_subjects(): void
    {
        $program = Program::factory()->create(['code' => 'BSOA']);
        $subject = Subject::factory()->for($program)->create();
        Section::factory()->for($subject)->create();

        $this->actingAs($this->admin)->getJson('/api/admin/programs')
            ->assertOk()->assertJsonPath('programs.0.code', 'BSOA');

        $this->actingAs($this->admin)->getJson("/api/admin/programs/{$program->id}/subjects")
            ->assertOk()
            ->assertJsonPath('subjects.0.id', $subject->id)
            ->assertJsonCount(1, 'subjects.0.sections');
    }

    public function test_admin_creates_subject_with_prerequisites(): void
    {
        $program = Program::factory()->create();
        $prereq = Subject::factory()->for($program)->create();

        $this->actingAs($this->admin)->postJson('/api/admin/subjects', [
            'program_id' => $program->id, 'code' => 'OA201', 'title' => 'Advanced Office Procedures',
            'units' => 3, 'year_level' => 2, 'semester' => 1, 'mode' => 'F2F',
            'prerequisite_ids' => [$prereq->id],
        ])->assertCreated();

        $subject = Subject::where('code', 'OA201')->first();
        $this->assertTrue($subject->prerequisites->first()->is($prereq));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Curriculum Updated']);
    }

    public function test_subject_with_enrollments_cannot_be_deleted(): void
    {
        $section = Section::factory()->create();
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/subjects/{$section->subject_id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('subjects', ['id' => $section->subject_id]);
    }

    public function test_section_crud_and_delete_guard(): void
    {
        $subject = Subject::factory()->create();

        $create = $this->actingAs($this->admin)->postJson('/api/admin/sections', [
            'subject_id' => $subject->id, 'block_label' => 'A', 'days' => ['M', 'W'],
            'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Rm 101',
            'professor' => 'J. Dela Cruz', 'capacity' => 40, 'school_year' => '2026-2027',
        ]);
        $create->assertCreated();
        $sectionId = $create->json('section.id');

        $this->actingAs($this->admin)->putJson("/api/admin/sections/{$sectionId}", [
            'room' => 'Rm 202',
        ])->assertOk();
        $this->assertSame('Rm 202', Section::find($sectionId)->room);

        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($sectionId);
        $this->actingAs($this->admin)->deleteJson("/api/admin/sections/{$sectionId}")->assertStatus(409);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter CurriculumApiTest`
Expected: FAIL — 404s.

- [ ] **Step 3: Create the controllers**

`app/Http/Controllers/Api/Admin/ProgramController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\JsonResponse;

class ProgramController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'programs' => Program::withCount('subjects')->orderBy('level')->orderBy('code')->get()
                ->map(fn (Program $p) => [
                    'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'level' => $p->level,
                    'years' => $p->years, 'is_enrollable' => $p->is_enrollable, 'subjects_count' => $p->subjects_count,
                ]),
        ]);
    }

    public function subjects(Program $program): JsonResponse
    {
        return response()->json([
            'subjects' => $program->subjects()->with(['prerequisites', 'sections'])
                ->orderBy('year_level')->orderBy('semester')->orderBy('code')->get()
                ->map(fn ($s) => [
                    'id' => $s->id, 'code' => $s->code, 'title' => $s->title, 'units' => $s->units,
                    'year_level' => $s->year_level, 'semester' => $s->semester, 'mode' => $s->mode,
                    'prerequisite_ids' => $s->prerequisites->pluck('id'),
                    'sections' => $s->sections->map(fn ($sec) => [
                        'id' => $sec->id, 'block_label' => $sec->block_label, 'days' => $sec->days,
                        'start_time' => $sec->start_time, 'end_time' => $sec->end_time, 'room' => $sec->room,
                        'professor' => $sec->professor, 'capacity' => $sec->capacity,
                        'school_year' => $sec->school_year, 'enrolled_count' => $sec->enrolledCount(),
                    ]),
                ]),
        ]);
    }
}
```

`app/Http/Controllers/Api/Admin/SubjectController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $subject = Subject::create($data);
        $subject->prerequisites()->sync($request->input('prerequisite_ids', []));

        AuditLog::record('Curriculum Updated', "Subject {$subject->code} created.", 'Subject', $subject->id);

        return response()->json(['subject' => $subject->load('prerequisites')], 201);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $subject->update($this->validated($request, $subject));
        if ($request->has('prerequisite_ids')) {
            $subject->prerequisites()->sync($request->input('prerequisite_ids', []));
        }

        AuditLog::record('Curriculum Updated', "Subject {$subject->code} updated.", 'Subject', $subject->id);

        return response()->json(['subject' => $subject->fresh()->load('prerequisites')]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $hasEnrollments = $subject->sections()
            ->whereHas('enrollments', fn ($q) => $q->where('enrollments.status', '!=', 'rejected'))
            ->exists();

        if ($hasEnrollments) {
            return response()->json(['message' => 'This subject has enrolled students and cannot be deleted. Edit it instead.'], 409);
        }

        AuditLog::record('Curriculum Updated', "Subject {$subject->code} deleted.", 'Subject', $subject->id);
        $subject->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $required = $subject ? 'sometimes' : 'required';

        return $request->validate([
            'program_id' => [$required, 'integer', 'exists:programs,id'],
            'code' => [$required, 'string', 'max:20'],
            'title' => [$required, 'string', 'max:255'],
            'units' => [$required, 'integer', 'between:1,12'],
            'year_level' => [$required, 'integer', 'between:1,4'],
            'semester' => [$required, 'integer', 'between:1,2'],
            'mode' => [$required, 'in:F2F,Online'],
        ]);
    }
}
```

`app/Http/Controllers/Api/Admin/SectionController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $section = Section::create($this->validated($request));

        AuditLog::record('Curriculum Updated', "Section {$section->block_label} added to subject #{$section->subject_id}.", 'Section', $section->id);

        return response()->json(['section' => $section], 201);
    }

    public function update(Request $request, Section $section): JsonResponse
    {
        $section->update($this->validated($request, $section));

        AuditLog::record('Curriculum Updated', "Section #{$section->id} updated.", 'Section', $section->id);

        return response()->json(['section' => $section->fresh()]);
    }

    public function destroy(Section $section): JsonResponse
    {
        if ($section->enrollments()->where('enrollments.status', '!=', 'rejected')->exists()) {
            return response()->json(['message' => 'This section has enrolled students and cannot be deleted. Edit it instead.'], 409);
        }

        AuditLog::record('Curriculum Updated', "Section #{$section->id} deleted.", 'Section', $section->id);
        $section->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?Section $section = null): array
    {
        $required = $section ? 'sometimes' : 'required';

        return $request->validate([
            'subject_id' => [$required, 'integer', 'exists:subjects,id'],
            'block_label' => [$required, 'string', 'max:10'],
            'days' => [$required, 'array', 'min:1'],
            'days.*' => ['in:M,T,W,Th,F,Sat,Sun'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i', 'after:start_time'],
            'room' => [$required, 'string', 'max:50'],
            'professor' => [$required, 'string', 'max:100'],
            'capacity' => [$required, 'integer', 'between:1,500'],
            'school_year' => [$required, 'string', 'max:20'],
        ]);
    }
}
```

- [ ] **Step 4: Register routes**

Append to `routes/api.php`:

```php
use App\Http\Controllers\Api\Admin\ProgramController;
use App\Http\Controllers\Api\Admin\SectionController;
use App\Http\Controllers\Api\Admin\SubjectController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::get('/programs/{program}/subjects', [ProgramController::class, 'subjects']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::put('/sections/{section}', [SectionController::class, 'update']);
    Route::delete('/sections/{section}', [SectionController::class, 'destroy']);
});
```

(Move the `use` lines to the top of the file with the other imports.)

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter CurriculumApiTest`
Expected: PASS (5 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin routes/api.php tests/Feature/Api/CurriculumApiTest.php
git commit -m "feat: admin curriculum CRUD API with enrollment-aware delete guards"
```

---

### Task 13: React enrollment island

**Files:**
- Create: `resources/js/lib/api.js`
- Modify: `resources/js/enrollment-app.jsx` (replace placeholder)
- Create: `resources/js/enrollment/RegularView.jsx`
- Create: `resources/js/enrollment/IrregularPicker.jsx`
- Create: `resources/js/enrollment/StatusCard.jsx`
- Modify: `resources/views/enrollment.blade.php`

**Interfaces:**
- Consumes: `GET /api/enrollment/context`, `POST /api/enrollment` (Task 10 payload shapes).
- Produces: React app mounted at `#enrollment-root` inside the existing enrollment Blade page.

- [ ] **Step 1: Create the shared API helper**

`resources/js/lib/api.js`:

```js
import axios from 'axios';

// Same-origin: the session cookie authenticates; axios auto-sends the
// XSRF-TOKEN cookie as X-XSRF-TOKEN, which Sanctum's stateful mode verifies.
const api = axios.create({
    baseURL: '/api',
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

export default api;
```

- [ ] **Step 2: Build the components**

`resources/js/enrollment/StatusCard.jsx`:

```jsx
import React from 'react';

const STYLES = {
    pending:  { icon: 'fa-hourglass-half', tone: 'text-amber-600',  label: 'Awaiting Department Chair Approval' },
    enrolled: { icon: 'fa-circle-check',   tone: 'text-brandGreen', label: 'Officially Enrolled' },
    rejected: { icon: 'fa-circle-xmark',   tone: 'text-red-600',    label: 'Returned with Remarks' },
};

export default function StatusCard({ enrollment, onResubmit }) {
    const style = STYLES[enrollment.status];

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <p className={`text-lg font-bold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            {enrollment.status === 'rejected' && (
                <div className="mt-3">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        <span className="font-semibold">Chair remarks:</span> {enrollment.remarks}
                    </p>
                    <button onClick={onResubmit}
                        className="mt-3 px-4 py-2 rounded-lg bg-brandNavy text-white text-sm font-semibold hover:opacity-90">
                        Revise & Resubmit
                    </button>
                </div>
            )}
            {enrollment.sections.length > 0 && (
                <table className="w-full mt-4 text-sm">
                    <thead className="text-left text-xs uppercase text-slate-400">
                        <tr><th className="py-1">Code</th><th>Title</th><th>Units</th><th>Schedule</th><th>Room</th><th>Professor</th></tr>
                    </thead>
                    <tbody>
                        {enrollment.sections.map((s) => (
                            <tr key={s.code} className="border-t border-slate-100 dark:border-slate-800">
                                <td className="py-1 font-mono">{s.code}</td>
                                <td>{s.title}</td>
                                <td>{s.units}</td>
                                <td>{s.days.join('/')} {s.start_time}–{s.end_time}</td>
                                <td>{s.room}</td>
                                <td>{s.professor}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
```

`resources/js/enrollment/RegularView.jsx`:

```jsx
import React from 'react';

export default function RegularView({ block, submitting, error, onConfirm }) {
    if (!block) {
        return (
            <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
                <p className="text-sm text-slate-500">
                    No block schedule with open seats is available for your program and year level.
                    Please contact the Registrar's Office.
                </p>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">
                Your Block Schedule — Block {block.label}
            </h2>
            <p className="text-xs text-slate-500 mb-4">Review your pre-assigned schedule, then confirm your subject load.</p>
            <table className="w-full text-sm">
                <thead className="text-left text-xs uppercase text-slate-400">
                    <tr><th className="py-1">Code</th><th>Title</th><th>Units</th><th>Schedule</th><th>Room</th><th>Professor</th></tr>
                </thead>
                <tbody>
                    {block.sections.map((s) => (
                        <tr key={s.code} className="border-t border-slate-100 dark:border-slate-800">
                            <td className="py-1 font-mono">{s.code}</td>
                            <td>{s.title}</td>
                            <td>{s.units}</td>
                            <td>{s.days.join('/')} {s.start_time}–{s.end_time}</td>
                            <td>{s.room}</td>
                            <td>{s.professor}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
            <button onClick={onConfirm} disabled={submitting}
                className="mt-4 px-5 py-2.5 rounded-lg bg-brandGreen text-white font-semibold text-sm hover:opacity-90 disabled:opacity-50">
                {submitting ? 'Submitting…' : 'Confirm Subject Load'}
            </button>
        </div>
    );
}
```

`resources/js/enrollment/IrregularPicker.jsx`:

```jsx
import React, { useMemo, useState } from 'react';

function overlaps(a, b) {
    if (!a.days.some((d) => b.days.includes(d))) return false;
    return a.start_time < b.end_time && b.start_time < a.end_time;
}

export default function IrregularPicker({ catalogue, submitting, error, onSubmit }) {
    // subjectId -> section object
    const [picks, setPicks] = useState({});

    const conflict = useMemo(() => {
        const chosen = Object.values(picks);
        for (let i = 0; i < chosen.length; i++) {
            for (let j = i + 1; j < chosen.length; j++) {
                if (overlaps(chosen[i], chosen[j])) return [chosen[i], chosen[j]];
            }
        }
        return null;
    }, [picks]);

    const toggle = (subject, section) => {
        setPicks((prev) => {
            const next = { ...prev };
            if (next[subject.id]?.id === section.id) delete next[subject.id];
            else next[subject.id] = { ...section, code: subject.code };
            return next;
        });
    };

    const years = useMemo(
        () => [...new Set(catalogue.map((s) => s.year_level))].sort(),
        [catalogue]
    );

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">Build Your Schedule</h2>
            <p className="text-xs text-slate-500 mb-4">
                Pick one section per subject. Your selection is submitted to the Department Chair for approval.
            </p>

            {years.map((year) => (
                <div key={year} className="mb-6">
                    <h3 className="text-sm font-bold text-slate-500 uppercase mb-2">Year {year}</h3>
                    {catalogue.filter((s) => s.year_level === year).map((subject) => (
                        <div key={subject.id}
                            className={`border rounded-xl p-4 mb-3 ${subject.eligible ? 'border-slate-200 dark:border-slate-700' : 'border-slate-100 dark:border-slate-800 opacity-60'}`}>
                            <div className="flex items-center justify-between flex-wrap gap-2">
                                <p className="font-semibold text-brandNavy dark:text-slate-100">
                                    <span className="font-mono">{subject.code}</span> — {subject.title}
                                    <span className="ml-2 text-xs text-slate-400">{subject.units} units · {subject.mode}</span>
                                </p>
                                {!subject.eligible && (
                                    <span className="text-xs font-semibold text-amber-600">
                                        <i className="fa-solid fa-lock mr-1" />{subject.reason}
                                    </span>
                                )}
                            </div>
                            {subject.eligible && (
                                <div className="flex flex-wrap gap-2 mt-3">
                                    {subject.sections.map((section) => {
                                        const selected = picks[subject.id]?.id === section.id;
                                        const full = section.seats_left <= 0;
                                        return (
                                            <button key={section.id} disabled={full && !selected}
                                                onClick={() => toggle(subject, section)}
                                                className={`px-3 py-2 rounded-lg border text-xs text-left
                                                    ${selected ? 'border-brandGreen bg-brandGreen/10 text-brandGreen font-semibold'
                                                        : full ? 'border-slate-200 text-slate-400 cursor-not-allowed'
                                                        : 'border-slate-300 dark:border-slate-600 hover:border-brandNavy'}`}>
                                                <span className="font-semibold">Block {section.block_label}</span>{' '}
                                                {section.days.join('/')} {section.start_time}–{section.end_time} · {section.room}
                                                <span className="block text-[10px] opacity-70">
                                                    {full ? 'Section full' : `${section.seats_left} seats left`} · {section.professor}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            ))}

            {conflict && (
                <p className="text-sm text-red-600 mb-2">
                    <i className="fa-solid fa-triangle-exclamation mr-1" />
                    Schedule conflict: {conflict[0].code} overlaps with {conflict[1].code}.
                </p>
            )}
            {error && <p className="text-sm text-red-600 mb-2">{error}</p>}

            <button
                disabled={submitting || conflict !== null || Object.keys(picks).length === 0}
                onClick={() => onSubmit(Object.values(picks).map((s) => s.id))}
                className="px-5 py-2.5 rounded-lg bg-brandNavy text-white font-semibold text-sm hover:opacity-90 disabled:opacity-50">
                {submitting ? 'Submitting…' : `Submit ${Object.keys(picks).length} Subject(s) for Approval`}
            </button>
        </div>
    );
}
```

Replace `resources/js/enrollment-app.jsx` with:

```jsx
import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import api from './lib/api';
import StatusCard from './enrollment/StatusCard';
import RegularView from './enrollment/RegularView';
import IrregularPicker from './enrollment/IrregularPicker';

function EnrollmentApp() {
    const [ctx, setCtx] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [resubmitting, setResubmitting] = useState(false);

    const load = useCallback(() => {
        setLoading(true);
        api.get('/enrollment/context')
            .then((res) => setCtx(res.data))
            .catch(() => setError('Could not load enrollment data. Please refresh the page.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(load, [load]);

    const submit = (sectionIds) => {
        setSubmitting(true);
        setError(null);
        const body = ctx.student.type === 'irregular' ? { section_ids: sectionIds } : {};
        api.post('/enrollment', body)
            .then(() => { setResubmitting(false); load(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Something went wrong. Please try again.'))
            .finally(() => setSubmitting(false));
    };

    if (loading) return <p className="text-sm text-slate-500">Loading your enrollment…</p>;
    if (!ctx) return <p className="text-sm text-red-600">{error}</p>;

    const { term, student, clearance_complete, enrollment, block, catalogue } = ctx;

    return (
        <div className="space-y-6">
            <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl font-bold text-brandNavy dark:text-slate-100">Enrollment — A.Y. {term.school_year}, Semester {term.semester}</h1>
                    <p className="text-xs text-slate-500">
                        {student.name} ({student.login_id}) · {student.program_name ?? student.program} · {student.year_level} ·{' '}
                        <span className={student.type === 'regular' ? 'text-brandGreen font-semibold' : 'text-amber-600 font-semibold'}>
                            {student.type === 'regular' ? 'Regular' : 'Irregular'}
                        </span>
                    </p>
                </div>
            </div>

            {!clearance_complete && (
                <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
                    <p className="text-sm font-semibold text-amber-600">
                        <i className="fa-solid fa-lock mr-2" />
                        Enrollment is locked until your clearance is fully approved.
                    </p>
                    <a href="/clearance" className="inline-block mt-3 text-sm text-brandNavy dark:text-slate-200 underline">
                        View my clearance status
                    </a>
                </div>
            )}

            {clearance_complete && enrollment && !(enrollment.status === 'rejected' && resubmitting) && (
                <StatusCard enrollment={enrollment} onResubmit={() => setResubmitting(true)} />
            )}

            {clearance_complete && (!enrollment || (enrollment.status === 'rejected' && resubmitting)) && (
                student.type === 'regular'
                    ? <RegularView block={block} submitting={submitting} error={error} onConfirm={() => submit()} />
                    : <IrregularPicker catalogue={catalogue ?? []} submitting={submitting} error={error} onSubmit={submit} />
            )}
        </div>
    );
}

const el = document.getElementById('enrollment-root');
if (el) createRoot(el).render(<EnrollmentApp />);
```

Note: with a rejected enrollment, `catalogue`/`block` are still needed to resubmit — Task 10's controller returns them when `status === 'rejected'` (`$hasActive` is false), so this works.

- [ ] **Step 3: Convert the Blade page to a shell**

In `resources/views/enrollment.blade.php`:

1. Keep everything up to and including the sidebar markup and the `<main>` opening tag (the page header/nav/theme scripts stay untouched).
2. Delete the entire main-content markup for the year/semester tabs and subject table, and delete the whole `<script>` block that defines `const CATALOGUE = [...]` through its closing `</script>` (this includes `conflictsWith`, tab rendering, and the enroll click handler).
3. In place of the deleted content area, insert:

```blade
                <div id="enrollment-root">
                    <p class="text-sm text-slate-500">Loading your enrollment…</p>
                </div>
```

4. Just before the closing `</body>` tag, add:

```blade
    @viteReactRefresh
    @vite('resources/js/enrollment-app.jsx')
```

- [ ] **Step 4: Build and verify manually**

Run: `npm run build`
Expected: build succeeds.

Run: `php artisan migrate:fresh --seed` (local MySQL), then log in as a seeded/created student with complete clearance and open `/enrollment`.
Expected: regular student sees the Block A table and can confirm; the page then shows "Officially Enrolled". (If no student user exists locally, create one via the admin Register Student form with major `BSOA`, year `1st Year`, then approve their clearance via the staff dashboards.)

- [ ] **Step 5: Run the full test suite**

Run: `php artisan test`
Expected: all PASS (Blade change is presentation-only; API tests still green).

- [ ] **Step 6: Commit**

```bash
git add resources/js resources/views/enrollment.blade.php
git commit -m "feat: React enrollment island with regular and irregular flows"
```

---

### Task 14: React curriculum editor island

**Files:**
- Modify: `resources/js/curriculum-app.jsx` (replace placeholder)
- Create: `resources/js/curriculum/SubjectRow.jsx`
- Create: `resources/js/curriculum/SectionEditor.jsx`
- Modify: `resources/views/admin/curriculum.blade.php`

**Interfaces:**
- Consumes: Task 12 admin API endpoints and payload shapes.
- Produces: React app mounted at `#curriculum-root` in the admin curriculum page.

- [ ] **Step 1: Build the components**

`resources/js/curriculum/SectionEditor.jsx`:

```jsx
import React, { useState } from 'react';
import api from '../lib/api';

const EMPTY = { block_label: 'A', days: ['M', 'W'], start_time: '08:00', end_time: '09:30', room: '', professor: '', capacity: 40 };
const DAY_OPTIONS = ['M', 'T', 'W', 'Th', 'F', 'Sat', 'Sun'];

export default function SectionEditor({ subject, schoolYear, onChanged }) {
    const [draft, setDraft] = useState(null); // null | {..section fields, id?}
    const [error, setError] = useState(null);

    const save = () => {
        setError(null);
        const payload = { ...draft, subject_id: subject.id, school_year: schoolYear, capacity: Number(draft.capacity) };
        const req = draft.id ? api.put(`/admin/sections/${draft.id}`, payload) : api.post('/admin/sections', payload);
        req.then(() => { setDraft(null); onChanged(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the section fields and try again.'));
    };

    const remove = (id) => {
        setError(null);
        api.delete(`/admin/sections/${id}`)
            .then(onChanged)
            .catch((err) => setError(err.response?.data?.message ?? 'Delete failed.'));
    };

    const toggleDay = (day) => setDraft((d) => ({
        ...d,
        days: d.days.includes(day) ? d.days.filter((x) => x !== day) : [...d.days, day],
    }));

    return (
        <div className="mt-3 pl-4 border-l-2 border-slate-100 dark:border-slate-800">
            {subject.sections.map((s) => (
                <div key={s.id} className="flex items-center justify-between text-xs py-1.5 border-b border-slate-50 dark:border-slate-800">
                    <span>
                        <span className="font-semibold">Block {s.block_label}</span> · {s.days.join('/')} {s.start_time}–{s.end_time} · {s.room} · {s.professor}
                        <span className="text-slate-400"> · {s.enrolled_count}/{s.capacity} enrolled</span>
                    </span>
                    <span className="flex gap-2">
                        <button onClick={() => setDraft({ ...s })} className="text-brandNavy dark:text-slate-300 hover:underline">Edit</button>
                        <button onClick={() => remove(s.id)} className="text-red-600 hover:underline">Delete</button>
                    </span>
                </div>
            ))}

            {draft ? (
                <div className="mt-2 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 space-y-2 text-xs">
                    <div className="flex flex-wrap gap-2">
                        <input value={draft.block_label} onChange={(e) => setDraft({ ...draft, block_label: e.target.value })}
                            placeholder="Block" className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="time" value={draft.start_time} onChange={(e) => setDraft({ ...draft, start_time: e.target.value })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="time" value={draft.end_time} onChange={(e) => setDraft({ ...draft, end_time: e.target.value })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input value={draft.room} onChange={(e) => setDraft({ ...draft, room: e.target.value })}
                            placeholder="Room" className="w-24 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input value={draft.professor} onChange={(e) => setDraft({ ...draft, professor: e.target.value })}
                            placeholder="Professor" className="w-36 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="number" value={draft.capacity} onChange={(e) => setDraft({ ...draft, capacity: e.target.value })}
                            placeholder="Cap" className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                    </div>
                    <div className="flex gap-1">
                        {DAY_OPTIONS.map((day) => (
                            <button key={day} onClick={() => toggleDay(day)}
                                className={`px-2 py-1 rounded ${draft.days.includes(day) ? 'bg-brandNavy text-white' : 'bg-slate-200 dark:bg-slate-700'}`}>
                                {day}
                            </button>
                        ))}
                    </div>
                    {error && <p className="text-red-600">{error}</p>}
                    <div className="flex gap-2">
                        <button onClick={save} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save Section</button>
                        <button onClick={() => setDraft(null)} className="px-3 py-1.5 rounded bg-slate-200 dark:bg-slate-700">Cancel</button>
                    </div>
                </div>
            ) : (
                <div>
                    {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
                    <button onClick={() => setDraft({ ...EMPTY })} className="mt-2 text-xs text-brandGreen font-semibold hover:underline">
                        + Add Section
                    </button>
                </div>
            )}
        </div>
    );
}
```

`resources/js/curriculum/SubjectRow.jsx`:

```jsx
import React, { useState } from 'react';
import api from '../lib/api';
import SectionEditor from './SectionEditor';

export default function SubjectRow({ subject, allSubjects, schoolYear, onChanged }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(null);
    const [error, setError] = useState(null);

    const startEdit = () => {
        setDraft({
            code: subject.code, title: subject.title, units: subject.units,
            year_level: subject.year_level, semester: subject.semester, mode: subject.mode,
            prerequisite_ids: subject.prerequisite_ids ?? [],
        });
        setEditing(true);
    };

    const save = () => {
        setError(null);
        api.put(`/admin/subjects/${subject.id}`, { ...draft, units: Number(draft.units) })
            .then(() => { setEditing(false); onChanged(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the fields and try again.'));
    };

    const remove = () => {
        setError(null);
        api.delete(`/admin/subjects/${subject.id}`)
            .then(onChanged)
            .catch((err) => setError(err.response?.data?.message ?? 'Delete failed.'));
    };

    const prereqCodes = (subject.prerequisite_ids ?? [])
        .map((id) => allSubjects.find((s) => s.id === id)?.code)
        .filter(Boolean);

    return (
        <div className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
            <div className="flex items-center justify-between flex-wrap gap-2">
                {editing ? (
                    <div className="flex flex-wrap gap-2 text-xs w-full">
                        <input value={draft.code} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                            className="w-24 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input value={draft.title} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                            className="flex-1 min-w-48 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="number" value={draft.units} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                            className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <select value={draft.mode} onChange={(e) => setDraft({ ...draft, mode: e.target.value })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <option>F2F</option><option>Online</option>
                        </select>
                        <select multiple value={draft.prerequisite_ids.map(String)}
                            onChange={(e) => setDraft({ ...draft, prerequisite_ids: [...e.target.selectedOptions].map((o) => Number(o.value)) })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800 min-w-40" size={3}>
                            {allSubjects.filter((s) => s.id !== subject.id).map((s) => (
                                <option key={s.id} value={s.id}>{s.code} (prereq)</option>
                            ))}
                        </select>
                        <button onClick={save} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save</button>
                        <button onClick={() => setEditing(false)} className="px-3 py-1.5 rounded bg-slate-200 dark:bg-slate-700">Cancel</button>
                        {error && <p className="w-full text-red-600">{error}</p>}
                    </div>
                ) : (
                    <>
                        <p className="text-sm font-semibold text-brandNavy dark:text-slate-100">
                            <span className="font-mono">{subject.code}</span> — {subject.title}
                            <span className="ml-2 text-xs text-slate-400">
                                {subject.units} units · {subject.mode}
                                {prereqCodes.length > 0 && <> · requires {prereqCodes.join(', ')}</>}
                            </span>
                        </p>
                        <span className="flex gap-3 text-xs">
                            <button onClick={() => setOpen(!open)} className="text-brandNavy dark:text-slate-300 hover:underline">
                                {open ? 'Hide' : 'Show'} sections ({subject.sections.length})
                            </button>
                            <button onClick={startEdit} className="text-brandNavy dark:text-slate-300 hover:underline">Edit</button>
                            <button onClick={remove} className="text-red-600 hover:underline">Delete</button>
                        </span>
                        {error && <p className="w-full text-xs text-red-600">{error}</p>}
                    </>
                )}
            </div>
            {open && <SectionEditor subject={subject} schoolYear={schoolYear} onChanged={onChanged} />}
        </div>
    );
}
```

Replace `resources/js/curriculum-app.jsx` with:

```jsx
import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import api from './lib/api';
import SubjectRow from './curriculum/SubjectRow';

const SCHOOL_YEAR = '2026-2027'; // matches Setting school_year; sections are created for this term

function CurriculumApp() {
    const [programs, setPrograms] = useState([]);
    const [active, setActive] = useState(null);
    const [subjects, setSubjects] = useState([]);
    const [adding, setAdding] = useState(false);
    const [draft, setDraft] = useState(null);
    const [error, setError] = useState(null);
    const [yearFilter, setYearFilter] = useState(1);
    const [semFilter, setSemFilter] = useState(1);

    useEffect(() => {
        api.get('/admin/programs').then((res) => {
            const enrollable = res.data.programs.filter((p) => p.is_enrollable);
            setPrograms(enrollable);
            if (enrollable.length > 0) setActive(enrollable[0]);
        });
    }, []);

    const loadSubjects = useCallback(() => {
        if (!active) return;
        api.get(`/admin/programs/${active.id}/subjects`).then((res) => setSubjects(res.data.subjects));
    }, [active]);

    useEffect(loadSubjects, [loadSubjects]);

    const createSubject = () => {
        setError(null);
        api.post('/admin/subjects', {
            ...draft, program_id: active.id, units: Number(draft.units),
            year_level: yearFilter, semester: semFilter, prerequisite_ids: [],
        }).then(() => { setAdding(false); setDraft(null); loadSubjects(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the fields and try again.'));
    };

    const visible = subjects.filter((s) => s.year_level === yearFilter && s.semester === semFilter);

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
                {programs.map((p) => (
                    <button key={p.id} onClick={() => setActive(p)}
                        className={`px-4 py-2 rounded-lg text-sm font-semibold
                            ${active?.id === p.id ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        {p.code}
                    </button>
                ))}
            </div>

            {active && (
                <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
                    <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">{active.name}</h2>
                    <div className="flex flex-wrap gap-2 my-3 text-xs">
                        {[...Array(active.years ?? 4)].map((_, i) => (
                            <button key={i} onClick={() => setYearFilter(i + 1)}
                                className={`px-3 py-1.5 rounded ${yearFilter === i + 1 ? 'bg-brandGreen text-white' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                Year {i + 1}
                            </button>
                        ))}
                        {[1, 2].map((sem) => (
                            <button key={sem} onClick={() => setSemFilter(sem)}
                                className={`px-3 py-1.5 rounded ${semFilter === sem ? 'bg-brandGold text-white' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                Sem {sem}
                            </button>
                        ))}
                    </div>

                    {visible.map((subject) => (
                        <SubjectRow key={subject.id} subject={subject} allSubjects={subjects}
                            schoolYear={SCHOOL_YEAR} onChanged={loadSubjects} />
                    ))}

                    {adding ? (
                        <div className="flex flex-wrap gap-2 text-xs mt-2">
                            <input placeholder="Code" value={draft?.code ?? ''} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                                className="w-24 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <input placeholder="Title" value={draft?.title ?? ''} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                                className="flex-1 min-w-48 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <input type="number" placeholder="Units" value={draft?.units ?? 3} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                                className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <select value={draft?.mode ?? 'F2F'} onChange={(e) => setDraft({ ...draft, mode: e.target.value })}
                                className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                                <option>F2F</option><option>Online</option>
                            </select>
                            <button onClick={createSubject} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Add</button>
                            <button onClick={() => setAdding(false)} className="px-3 py-1.5 rounded bg-slate-200 dark:bg-slate-700">Cancel</button>
                            {error && <p className="w-full text-red-600">{error}</p>}
                        </div>
                    ) : (
                        <button onClick={() => { setAdding(true); setDraft({ code: '', title: '', units: 3, mode: 'F2F' }); }}
                            className="mt-2 text-sm text-brandGreen font-semibold hover:underline">
                            + Add Subject to Year {yearFilter}, Sem {semFilter}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

const el = document.getElementById('curriculum-root');
if (el) createRoot(el).render(<CurriculumApp />);
```

- [ ] **Step 2: Convert the Blade page to a shell**

In `resources/views/admin/curriculum.blade.php`: keep the layout/sidebar/header, delete the `const CATEGORIES = {...}`/`const SUBJECTS = {...}` script block and the static editor markup it renders into, insert `<div id="curriculum-root"></div>` in the content area, and add before `</body>`:

```blade
    @viteReactRefresh
    @vite('resources/js/curriculum-app.jsx')
```

- [ ] **Step 3: Build and verify manually**

Run: `npm run build`
Expected: success.

Log in as admin (`admin`), open `/admin/curriculum`.
Expected: program tabs (BOM, FSM, BSOA, BTVTED), seeded subjects listed per year/sem, add/edit/delete subject works, sections expand and are editable, deleting an enrolled section shows the guard message.

- [ ] **Step 4: Run the full test suite**

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/curriculum-app.jsx resources/js/curriculum resources/views/admin/curriculum.blade.php
git commit -m "feat: React curriculum editor with real CRUD over subjects and sections"
```

---

### Task 15: Downstream wiring — COR/QR schedule, notif bell, admin program select

**Files:**
- Modify: `app/Http/Controllers/AuthController.php` (`showCor`, around line 255)
- Modify: `resources/views/partials/notif-script.blade.php`
- Modify: `resources/views/admin/create-student.blade.php` and its route closure in `routes/web.php` (`GET /admin/students/create`)
- Test: `tests/Feature/CorEnrollmentTest.php`

**Interfaces:**
- Consumes: `Enrollment` with `sections.subject`.
- Produces: `showCor` builds `$subjects` from the student's latest `enrolled` enrollment (same array keys the `schedule` view already expects: `code, desc, units, days, time, room, type, color`); empty array + `$noEnrollment = true` flag when none.

- [ ] **Step 1: Write the failing test**

`tests/Feature/CorEnrollmentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cor_shows_enrolled_sections(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $section = Section::factory()->create(['room' => 'Rm 777', 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach($section->id);

        $response = $this->actingAs($user)->get('/cor');

        $response->assertOk();
        $subjects = $response->viewData('subjects');
        $this->assertCount(1, $subjects);
        $this->assertSame($section->subject->code, $subjects[0]['code']);
        $this->assertSame('Rm 777', $subjects[0]['room']);
        $this->assertSame('M/W', $subjects[0]['days']);
    }

    public function test_cor_without_enrollment_shows_empty_state(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get('/cor');

        $response->assertOk();
        $this->assertSame([], $response->viewData('subjects'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter CorEnrollmentTest`
Expected: FAIL — the first test finds the 9 hardcoded subjects instead of 1.

- [ ] **Step 3: Rewrite showCor**

In `app/Http/Controllers/AuthController.php`, add `use App\Models\Enrollment;` and replace the hardcoded `$subjects` array in `showCor()` with:

```php
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
```

Keep the `return view('schedule', compact('user', 'student', 'subjects'));` line. If the `schedule` view renders poorly with an empty `$subjects`, add a friendly empty state to the view: inside the schedule card, wrap the table/grid in `@if (count($subjects)) ... @else <p class="text-sm text-slate-400">No enrolled subjects yet — complete your enrollment first.</p> @endif`.

- [ ] **Step 4: Add enrollment states to the notif bell**

In `resources/views/partials/notif-script.blade.php`, inside the `if ($authRole === 'student')` block after the `$allCleared` notification, add:

```php
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
```

Also add a chair-side notification: in the same file, find the staff branch that handles `$authRole === 'chair'` (the approver notifications) and add:

```php
        $pendingCount = \App\Models\Enrollment::where('status', 'pending')->count();
        if ($pendingCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-user-graduate', 'color' => '#E2A700', 'title' => 'Enrollments Awaiting Approval', 'desc' => $pendingCount . ' irregular enrollment(s) need your review.', 'time' => 'Action needed'];
        }
```

(If the file has no explicit `chair` branch, add this inside whatever branch renders for the chair/approver role — check how `$authRole` is switched in the file and follow that pattern.)

- [ ] **Step 5: Feed real programs into the admin student form**

In `routes/web.php`, in the `GET /admin/students/create` closure, pass programs to the view (add `use App\Models\Program;` import):

```php
        $programs = Program::orderBy('level')->orderBy('code')->get();
```

and include `'programs' => $programs` in the view data.

In `resources/views/admin/create-student.blade.php`, find the program/major `<select>` (field name `major` — verify in the file; if the form uses a text input or hardcoded `<option>` list, replace the options) with:

```blade
                            @foreach ($programs as $program)
                                <option value="{{ $program->code }}">{{ $program->code }} — {{ $program->name }}{{ $program->is_enrollable ? '' : ' (manual enrollment)' }}</option>
                            @endforeach
```

If the page's JavaScript references the old hardcoded option values, update those references to the program codes.

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter CorEnrollmentTest` → PASS (2 tests).
Run: `php artisan test` → all PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/AuthController.php resources/views/partials/notif-script.blade.php resources/views/admin/create-student.blade.php routes/web.php resources/views/schedule.blade.php tests/Feature/CorEnrollmentTest.php
git commit -m "feat: wire COR, notifications, and admin forms to real enrollment data"
```

---

### Task 16: Final verification and demo data

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php` (demo accounts, only if missing)

- [ ] **Step 1: Full test suite**

Run: `php artisan test`
Expected: all tests PASS. Paste the summary line into the commit/PR notes.

- [ ] **Step 2: Fresh build**

Run: `npm run build`
Expected: success, both React entries in the manifest.

- [ ] **Step 3: Fresh local database with demo users**

Check `database/seeders/DatabaseSeeder.php` — first look at what demo accounts it (or the login page's displayed demo credentials) already relies on and keep those working. Then ensure these exist by adding to `run()` (adjust only if an account with the same `login_id` already exists):

```php
        $regular = User::firstOrCreate(
            ['login_id' => '2300410'],
            ['name' => 'Demo Regular Student', 'email' => 'regular.demo@aitsa.test', 'password' => 'password',
             'role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']
        );
        \App\Models\Clearance::firstOrCreate(
            ['user_id' => $regular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
             'library_status' => 'Approved', 'clinic_status' => 'Approved']
        );

        $irregular = User::firstOrCreate(
            ['login_id' => '2300411'],
            ['name' => 'Demo Irregular Student', 'email' => 'irregular.demo@aitsa.test', 'password' => 'password',
             'role' => 'student', 'major' => 'BSOA', 'year_level' => '2nd Year']
        );
        \App\Models\Clearance::firstOrCreate(
            ['user_id' => $irregular->id],
            ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
             'library_status' => 'Approved', 'clinic_status' => 'Approved']
        );
        \App\Models\StudentGrade::firstOrCreate(
            ['user_id' => $irregular->id, 'subject_code' => 'BSOA1131'],
            ['status' => 'Failed', 'final_grade' => '5.00']
        );

        foreach ([
            ['login_id' => 'chair',     'name' => 'Demo Dept. Chair', 'role' => 'chair'],
            ['login_id' => 'admin',     'name' => 'Demo Admin',       'role' => 'admin'],
            ['login_id' => 'registrar', 'name' => 'Demo Registrar',   'role' => 'registrar'],
            ['login_id' => 'cashier',   'name' => 'Demo Cashier',     'role' => 'cashier'],
        ] as $staff) {
            User::firstOrCreate(
                ['login_id' => $staff['login_id']],
                ['name' => $staff['name'], 'email' => $staff['login_id'] . '@aitsa.test',
                 'password' => $staff['login_id'] === 'admin' ? 'admin123' : 'password', 'role' => $staff['role']]
            );
        }
```

(Passwords hash automatically via the User model's `hashed` cast. If existing seeders already create any of these roles, keep theirs and skip the duplicate here.)

Run: `php artisan migrate:fresh --seed`
Expected: no errors.

- [ ] **Step 4: Manual demo walkthrough (the flow panelists will see)**

1. Log in as the regular student → `/enrollment` shows Block A preview → Confirm → status "Officially Enrolled" → `/cor` shows the real block subjects with QR.
2. Log in as the irregular student → `/enrollment` shows the picker with prereq locks → pick two non-conflicting sections → submit → status "Awaiting Department Chair Approval".
3. Log in as chair → dashboard shows the pending enrollment with subject table → Reject with remarks → student sees remarks and can revise & resubmit → Approve → student is enrolled.
4. Log in as admin → `/admin/curriculum` → edit a subject title, add a section, try deleting an enrolled section (guard message shows).
5. Check `/admin/audit` — enrollment commits, approvals, and curriculum edits are all logged.

- [ ] **Step 5: Commit any demo-data changes**

```bash
git add database/seeders/DatabaseSeeder.php
git commit -m "chore: demo accounts for enrollment walkthrough"
```
