# Change of Matriculation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an enrolled student request add/drop/swap changes to their committed subject load during an admin-controlled window; the Department Chair approves (deltas applied atomically) or rejects with remarks.

**Architecture:** New `matriculation_changes` + `matriculation_change_items` tables hold explicit delta requests. `App\Services\MatriculationChangeService` (same shape as `EnrollmentService`) validates on submit and re-validates inside the approval transaction with section row locks. Student UI lives inside the existing enrollment React island; chair UI is a second queue card on the approver Blade dashboard; a `settings` row gates the window.

**Tech Stack:** PHP 8.2 / Laravel 12, MySQL (SQLite `:memory:` for tests), React 18 + Vite 7, Axios, Tailwind CDN on Blade pages.

**Spec:** `docs/superpowers/specs/2026-07-12-change-of-matriculation-design.md`

## Global Constraints

- Current term comes from `Setting::get('school_year', '2026-2027')` and `Setting::get('semester', '1')` — always via `EnrollmentService::currentTerm()`.
- Window setting key: `change_matriculation_open`, values `'1'` / `'0'`, default `'0'` (closed). Read via `Setting::get('change_matriculation_open', '0') === '1'`.
- Business-rule failures throw `App\Exceptions\EnrollmentException` (existing class; constructor `(string $message, int $status = 409)`); API controllers convert to JSON `{"message": ...}` with that status. Malformed payloads use Laravel-standard 422 validation.
- Statuses: `pending` → `approved` | `rejected`. At most one `pending` request per enrollment (enforced in service).
- Item semantics: `add` → `section_id` = new section, `replaced_section_id` null; `drop` → `section_id` = currently enrolled section to remove; `swap` → `section_id` = new section, `replaced_section_id` = currently enrolled section of the same subject.
- Every state change calls `AuditLog::record(string $action, string $description, string $targetType = null, int $targetId = null)` with actions **"Matriculation Change Submitted" / "Matriculation Change Approved" / "Matriculation Change Rejected" / "Change Matriculation Window Opened|Closed"**.
- Roles via existing `role:` middleware alias; API routes use `auth:sanctum` (stateful SPA cookies), chair actions are web routes.
- Blade/React keep brand colors (`brandNavy #0B3C5D`, `brandGreen #1D7A46`, `brandGold #E2A700`) and dark-mode conventions.
- Tests: PHPUnit, `RefreshDatabase`, SQLite `:memory:` (configured in `phpunit.xml`). Run with `php artisan test`. The pre-existing 41 tests must stay green.
- Section times are `HH:MM` 24-hour strings; overlap check is `Section::overlaps(Section $other)` (already exists).
- Out of scope: fees/refunds, printable form, email, registrar involvement, editing/cancelling a pending request.

---

### Task 1: Schema and models for matriculation changes

**Files:**
- Create: `database/migrations/2026_07_12_000001_create_matriculation_changes_table.php`
- Create: `database/migrations/2026_07_12_000002_create_matriculation_change_items_table.php`
- Create: `app/Models/MatriculationChange.php`
- Create: `app/Models/MatriculationChangeItem.php`
- Test: `tests/Unit/MatriculationSchemaTest.php`

**Interfaces:**
- Produces: `MatriculationChange` model (fillable `enrollment_id, user_id, status, remarks`; relations `enrollment(): BelongsTo`, `user(): BelongsTo`, `items(): HasMany`) and `MatriculationChangeItem` (fillable `matriculation_change_id, action, section_id, replaced_section_id`; relations `change(): BelongsTo`, `section(): BelongsTo`, `replacedSection(): BelongsTo` to `Section` via `replaced_section_id`). Later tasks rely on these exact names.

- [ ] **Step 1: Write the failing test**

`tests/Unit/MatriculationSchemaTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_with_items_and_relations(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id]);
        $old = Section::factory()->create();
        $new = Section::factory()->create(['subject_id' => $old->subject_id, 'block_label' => 'B']);

        $change = MatriculationChange::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        $change->items()->create([
            'action' => 'swap',
            'section_id' => $new->id,
            'replaced_section_id' => $old->id,
        ]);

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($user->id, $change->user->id);
        $this->assertSame($enrollment->id, $change->enrollment->id);

        $item = $change->items()->first();
        $this->assertSame('swap', $item->action);
        $this->assertSame($new->id, $item->section->id);
        $this->assertSame($old->id, $item->replacedSection->id);
        $this->assertSame($change->id, $item->change->id);
    }

    public function test_deleting_change_cascades_items(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id]);
        $section = Section::factory()->create();

        $change = MatriculationChange::create([
            'enrollment_id' => $enrollment->id, 'user_id' => $user->id, 'status' => 'pending',
        ]);
        $change->items()->create(['action' => 'add', 'section_id' => $section->id]);

        $change->delete();

        $this->assertDatabaseCount('matriculation_change_items', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MatriculationSchemaTest`
Expected: FAIL — class `App\Models\MatriculationChange` not found.

- [ ] **Step 3: Create the migrations**

`database/migrations/2026_07_12_000001_create_matriculation_changes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculation_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculation_changes');
    }
};
```

`database/migrations/2026_07_12_000002_create_matriculation_change_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculation_change_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matriculation_change_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // add | drop | swap
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('replaced_section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculation_change_items');
    }
};
```

- [ ] **Step 4: Create the models**

`app/Models/MatriculationChange.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatriculationChange extends Model
{
    protected $fillable = ['enrollment_id', 'user_id', 'status', 'remarks'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MatriculationChangeItem::class);
    }
}
```

`app/Models/MatriculationChangeItem.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatriculationChangeItem extends Model
{
    protected $fillable = ['matriculation_change_id', 'action', 'section_id', 'replaced_section_id'];

    public function change(): BelongsTo
    {
        return $this->belongsTo(MatriculationChange::class, 'matriculation_change_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function replacedSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'replaced_section_id');
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter MatriculationSchemaTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_12_000001_create_matriculation_changes_table.php database/migrations/2026_07_12_000002_create_matriculation_change_items_table.php app/Models/MatriculationChange.php app/Models/MatriculationChangeItem.php tests/Unit/MatriculationSchemaTest.php
git commit -m "feat: matriculation change schema and models"
```

---

### Task 2: MatriculationChangeService — submit with full validation

**Files:**
- Create: `app/Services/MatriculationChangeService.php`
- Test: `tests/Feature/MatriculationSubmitTest.php`

**Interfaces:**
- Consumes: `EnrollmentService::currentTerm()/activeEnrollment(User)`, `Setting::get/put`, `Section::hasSeats()/overlaps()`, `User::program()/passedSubjectCodes()`, Task 1 models.
- Produces: `MatriculationChangeService` with:
  - `windowOpen(): bool`
  - `latestRequestFor(Enrollment $enrollment): ?MatriculationChange`
  - `submit(User $user, array $items): MatriculationChange` — `$items` is `[['action' => 'add|drop|swap', 'section_id' => int, 'replaced_section_id' => int|null], ...]`; throws `EnrollmentException` on any rule failure.
  - (Task 3 adds `approve` / `reject`.)

- [ ] **Step 1: Write the failing tests**

`tests/Feature/MatriculationSubmitTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationSubmitTest extends TestCase
{
    use RefreshDatabase;

    private MatriculationChangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MatriculationChangeService::class);
    }

    /**
     * Enrolled student in program BSOA with two non-conflicting sections:
     * SUBJ-A (M/W 08:00–09:30) and SUBJ-B (T/Th 08:00–09:30).
     *
     * @return array{0: User, 1: Enrollment, 2: Section, 3: Section}
     */
    private function enrolledStudent(): array
    {
        Setting::put('change_matriculation_open', '1');

        $program = Program::factory()->create(['code' => 'BSOA', 'is_enrollable' => true]);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);

        $subjectA = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-A']);
        $subjectB = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-B']);

        $secA = Section::factory()->create(['subject_id' => $subjectA->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $secB = Section::factory()->create(['subject_id' => $subjectB->id, 'days' => ['T', 'Th'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach([$secA->id, $secB->id]);

        return [$user, $enrollment, $secA, $secB];
    }

    /** New addable subject in the same program with one section. */
    private function addableSection(array $days = ['F'], string $start = '10:00', string $end = '11:30', int $capacity = 40): Section
    {
        $program = Program::where('code', 'BSOA')->firstOrFail();
        $subject = Subject::factory()->create(['program_id' => $program->id]);

        return Section::factory()->create([
            'subject_id' => $subject->id, 'days' => $days,
            'start_time' => $start, 'end_time' => $end, 'capacity' => $capacity,
        ]);
    }

    public function test_window_closed_blocks_submit(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        Setting::put('change_matriculation_open', '0');

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('window is closed');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
    }

    public function test_must_be_enrolled(): void
    {
        [$user, $enrollment, $secA] = $this->enrolledStudent();
        $enrollment->update(['status' => 'pending']);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('officially enrolled');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
    }

    public function test_only_one_pending_request(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('pending change request');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
    }

    public function test_add_conflicting_section_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $clash = $this->addableSection(['M', 'W'], '09:00', '10:00'); // overlaps SUBJ-A

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('Schedule conflict');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $clash->id]]);
    }

    public function test_add_full_section_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $full = $this->addableSection(['F'], '10:00', '11:30', 1);
        $other = Enrollment::factory()->create(['status' => 'enrolled']);
        $other->sections()->attach($full->id);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('no seats left');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $full->id]]);
    }

    public function test_add_missing_prerequisite_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $section = $this->addableSection();
        $prereq = Subject::factory()->create(['program_id' => $section->subject->program_id, 'code' => 'PRE-1']);
        $section->subject->prerequisites()->attach($prereq->id);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('requires: PRE-1');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $section->id]]);
    }

    public function test_add_already_passed_subject_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $section = $this->addableSection();
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => $section->subject->code, 'status' => 'Passed', 'final_grade' => '1.50']);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('already passed');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $section->id]]);
    }

    public function test_drop_below_one_subject_rejected(): void
    {
        [$user, , $secA, $secB] = $this->enrolledStudent();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('at least one subject');
        $this->service->submit($user, [
            ['action' => 'drop', 'section_id' => $secA->id],
            ['action' => 'drop', 'section_id' => $secB->id],
        ]);
    }

    public function test_drop_of_section_not_enrolled_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $foreign = $this->addableSection();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('not enrolled');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $foreign->id]]);
    }

    public function test_swap_to_different_subject_rejected(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $otherSubject = $this->addableSection();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('same subject');
        $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $otherSubject->id, 'replaced_section_id' => $secA->id],
        ]);
    }

    public function test_items_checked_against_combined_result(): void
    {
        [$user] = $this->enrolledStudent();
        $addOne = $this->addableSection(['F'], '10:00', '11:30');
        $addTwo = $this->addableSection(['F'], '11:00', '12:30'); // conflicts with $addOne, not with current load

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('Schedule conflict');
        $this->service->submit($user, [
            ['action' => 'add', 'section_id' => $addOne->id],
            ['action' => 'add', 'section_id' => $addTwo->id],
        ]);
    }

    public function test_valid_combined_request_goes_pending(): void
    {
        [$user, $enrollment, $secA, $secB] = $this->enrolledStudent();
        $swapTarget = Section::factory()->create([
            'subject_id' => $secA->subject_id, 'block_label' => 'B',
            'days' => ['M', 'W'], 'start_time' => '13:00', 'end_time' => '14:30',
        ]);
        $addSection = $this->addableSection(['F'], '10:00', '11:30');

        $change = $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $swapTarget->id, 'replaced_section_id' => $secA->id],
            ['action' => 'drop', 'section_id' => $secB->id],
            ['action' => 'add', 'section_id' => $addSection->id],
        ]);

        $this->assertSame('pending', $change->status);
        $this->assertCount(3, $change->items);
        // Enrollment untouched until approval:
        $this->assertEqualsCanonicalizing([$secA->id, $secB->id], $enrollment->sections()->pluck('sections.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Matriculation Change Submitted']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter MatriculationSubmitTest`
Expected: FAIL — class `App\Services\MatriculationChangeService` not found.

- [ ] **Step 3: Implement the service (submit + validation)**

`app/Services/MatriculationChangeService.php`:

```php
<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatriculationChangeService
{
    public function __construct(private readonly EnrollmentService $enrollments)
    {
    }

    public function windowOpen(): bool
    {
        return Setting::get('change_matriculation_open', '0') === '1';
    }

    public function latestRequestFor(Enrollment $enrollment): ?MatriculationChange
    {
        return MatriculationChange::where('enrollment_id', $enrollment->id)->latest('id')->first();
    }

    /** @param array<int, array{action: string, section_id: int, replaced_section_id?: int|null}> $items */
    public function submit(User $user, array $items): MatriculationChange
    {
        if (! $this->windowOpen()) {
            throw new EnrollmentException('The change of matriculation window is closed.');
        }

        $enrollment = $this->enrollments->activeEnrollment($user);
        if (! $enrollment || $enrollment->status !== 'enrolled') {
            throw new EnrollmentException('You must be officially enrolled before requesting a change of matriculation.');
        }

        if (MatriculationChange::where('enrollment_id', $enrollment->id)->where('status', 'pending')->exists()) {
            throw new EnrollmentException('You already have a pending change request. Wait for the Department Chair to act on it.');
        }

        return DB::transaction(function () use ($user, $enrollment, $items) {
            $this->validate($user, $enrollment, $items);

            $change = MatriculationChange::create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
            foreach ($items as $item) {
                $change->items()->create([
                    'action' => $item['action'],
                    'section_id' => $item['section_id'],
                    'replaced_section_id' => $item['replaced_section_id'] ?? null,
                ]);
            }

            AuditLog::record(
                'Matriculation Change Submitted',
                sprintf('%s (%s) requested %d change(s) to their enrollment; awaiting Department Chair approval.', $user->name, $user->login_id, count($items)),
                'MatriculationChange',
                $change->id
            );

            return $change->load('items.section.subject', 'items.replacedSection.subject');
        });
    }

    /**
     * Validate items against the enrollment's resulting schedule.
     * Returns [$attach, $detach] section-id collections for approval to apply.
     *
     * @param array<int, array{action: string, section_id: int, replaced_section_id?: int|null}> $items
     * @return array{0: Collection<int, int>, 1: Collection<int, int>}
     */
    private function validate(User $user, Enrollment $enrollment, array $items): array
    {
        if (count($items) < 1 || count($items) > 10) {
            throw new EnrollmentException('A change request must contain between 1 and 10 items.', 422);
        }

        $term = $this->enrollments->currentTerm();
        $program = $user->program();
        $passed = $user->passedSubjectCodes();

        $targets = Section::whereIn('id', collect($items)->pluck('section_id'))
            ->with('subject.prerequisites')->get()->keyBy('id');

        $detach = collect();
        $attach = collect();
        $resulting = $enrollment->sections()->with('subject')->get()->keyBy('id');

        foreach ($items as $item) {
            $target = $targets->get($item['section_id']);
            if (! $target) {
                throw new EnrollmentException('One of the selected sections no longer exists.', 422);
            }

            if ($item['action'] === 'drop') {
                if (! $resulting->has($target->id)) {
                    throw new EnrollmentException("You are not enrolled in the {$target->subject->code} section you are trying to drop.", 422);
                }
                $resulting->forget($target->id);
                $detach->push($target->id);
                continue;
            }

            if ($item['action'] === 'swap') {
                $replaced = $resulting->get($item['replaced_section_id'] ?? 0);
                if (! $replaced) {
                    throw new EnrollmentException('The section you are trying to swap out is not part of your enrollment.', 422);
                }
                if ($replaced->subject_id !== $target->subject_id) {
                    throw new EnrollmentException('You can only swap to another section of the same subject.', 422);
                }
                if ($replaced->id === $target->id) {
                    throw new EnrollmentException('You are already enrolled in that section.', 422);
                }
                $resulting->forget($replaced->id);
                $detach->push($replaced->id);
            }

            // From here $item['action'] is add or swap: $target joins the schedule.
            $subject = $target->subject;
            if ($target->school_year !== $term['school_year'] || $subject->semester !== $term['semester'] || $subject->program_id !== $program?->id) {
                throw new EnrollmentException("Section for {$subject->code} is not offered to your program this term.", 422);
            }
            if ($resulting->contains(fn (Section $s) => $s->subject_id === $subject->id)) {
                throw new EnrollmentException("You already have {$subject->code} in your schedule.");
            }
            if ($item['action'] === 'add') {
                if (in_array($subject->code, $passed, true)) {
                    throw new EnrollmentException("You have already passed {$subject->code}.");
                }
                $missing = $subject->prerequisites->pluck('code')->diff($passed);
                if ($missing->isNotEmpty()) {
                    throw new EnrollmentException("{$subject->code} requires: " . $missing->implode(', ') . '.');
                }
            }
            if (! $target->hasSeats()) {
                throw new EnrollmentException("The {$subject->code} section you picked has no seats left.");
            }
            foreach ($resulting as $existing) {
                if ($target->overlaps($existing)) {
                    throw new EnrollmentException("Schedule conflict: {$subject->code} overlaps with {$existing->subject->code}.");
                }
            }

            $resulting->put($target->id, $target);
            $attach->push($target->id);
        }

        if ($detach->duplicates()->isNotEmpty() || $attach->duplicates()->isNotEmpty()) {
            throw new EnrollmentException('Your request references the same section more than once.', 422);
        }

        if ($resulting->isEmpty()) {
            throw new EnrollmentException('You cannot drop your entire subject load. Keep at least one subject.');
        }

        return [$attach, $detach];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter MatriculationSubmitTest`
Expected: PASS (12 tests).

- [ ] **Step 5: Run the whole suite**

Run: `php artisan test`
Expected: all PASS (41 pre-existing + new).

- [ ] **Step 6: Commit**

```bash
git add app/Services/MatriculationChangeService.php tests/Feature/MatriculationSubmitTest.php
git commit -m "feat: matriculation change submission with delta validation"
```

---

### Task 3: MatriculationChangeService — approve and reject

**Files:**
- Modify: `app/Services/MatriculationChangeService.php` (add two public methods)
- Test: `tests/Feature/MatriculationApprovalTest.php`

**Interfaces:**
- Consumes: Task 2 service (`submit`, private `validate` returning `[$attach, $detach]`).
- Produces: `approve(MatriculationChange $change): void` (applies deltas atomically, re-validates, throws `EnrollmentException` on drift or non-pending) and `reject(MatriculationChange $change, string $remarks): void`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/MatriculationApprovalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private MatriculationChangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MatriculationChangeService::class);
    }

    /** @return array{0: User, 1: Enrollment, 2: Section, 3: Section} */
    private function enrolledStudent(): array
    {
        Setting::put('change_matriculation_open', '1');

        $program = Program::factory()->create(['code' => 'BSOA', 'is_enrollable' => true]);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);

        $subjectA = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-A']);
        $subjectB = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-B']);

        $secA = Section::factory()->create(['subject_id' => $subjectA->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $secB = Section::factory()->create(['subject_id' => $subjectB->id, 'days' => ['T', 'Th'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach([$secA->id, $secB->id]);

        return [$user, $enrollment, $secA, $secB];
    }

    public function test_approve_applies_deltas_and_audits(): void
    {
        [$user, $enrollment, $secA, $secB] = $this->enrolledStudent();
        $swapTarget = Section::factory()->create([
            'subject_id' => $secA->subject_id, 'block_label' => 'B',
            'days' => ['M', 'W'], 'start_time' => '13:00', 'end_time' => '14:30',
        ]);

        $change = $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $swapTarget->id, 'replaced_section_id' => $secA->id],
            ['action' => 'drop', 'section_id' => $secB->id],
        ]);

        $this->service->approve($change);

        $this->assertSame('approved', $change->fresh()->status);
        $this->assertEqualsCanonicalizing([$swapTarget->id], $enrollment->sections()->pluck('sections.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Matriculation Change Approved']);
    }

    public function test_approve_fails_when_target_seat_disappeared(): void
    {
        [$user, $enrollment, $secA] = $this->enrolledStudent();
        $swapTarget = Section::factory()->create([
            'subject_id' => $secA->subject_id, 'block_label' => 'B', 'capacity' => 1,
            'days' => ['M', 'W'], 'start_time' => '13:00', 'end_time' => '14:30',
        ]);

        $change = $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $swapTarget->id, 'replaced_section_id' => $secA->id],
        ]);

        // Someone else takes the last seat after submit:
        $other = Enrollment::factory()->create(['status' => 'enrolled']);
        $other->sections()->attach($swapTarget->id);

        try {
            $this->service->approve($change);
            $this->fail('Expected EnrollmentException');
        } catch (EnrollmentException $e) {
            $this->assertStringContainsString('no seats left', $e->getMessage());
        }

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertTrue($enrollment->sections()->pluck('sections.id')->contains($secA->id));
    }

    public function test_reject_stores_remarks(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $change = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);

        $this->service->reject($change, 'Load must stay at 2 subjects.');

        $fresh = $change->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Load must stay at 2 subjects.', $fresh->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Matriculation Change Rejected']);
    }

    public function test_acting_on_non_pending_request_fails(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $change = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
        $this->service->reject($change, 'No.');

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('no longer pending');
        $this->service->approve($change->fresh());
    }

    public function test_student_can_refile_after_rejection(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $first = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
        $this->service->reject($first, 'Try again.');

        $second = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);

        $this->assertSame('pending', $second->status);
        $this->assertNotSame($first->id, $second->id);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter MatriculationApprovalTest`
Expected: FAIL — call to undefined method `approve`.

- [ ] **Step 3: Add approve/reject to the service**

Append these two public methods to `app/Services/MatriculationChangeService.php` (after `submit`, before `validate`):

```php
    public function approve(MatriculationChange $change): void
    {
        if ($change->status !== 'pending') {
            throw new EnrollmentException('This change request is no longer pending.');
        }

        DB::transaction(function () use ($change) {
            $items = $change->items()->get()->map(fn ($i) => [
                'action' => $i->action,
                'section_id' => $i->section_id,
                'replaced_section_id' => $i->replaced_section_id,
            ])->all();

            // Hold the target section rows so seat counts cannot drift mid-approval.
            $targetIds = collect($items)->whereIn('action', ['add', 'swap'])->pluck('section_id');
            Section::whereIn('id', $targetIds)->lockForUpdate()->get();

            $enrollment = $change->enrollment;
            [$attach, $detach] = $this->validate($change->user, $enrollment, $items);

            if ($detach->isNotEmpty()) {
                $enrollment->sections()->detach($detach->all());
            }
            if ($attach->isNotEmpty()) {
                $enrollment->sections()->attach($attach->all());
            }

            $change->update(['status' => 'approved']);
        });

        AuditLog::record(
            'Matriculation Change Approved',
            'Department Chair approved change of matriculation for ' . ($change->user->name ?? 'ID ' . $change->user_id) . ' (' . ($change->user->login_id ?? 'N/A') . ').',
            'MatriculationChange',
            $change->id
        );
    }

    public function reject(MatriculationChange $change, string $remarks): void
    {
        if ($change->status !== 'pending') {
            throw new EnrollmentException('This change request is no longer pending.');
        }

        $change->update(['status' => 'rejected', 'remarks' => $remarks]);

        AuditLog::record(
            'Matriculation Change Rejected',
            'Department Chair rejected change of matriculation for ' . ($change->user->name ?? 'ID ' . $change->user_id) . ': ' . $remarks,
            'MatriculationChange',
            $change->id
        );
    }
```

Note: `approve` deliberately skips the window check — the chair may act after the window closes (spec).

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter MatriculationApprovalTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Run the whole suite**

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/MatriculationChangeService.php tests/Feature/MatriculationApprovalTest.php
git commit -m "feat: matriculation change approval and rejection with re-validation"
```

---

### Task 4: Student API — context and submit endpoints

**Files:**
- Create: `app/Http/Controllers/Api/MatriculationController.php`
- Modify: `routes/api.php` (student group)
- Test: `tests/Feature/Api/MatriculationApiTest.php`

**Interfaces:**
- Consumes: `MatriculationChangeService` (Tasks 2–3), `EnrollmentService::activeEnrollment/catalogueFor`.
- Produces:
  - `GET /api/matriculation/context` → `{ window_open: bool, enrollment: {id, status, sections: [...]}|null, request: {id, status, remarks, items: [{action, section, replaced_section|null}]}|null, catalogue: [...]|null }`. Section payload here **includes `id` and `subject_id`** (the builder needs them): `{id, subject_id, code, title, units, block_label, days, start_time, end_time, room, professor}`.
  - `POST /api/matriculation` body `{ items: [{action, section_id, replaced_section_id?}] }` → 201 `{request: ...}` | 409 | 422.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Api/MatriculationApiTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Enrollment, 2: Section, 3: Section} */
    private function enrolledStudent(): array
    {
        Setting::put('change_matriculation_open', '1');

        $program = Program::factory()->create(['code' => 'BSOA', 'is_enrollable' => true]);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);

        $subjectA = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-A']);
        $subjectB = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-B']);

        $secA = Section::factory()->create(['subject_id' => $subjectA->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $secB = Section::factory()->create(['subject_id' => $subjectB->id, 'days' => ['T', 'Th'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach([$secA->id, $secB->id]);

        return [$user, $enrollment, $secA, $secB];
    }

    public function test_context_for_enrolled_student(): void
    {
        [$user, , $secA] = $this->enrolledStudent();

        $response = $this->actingAs($user)->getJson('/api/matriculation/context');

        $response->assertOk()
            ->assertJsonPath('window_open', true)
            ->assertJsonPath('enrollment.status', 'enrolled')
            ->assertJsonPath('request', null)
            ->assertJsonCount(2, 'enrollment.sections');
        $this->assertContains($secA->id, array_column($response->json('enrollment.sections'), 'id'));
        $this->assertIsArray($response->json('catalogue'));
    }

    public function test_context_without_enrollment(): void
    {
        Setting::put('change_matriculation_open', '0');
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA']);

        $response = $this->actingAs($user)->getJson('/api/matriculation/context');

        $response->assertOk()
            ->assertJsonPath('window_open', false)
            ->assertJsonPath('enrollment', null)
            ->assertJsonPath('catalogue', null);
    }

    public function test_submit_drop_via_api(): void
    {
        [$user, , $secA] = $this->enrolledStudent();

        $response = $this->actingAs($user)->postJson('/api/matriculation', [
            'items' => [['action' => 'drop', 'section_id' => $secA->id]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('request.status', 'pending')
            ->assertJsonPath('request.items.0.action', 'drop')
            ->assertJsonPath('request.items.0.section.code', 'SUBJ-A');
    }

    public function test_rule_failure_passes_through_as_409(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        Setting::put('change_matriculation_open', '0');

        $this->actingAs($user)->postJson('/api/matriculation', [
            'items' => [['action' => 'drop', 'section_id' => $secA->id]],
        ])->assertStatus(409)->assertJsonStructure(['message']);
    }

    public function test_malformed_payload_is_422(): void
    {
        [$user] = $this->enrolledStudent();

        $this->actingAs($user)->postJson('/api/matriculation', [
            'items' => [['action' => 'explode', 'section_id' => 1]],
        ])->assertStatus(422);
    }

    public function test_requires_student_role(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)->getJson('/api/matriculation/context')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter MatriculationApiTest`
Expected: FAIL — 404 on `/api/matriculation/context`.

- [ ] **Step 3: Create the controller**

`app/Http/Controllers/Api/MatriculationController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\MatriculationChange;
use App\Models\Section;
use App\Services\EnrollmentService;
use App\Services\MatriculationChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatriculationController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly MatriculationChangeService $service,
    ) {
    }

    public function context(Request $request): JsonResponse
    {
        $user = $request->user();
        $enrollment = $this->enrollments->activeEnrollment($user);
        $enrolled = $enrollment && $enrollment->status === 'enrolled';
        $latest = $enrolled ? $this->service->latestRequestFor($enrollment) : null;

        return response()->json([
            'window_open' => $this->service->windowOpen(),
            'enrollment' => $enrolled ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'sections' => $enrollment->sections()->with('subject')->get()
                    ->map(fn (Section $s) => $this->sectionPayload($s))->values(),
            ] : null,
            'request' => $latest ? $this->requestPayload($latest) : null,
            'catalogue' => $enrolled ? $this->enrollments->catalogueFor($user) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.action' => ['required', 'in:add,drop,swap'],
            'items.*.section_id' => ['required', 'integer'],
            'items.*.replaced_section_id' => ['nullable', 'integer'],
        ]);

        try {
            $change = $this->service->submit($request->user(), $data['items']);
        } catch (EnrollmentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['request' => $this->requestPayload($change)], 201);
    }

    private function requestPayload(MatriculationChange $change): array
    {
        $change->loadMissing('items.section.subject', 'items.replacedSection.subject');

        return [
            'id' => $change->id,
            'status' => $change->status,
            'remarks' => $change->remarks,
            'items' => $change->items->map(fn ($item) => [
                'action' => $item->action,
                'section' => $this->sectionPayload($item->section),
                'replaced_section' => $item->replacedSection ? $this->sectionPayload($item->replacedSection) : null,
            ])->values(),
        ];
    }

    private function sectionPayload(Section $section): array
    {
        return [
            'id' => $section->id,
            'subject_id' => $section->subject_id,
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

- [ ] **Step 4: Register the routes**

In `routes/api.php`, add the import and two routes inside the existing student group:

```php
use App\Http\Controllers\Api\MatriculationController;
```

```php
Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/enrollment/context', [EnrollmentController::class, 'context']);
    Route::post('/enrollment', [EnrollmentController::class, 'store']);
    Route::get('/matriculation/context', [MatriculationController::class, 'context']);
    Route::post('/matriculation', [MatriculationController::class, 'store']);
});
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter MatriculationApiTest`
Expected: PASS (6 tests).

- [ ] **Step 6: Run the whole suite, then commit**

Run: `php artisan test` — all PASS.

```bash
git add app/Http/Controllers/Api/MatriculationController.php routes/api.php tests/Feature/Api/MatriculationApiTest.php
git commit -m "feat: matriculation change API for students"
```

---

### Task 5: Chair approval queue — web routes and dashboard card

**Files:**
- Modify: `routes/web.php` (chair group, around line 203; also the `/approver/dashboard` closure)
- Modify: `resources/views/approver/dashboard.blade.php`
- Test: `tests/Feature/ChairMatriculationTest.php`

**Interfaces:**
- Consumes: `MatriculationChangeService::approve/reject`, `MatriculationChange` model.
- Produces: named routes `approver.matriculation.approve` / `approver.matriculation.reject` (POST, `role:chair`); dashboard view variable `$pendingChanges` (collection of pending `MatriculationChange` with `user`, `items.section.subject`, `items.replacedSection.subject` eager-loaded).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ChairMatriculationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChairMatriculationTest extends TestCase
{
    use RefreshDatabase;

    private function pendingChange(): MatriculationChange
    {
        Setting::put('change_matriculation_open', '1');

        $program = Program::factory()->create(['code' => 'BSOA', 'is_enrollable' => true]);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);

        $subjectA = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-A']);
        $subjectB = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-B']);
        $secA = Section::factory()->create(['subject_id' => $subjectA->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $secB = Section::factory()->create(['subject_id' => $subjectB->id, 'days' => ['T', 'Th'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach([$secA->id, $secB->id]);

        return app(MatriculationChangeService::class)
            ->submit($user, [['action' => 'drop', 'section_id' => $secB->id]]);
    }

    public function test_dashboard_lists_pending_changes(): void
    {
        $change = $this->pendingChange();
        $chair = User::factory()->create(['role' => 'chair']);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $this->assertTrue($response->viewData('pendingChanges')->contains('id', $change->id));
        $response->assertSee('Change of Matriculation');
    }

    public function test_chair_can_approve(): void
    {
        $change = $this->pendingChange();
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)
            ->post("/approver/matriculation/{$change->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $change->fresh()->status);
        $this->assertCount(1, $change->enrollment->sections()->get());
    }

    public function test_chair_reject_requires_remarks(): void
    {
        $change = $this->pendingChange();
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)
            ->from('/approver/dashboard')
            ->post("/approver/matriculation/{$change->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($chair)
            ->post("/approver/matriculation/{$change->id}/reject", ['remarks' => 'Keep your full load.'])
            ->assertRedirect();

        $this->assertSame('rejected', $change->fresh()->status);
    }

    public function test_students_cannot_hit_chair_routes(): void
    {
        $change = $this->pendingChange();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post("/approver/matriculation/{$change->id}/approve")
            ->assertForbidden();
    }
}
```

(`EnsureUserHasRole` aborts 403 for wrong roles, so `assertForbidden()` is correct.)

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter ChairMatriculationTest`
Expected: FAIL — `pendingChanges` view data missing / 404 on matriculation routes.

- [ ] **Step 3: Add routes and dashboard data**

In `routes/web.php`, add imports at the top with the other `use App\Models\...` lines:

```php
use App\Models\MatriculationChange;
use App\Services\MatriculationChangeService;
```

Update the `/approver/dashboard` closure (inside `role:chair` group) to also load pending changes:

```php
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
```

Add the two action routes inside the same `role:chair` group (after the enrollment reject route):

```php
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
```

- [ ] **Step 4: Add the queue card to the dashboard**

In `resources/views/approver/dashboard.blade.php`, directly after the closing `</div>` of the "Pending Irregular Enrollments" card (the `@forelse ($pendingEnrollments ...)` block) and before the final content `</div>`, insert:

```blade
                {{-- Change of Matriculation Queue --}}
                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
                    <h2 class="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                        <i class="fa-solid fa-arrows-rotate mr-2 text-brandGold"></i>Change of Matriculation Requests
                    </h2>

                    @forelse (($pendingChanges ?? []) as $change)
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <p class="font-semibold text-brandNavy dark:text-slate-100">{{ $change->user->name }} ({{ $change->user->login_id }})</p>
                                    <p class="text-xs text-slate-500">{{ $change->user->major }} — {{ $change->user->year_level }} — filed {{ $change->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('approver.matriculation.approve', $change) }}">
                                        @csrf
                                        <button class="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('approver.matriculation.reject', $change) }}" class="flex gap-2">
                                        @csrf
                                        <input name="remarks" required maxlength="500" placeholder="Reason for rejection"
                                               class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm" />
                                        <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                                    </form>
                                </div>
                            </div>
                            <ul class="mt-3 space-y-1 text-sm">
                                @foreach ($change->items as $item)
                                    <li class="border-t border-slate-100 dark:border-slate-800 pt-1">
                                        @if ($item->action === 'add')
                                            <span class="font-bold text-brandGreen uppercase text-xs mr-2">Add</span>
                                            <span class="font-mono">{{ $item->section->subject->code }}</span>
                                            (Block {{ $item->section->block_label }}, {{ implode('/', $item->section->days) }} {{ $item->section->start_time }}–{{ $item->section->end_time }}, {{ $item->section->room }})
                                        @elseif ($item->action === 'drop')
                                            <span class="font-bold text-red-600 uppercase text-xs mr-2">Drop</span>
                                            <span class="font-mono">{{ $item->section->subject->code }}</span>
                                            (Block {{ $item->section->block_label }}, {{ implode('/', $item->section->days) }} {{ $item->section->start_time }}–{{ $item->section->end_time }})
                                        @else
                                            <span class="font-bold text-brandGold uppercase text-xs mr-2">Swap</span>
                                            <span class="font-mono">{{ $item->replacedSection->subject->code }}</span>
                                            Block {{ $item->replacedSection->block_label }} →
                                            Block {{ $item->section->block_label }}
                                            ({{ implode('/', $item->section->days) }} {{ $item->section->start_time }}–{{ $item->section->end_time }}, {{ $item->section->room }})
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No change requests awaiting approval.</p>
                    @endforelse
                </div>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter ChairMatriculationTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Run the whole suite, then commit**

Run: `php artisan test` — all PASS.

```bash
git add routes/web.php resources/views/approver/dashboard.blade.php tests/Feature/ChairMatriculationTest.php
git commit -m "feat: chair queue for change of matriculation requests"
```

---

### Task 6: Admin window toggle

**Files:**
- Create: `app/Http/Controllers/Api/Admin/SettingController.php`
- Modify: `routes/api.php` (admin group)
- Modify: `app/Http/Controllers/Api/Admin/ProgramController.php` (`index`)
- Modify: `resources/js/curriculum-app.jsx` (header toggle)
- Test: `tests/Feature/Api/SettingsApiTest.php`

**Interfaces:**
- Consumes: `Setting::get/put`, `AuditLog::record`.
- Produces: `POST /api/admin/settings/change-matriculation` body `{open: bool}` → 200 `{change_matriculation_open: bool}` (role:admin). `GET /api/admin/programs` response gains top-level `change_matriculation_open: bool`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Api/SettingsApiTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_change_matriculation_window(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => true])
            ->assertOk()
            ->assertJsonPath('change_matriculation_open', true);
        $this->assertSame('1', Setting::get('change_matriculation_open'));

        $this->actingAs($admin)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => false])
            ->assertOk()
            ->assertJsonPath('change_matriculation_open', false);
        $this->assertSame('0', Setting::get('change_matriculation_open'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'Change Matriculation Window Opened']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Change Matriculation Window Closed']);
    }

    public function test_programs_index_exposes_window_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('change_matriculation_open', '1');

        $this->actingAs($admin)->getJson('/api/admin/programs')
            ->assertOk()
            ->assertJsonPath('change_matriculation_open', true);
    }

    public function test_students_cannot_toggle(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->postJson('/api/admin/settings/change-matriculation', ['open' => true])
            ->assertForbidden();
    }
}
```

(`EnsureUserHasRole` aborts 403 for wrong roles, so `assertForbidden()` is correct.)

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter SettingsApiTest`
Expected: FAIL — 404 on the settings route.

- [ ] **Step 3: Create the controller and route; extend programs payload**

`app/Http/Controllers/Api/Admin/SettingController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function changeMatriculation(Request $request): JsonResponse
    {
        $data = $request->validate(['open' => ['required', 'boolean']]);

        Setting::put('change_matriculation_open', $data['open'] ? '1' : '0');

        AuditLog::record(
            'Change Matriculation Window ' . ($data['open'] ? 'Opened' : 'Closed'),
            'Admin set the change of matriculation window to ' . ($data['open'] ? 'OPEN' : 'CLOSED') . '.',
            'Setting'
        );

        return response()->json(['change_matriculation_open' => $data['open']]);
    }
}
```

In `routes/api.php`, add the import and route inside the admin group:

```php
use App\Http\Controllers\Api\Admin\SettingController;
```

```php
    Route::post('/settings/change-matriculation', [SettingController::class, 'changeMatriculation']);
```

In `app/Http/Controllers/Api/Admin/ProgramController.php`, add the import `use App\Models\Setting;` and change `index()` to:

```php
    public function index(): JsonResponse
    {
        return response()->json([
            'change_matriculation_open' => Setting::get('change_matriculation_open', '0') === '1',
            'programs' => Program::withCount('subjects')->orderBy('level')->orderBy('code')->get()
                ->map(fn (Program $p) => [
                    'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'level' => $p->level,
                    'years' => $p->years, 'is_enrollable' => $p->is_enrollable, 'subjects_count' => $p->subjects_count,
                ]),
        ]);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter SettingsApiTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Add the toggle to the curriculum editor**

In `resources/js/curriculum-app.jsx`:

1. Add state next to the other `useState` calls:

```jsx
    const [windowOpen, setWindowOpen] = useState(false);
```

2. In the programs-loading `useEffect`, read the flag:

```jsx
    useEffect(() => {
        api.get('/admin/programs').then((res) => {
            const enrollable = res.data.programs.filter((p) => p.is_enrollable);
            setPrograms(enrollable);
            setWindowOpen(Boolean(res.data.change_matriculation_open));
            if (enrollable.length > 0) setActive(enrollable[0]);
        });
    }, []);
```

3. Add a toggle handler above the `return`:

```jsx
    const toggleWindow = () => {
        api.post('/admin/settings/change-matriculation', { open: !windowOpen })
            .then((res) => setWindowOpen(res.data.change_matriculation_open));
    };
```

4. In the JSX, change the program-tabs row to include the toggle on the right:

```jsx
            <div className="flex flex-wrap gap-2 items-center justify-between">
                <div className="flex flex-wrap gap-2">
                    {programs.map((p) => (
                        <button key={p.id} onClick={() => setActive(p)}
                            className={`px-4 py-2 rounded-lg text-sm font-semibold
                                ${active?.id === p.id ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                            {p.code}
                        </button>
                    ))}
                </div>
                <button onClick={toggleWindow}
                    className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                        ${windowOpen ? 'bg-brandGreen text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                    Change of Matriculation: {windowOpen ? 'OPEN' : 'CLOSED'}
                </button>
            </div>
```

- [ ] **Step 6: Build, run suite, commit**

Run: `npm run build` — success.
Run: `php artisan test` — all PASS.

```bash
git add app/Http/Controllers/Api/Admin/SettingController.php app/Http/Controllers/Api/Admin/ProgramController.php routes/api.php resources/js/curriculum-app.jsx tests/Feature/Api/SettingsApiTest.php
git commit -m "feat: admin toggle for change of matriculation window"
```

---

### Task 7: Student React UI — ChangeBuilder in the enrollment island

**Files:**
- Create: `resources/js/matriculation/ChangeBuilder.jsx`
- Create: `resources/js/matriculation/RequestCard.jsx`
- Modify: `resources/js/enrollment/StatusCard.jsx` (optional `action` prop)
- Modify: `resources/js/enrollment-app.jsx`

**Interfaces:**
- Consumes: Task 4 endpoints (`GET /api/matriculation/context`, `POST /api/matriculation`); section payloads include `id`, `subject_id`, `code`, `days`, `start_time`, `end_time`, `seats_left` (catalogue sections) per Task 4.
- Produces: `ChangeBuilder({ current, catalogue, submitting, error, onSubmit, onCancel })` calling `onSubmit(items)` with the API items array; `RequestCard({ request, windowOpen, onNewRequest })`.

- [ ] **Step 1: Create RequestCard**

`resources/js/matriculation/RequestCard.jsx`:

```jsx
import React from 'react';

const STYLES = {
    pending:  { icon: 'fa-hourglass-half', tone: 'text-amber-600',  label: 'Change Request Awaiting Chair Approval' },
    approved: { icon: 'fa-circle-check',   tone: 'text-brandGreen', label: 'Change of Matriculation Approved' },
    rejected: { icon: 'fa-circle-xmark',   tone: 'text-red-600',    label: 'Change Request Returned' },
};

const ACTION_TONE = { add: 'text-brandGreen', drop: 'text-red-600', swap: 'text-amber-600' };

export default function RequestCard({ request, windowOpen, onNewRequest }) {
    const style = STYLES[request.status];

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <p className={`text-lg font-bold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            <ul className="mt-3 space-y-1 text-sm">
                {request.items.map((item, i) => (
                    <li key={i} className="border-t border-slate-100 dark:border-slate-800 pt-1">
                        <span className={`font-bold uppercase text-xs mr-2 ${ACTION_TONE[item.action]}`}>{item.action}</span>
                        {item.action === 'swap' && item.replaced_section && (
                            <>Block {item.replaced_section.block_label} → </>
                        )}
                        <span className="font-mono">{item.section.code}</span>{' '}
                        Block {item.section.block_label} · {item.section.days.join('/')} {item.section.start_time}–{item.section.end_time} · {item.section.room}
                    </li>
                ))}
            </ul>
            {request.status === 'rejected' && (
                <div className="mt-3">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        <span className="font-semibold">Chair remarks:</span> {request.remarks}
                    </p>
                    {windowOpen && (
                        <button onClick={onNewRequest}
                            className="mt-3 px-4 py-2 rounded-lg bg-brandNavy text-white text-sm font-semibold hover:opacity-90">
                            File New Request
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
```

- [ ] **Step 2: Create ChangeBuilder**

`resources/js/matriculation/ChangeBuilder.jsx`:

```jsx
import React, { useMemo, useState } from 'react';

function overlaps(a, b) {
    if (!a.days.some((d) => b.days.includes(d))) return false;
    return a.start_time < b.end_time && b.start_time < a.end_time;
}

export default function ChangeBuilder({ current, catalogue, submitting, error, onSubmit, onCancel }) {
    const [drops, setDrops] = useState({});  // current section id -> true
    const [swaps, setSwaps] = useState({});  // replaced section id -> target section (with code)
    const [adds, setAdds] = useState({});    // subject id -> target section (with code)

    const items = useMemo(() => [
        ...Object.keys(drops).map((id) => ({ action: 'drop', section_id: Number(id) })),
        ...Object.entries(swaps).map(([replacedId, s]) => ({ action: 'swap', section_id: s.id, replaced_section_id: Number(replacedId) })),
        ...Object.values(adds).map((s) => ({ action: 'add', section_id: s.id })),
    ], [drops, swaps, adds]);

    const resulting = useMemo(() => {
        const kept = current.filter((s) => !drops[s.id] && !swaps[s.id]);
        return [...kept, ...Object.values(swaps), ...Object.values(adds)];
    }, [current, drops, swaps, adds]);

    const conflict = useMemo(() => {
        for (let i = 0; i < resulting.length; i++) {
            for (let j = i + 1; j < resulting.length; j++) {
                if (overlaps(resulting[i], resulting[j])) return [resulting[i], resulting[j]];
            }
        }
        return null;
    }, [resulting]);

    const toggleDrop = (section) => {
        setDrops((prev) => {
            const next = { ...prev };
            if (next[section.id]) delete next[section.id];
            else { next[section.id] = true; }
            return next;
        });
        setSwaps((prev) => { const next = { ...prev }; delete next[section.id]; return next; });
    };

    const toggleSwap = (currentSection, target, code) => {
        setSwaps((prev) => {
            const next = { ...prev };
            if (next[currentSection.id]?.id === target.id) delete next[currentSection.id];
            else next[currentSection.id] = { ...target, code };
            return next;
        });
        setDrops((prev) => { const next = { ...prev }; delete next[currentSection.id]; return next; });
    };

    const toggleAdd = (subject, section) => {
        setAdds((prev) => {
            const next = { ...prev };
            if (next[subject.id]?.id === section.id) delete next[subject.id];
            else next[subject.id] = { ...section, code: subject.code };
            return next;
        });
    };

    const currentSubjectIds = new Set(current.map((s) => s.subject_id));
    const addable = catalogue.filter((subj) => subj.eligible && !currentSubjectIds.has(subj.id));

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">Change of Matriculation</h2>
            <p className="text-xs text-slate-500 mb-4">
                Drop or swap your current sections, or add new subjects. Your request is submitted to the Department Chair for approval.
            </p>

            <h3 className="text-sm font-bold text-slate-500 uppercase mb-2">Current Subjects</h3>
            {current.map((section) => {
                const subject = catalogue.find((s) => s.id === section.subject_id);
                const alternatives = (subject?.sections ?? []).filter((s) => s.id !== section.id);
                return (
                    <div key={section.id}
                        className={`border rounded-xl p-4 mb-3 ${drops[section.id] ? 'border-red-300 bg-red-50/50 dark:bg-red-900/10' : 'border-slate-200 dark:border-slate-700'}`}>
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <p className="font-semibold text-brandNavy dark:text-slate-100">
                                <span className="font-mono">{section.code}</span> — {section.title}
                                <span className="ml-2 text-xs text-slate-400">
                                    Block {section.block_label} · {section.days.join('/')} {section.start_time}–{section.end_time} · {section.room}
                                </span>
                            </p>
                            <button onClick={() => toggleDrop(section)}
                                className={`px-3 py-1.5 rounded-lg border text-xs font-semibold
                                    ${drops[section.id] ? 'border-red-500 bg-red-500/10 text-red-600' : 'border-slate-300 dark:border-slate-600 text-red-600 hover:border-red-400'}`}>
                                {drops[section.id] ? 'Undo Drop' : 'Drop'}
                            </button>
                        </div>
                        {alternatives.length > 0 && !drops[section.id] && (
                            <div className="flex flex-wrap gap-2 mt-3">
                                <span className="text-[10px] uppercase font-bold text-slate-400 self-center">Swap to:</span>
                                {alternatives.map((alt) => {
                                    const selected = swaps[section.id]?.id === alt.id;
                                    const full = alt.seats_left <= 0;
                                    return (
                                        <button key={alt.id} disabled={full && !selected}
                                            onClick={() => toggleSwap(section, alt, section.code)}
                                            className={`px-3 py-2 rounded-lg border text-xs text-left
                                                ${selected ? 'border-brandGold bg-brandGold/10 text-amber-600 font-semibold'
                                                    : full ? 'border-slate-200 text-slate-400 cursor-not-allowed'
                                                    : 'border-slate-300 dark:border-slate-600 hover:border-brandNavy'}`}>
                                            <span className="font-semibold">Block {alt.block_label}</span>{' '}
                                            {alt.days.join('/')} {alt.start_time}–{alt.end_time} · {alt.room}
                                            <span className="block text-[10px] opacity-70">
                                                {full ? 'Section full' : `${alt.seats_left} seats left`} · {alt.professor}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                );
            })}

            {addable.length > 0 && (
                <>
                    <h3 className="text-sm font-bold text-slate-500 uppercase mb-2 mt-6">Add a Subject</h3>
                    {addable.map((subject) => (
                        <div key={subject.id} className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
                            <p className="font-semibold text-brandNavy dark:text-slate-100">
                                <span className="font-mono">{subject.code}</span> — {subject.title}
                                <span className="ml-2 text-xs text-slate-400">{subject.units} units · {subject.mode}</span>
                            </p>
                            <div className="flex flex-wrap gap-2 mt-3">
                                {subject.sections.map((section) => {
                                    const selected = adds[subject.id]?.id === section.id;
                                    const full = section.seats_left <= 0;
                                    return (
                                        <button key={section.id} disabled={full && !selected}
                                            onClick={() => toggleAdd(subject, section)}
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
                        </div>
                    ))}
                </>
            )}

            {conflict && (
                <p className="text-sm text-red-600 mb-2 mt-2">
                    <i className="fa-solid fa-triangle-exclamation mr-1" />
                    Schedule conflict: {conflict[0].code} overlaps with {conflict[1].code}.
                </p>
            )}
            {resulting.length === 0 && (
                <p className="text-sm text-red-600 mb-2 mt-2">You must keep at least one subject.</p>
            )}
            {error && <p className="text-sm text-red-600 mb-2 mt-2">{error}</p>}

            <div className="flex gap-2 mt-4">
                <button
                    disabled={submitting || items.length === 0 || conflict !== null || resulting.length === 0}
                    onClick={() => onSubmit(items)}
                    className="px-5 py-2.5 rounded-lg bg-brandNavy text-white font-semibold text-sm hover:opacity-90 disabled:opacity-50">
                    {submitting ? 'Submitting…' : `Submit ${items.length} Change(s) for Approval`}
                </button>
                <button onClick={onCancel}
                    className="px-5 py-2.5 rounded-lg bg-slate-200 dark:bg-slate-700 text-sm font-semibold">
                    Cancel
                </button>
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Let StatusCard host an action**

In `resources/js/enrollment/StatusCard.jsx`, change the signature and add the action slot right after the status label paragraph:

```jsx
export default function StatusCard({ enrollment, onResubmit, action = null }) {
```

```jsx
            <p className={`text-lg font-bold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            {action}
```

(Everything else in the file stays as is.)

- [ ] **Step 4: Wire the enrollment app**

In `resources/js/enrollment-app.jsx`:

1. Add imports:

```jsx
import ChangeBuilder from './matriculation/ChangeBuilder';
import RequestCard from './matriculation/RequestCard';
```

2. Add state next to the existing `useState` calls:

```jsx
    const [mtx, setMtx] = useState(null);           // matriculation context
    const [building, setBuilding] = useState(false);
    const [mtxSubmitting, setMtxSubmitting] = useState(false);
    const [mtxError, setMtxError] = useState(null);
```

3. Extend `load` so both contexts refresh together — replace the existing `load` callback with:

```jsx
    const load = useCallback(() => {
        setLoading(true);
        api.get('/enrollment/context')
            .then((res) => {
                setCtx(res.data);
                if (res.data.enrollment?.status === 'enrolled') {
                    return api.get('/matriculation/context').then((m) => setMtx(m.data));
                }
                setMtx(null);
            })
            .catch(() => setError('Could not load enrollment data. Please refresh the page.'))
            .finally(() => setLoading(false));
    }, []);
```

4. Add the submit handler after the existing `submit`:

```jsx
    const submitChange = (items) => {
        setMtxSubmitting(true);
        setMtxError(null);
        api.post('/matriculation', { items })
            .then(() => { setBuilding(false); load(); })
            .catch((err) => setMtxError(err.response?.data?.message ?? 'Something went wrong. Please try again.'))
            .finally(() => setMtxSubmitting(false));
    };
```

5. In the JSX, replace the existing `<StatusCard ... />` line with a block that adds the request-change action and the matriculation panels:

```jsx
            {clearance_complete && enrollment && !(enrollment.status === 'rejected' && resubmitting) && (
                <>
                    <StatusCard
                        enrollment={enrollment}
                        onResubmit={() => setResubmitting(true)}
                        action={enrollment.status === 'enrolled' && mtx?.window_open && !building
                            && (!mtx.request || mtx.request.status === 'approved') ? (
                            <button onClick={() => setBuilding(true)}
                                className="mt-2 px-4 py-2 rounded-lg bg-brandGold text-brandNavy text-sm font-semibold hover:opacity-90">
                                <i className="fa-solid fa-arrows-rotate mr-2" />Request Change of Matriculation
                            </button>
                        ) : null}
                    />
                    {mtx?.request && !building && mtx.request.status !== 'approved' && (
                        <RequestCard request={mtx.request} windowOpen={mtx.window_open}
                            onNewRequest={() => setBuilding(true)} />
                    )}
                    {building && mtx && (
                        <ChangeBuilder
                            current={mtx.enrollment.sections}
                            catalogue={mtx.catalogue ?? []}
                            submitting={mtxSubmitting}
                            error={mtxError}
                            onSubmit={submitChange}
                            onCancel={() => { setBuilding(false); setMtxError(null); }}
                        />
                    )}
                </>
            )}
```

- [ ] **Step 5: Build and verify manually**

Run: `npm run build`
Expected: success.

Manual check (`php artisan serve --port=8000`, log in as demo student `2300410` / `password` after enrolling them, with the window opened as admin): the enrolled status card shows the gold "Request Change of Matriculation" button; the builder lists current subjects with Drop/Swap and addable subjects; submitting shows the pending RequestCard.

- [ ] **Step 6: Run the suite, then commit**

Run: `php artisan test` — all PASS (Blade/React untouched by PHP tests).

```bash
git add resources/js/matriculation resources/js/enrollment/StatusCard.jsx resources/js/enrollment-app.jsx
git commit -m "feat: change of matriculation builder in the enrollment island"
```

---

### Task 8: Notif bell, final verification, and demo walkthrough

**Files:**
- Modify: `resources/views/partials/notif-script.blade.php`

**Interfaces:**
- Consumes: `MatriculationChange` model; existing notif array conventions (`$notifs[]`, `$nid`).

- [ ] **Step 1: Student notifications**

In `resources/views/partials/notif-script.blade.php`, inside the student branch, directly after the `$latestEnrollment` block, add:

```php
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
```

- [ ] **Step 2: Chair notification**

In the same file, inside the `$authRole === 'chair'` branch, after the enrollment `$pendingCount` block, add:

```php
        $pendingChangeCount = \App\Models\MatriculationChange::where('status', 'pending')->count();
        if ($pendingChangeCount > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-arrows-rotate', 'color' => '#E2A700', 'title' => 'Change Requests Awaiting Approval', 'desc' => $pendingChangeCount . ' change of matriculation request(s) need your review.', 'time' => 'Action needed'];
        }
```

- [ ] **Step 3: Full verification**

Run: `php artisan view:cache && php artisan view:clear` — no Blade syntax errors.
Run: `php artisan test` — all PASS (expect 41 pre-existing + ~30 new).
Run: `npm run build` — success, all four entries in the manifest.
Run: `php artisan migrate:fresh --seed` — no errors.

- [ ] **Step 4: Demo walkthrough (the flow panelists will see)**

1. Log in as `admin01` → `/admin/curriculum` → toggle "Change of Matriculation: OPEN".
2. Log in as `2300410` (regular demo student) → `/enrollment` → confirm block → enrolled. Status card now shows "Request Change of Matriculation".
3. Open the builder → swap one subject to Block B, drop another, add an eligible subject → submit → pending RequestCard shows.
4. Log in as `chair01` → approver dashboard shows the change request with Add/Drop/Swap lines → Reject with remarks → student sees remarks and can re-file → Approve on the second request.
5. Student's `/cor` timetable shows the updated sections. `/admin/audit` shows Submitted/Rejected/Approved entries and the window toggle.

- [ ] **Step 5: Commit**

```bash
git add resources/views/partials/notif-script.blade.php
git commit -m "feat: matriculation change notifications for students and chair"
```
