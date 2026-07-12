# Faculty & Room Loading Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admins assign professors (faculty user accounts) and rooms (physical/virtual entities) to sections with double-booking prevention; faculty log in to a read-only weekly teaching schedule.

**Architecture:** Faculty are `users` rows with `role = 'faculty'`; rooms are a new `rooms` table. `sections` gains nullable `faculty_id`/`room_id` FKs while the legacy `professor`/`room` strings remain as display fallback. A `SectionScheduleService` rejects conflicting assignments before `SectionController` persists. The admin curriculum React island gets dropdowns + a Faculty Loading view; faculty get a Blade schedule page.

**Tech Stack:** Laravel 12, PHPUnit (RefreshDatabase, SQLite `:memory:`), React 18 islands via Vite 7, Tailwind CDN on Blade pages, Sanctum stateful cookies.

## Global Constraints

- Business-rule failures throw `App\Exceptions\EnrollmentException` (constructor `(string $message, int $status = 409)`); API responses render as JSON `{"message": ...}` with that status.
- Every state change is audit-logged via `AuditLog::record(action, description, targetType, targetId)`. Actions in this plan: `Faculty Created`, `Room Created`, existing `Curriculum Updated` for section changes.
- "Current school year" = `Setting::get('school_year', '2026-2027')` (what `EnrollmentService::currentTerm()` returns).
- Section times are `HH:MM` 24-hour strings; `days` is a JSON array of `M,T,W,Th,F,Sat,Sun`; overlap semantics identical to `Section::overlaps()` (shared day AND `start < other.end && other.start < end`).
- Conflict rules: same school year + overlap → same faculty always conflicts; same room conflicts only when the room's type is `physical`. Null FKs never conflict.
- Brand colors: brandNavy `#0B3C5D`, brandGreen `#1D7A46`, brandGold `#E2A700`; dark-mode variants required. New notif fields must render through the existing `escNotif()` escape.
- Run tests with `php artisan test` (optionally `--filter Name`). Build JS with `npm run build`.
- TDD: write tests, see them fail, implement, see them pass. Commit at the end of each task.

---

### Task 1: Rooms + section FKs — schema and models

**Files:**
- Create: `database/migrations/2026_07_12_100001_create_rooms_table.php`
- Create: `database/migrations/2026_07_12_100002_add_faculty_and_room_to_sections_table.php`
- Create: `app/Models/Room.php`
- Modify: `app/Models/Section.php`
- Modify: `app/Models/User.php`
- Test: `tests/Unit/FacultyRoomSchemaTest.php`

**Interfaces:**
- Consumes: existing `Section`, `User` models; `Section::overlaps()`.
- Produces: `Room` model (`$fillable = ['name','type']`, `sections(): HasMany`, `isPhysical(): bool`); `Section::faculty(): BelongsTo(User)`, `Section::roomEntity(): BelongsTo(Room)`, `Section::facultyName(): string`, `Section::roomLabel(): string`; `User::taughtSections(): HasMany(Section, 'faculty_id')`. `sections.faculty_id` and `sections.room_id` nullable FK columns.

- [ ] **Step 1: Write the failing test**

`tests/Unit/FacultyRoomSchemaTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyRoomSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_and_faculty_links_on_sections(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Reyes']);
        $room = Room::create(['name' => 'Rm 201', 'type' => 'physical']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id, 'room_id' => $room->id]);

        $this->assertTrue($section->faculty->is($faculty));
        $this->assertTrue($section->roomEntity->is($room));
        $this->assertTrue($room->isPhysical());
        $this->assertSame('Prof. Reyes', $section->facultyName());
        $this->assertSame('Rm 201', $section->roomLabel());
        $this->assertTrue($faculty->taughtSections()->whereKey($section->id)->exists());
        $this->assertTrue($room->sections()->whereKey($section->id)->exists());
    }

    public function test_labels_fall_back_to_legacy_strings(): void
    {
        $section = Section::factory()->create(['room' => 'Old Hall', 'professor' => 'TBA Faculty']);

        $this->assertNull($section->faculty_id);
        $this->assertNull($section->room_id);
        $this->assertSame('TBA Faculty', $section->facultyName());
        $this->assertSame('Old Hall', $section->roomLabel());
    }

    public function test_virtual_room_is_not_physical(): void
    {
        $room = Room::create(['name' => 'Google Meet A', 'type' => 'virtual']);

        $this->assertFalse($room->isPhysical());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter FacultyRoomSchemaTest`
Expected: FAIL — `Class "App\Models\Room" not found`.

- [ ] **Step 3: Create migrations and models**

`database/migrations/2026_07_12_100001_create_rooms_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('type', 10); // physical | virtual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
```

`database/migrations/2026_07_12_100002_add_faculty_and_room_to_sections_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('faculty_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faculty_id');
            $table->dropConstrainedForeignId('room_id');
        });
    }
};
```

`app/Models/Room.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'type'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }
}
```

In `app/Models/Section.php`, add `'faculty_id', 'room_id'` to `$fillable` (so it reads):

```php
    protected $fillable = [
        'subject_id', 'block_label', 'days', 'start_time', 'end_time',
        'room', 'professor', 'capacity', 'school_year', 'faculty_id', 'room_id',
    ];
```

and add these methods after `subject()`:

```php
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function roomEntity(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function facultyName(): string
    {
        return $this->faculty?->name ?? $this->professor;
    }

    public function roomLabel(): string
    {
        return $this->roomEntity?->name ?? $this->room;
    }
```

In `app/Models/User.php`, add after `enrollments()`:

```php
    public function taughtSections(): HasMany
    {
        return $this->hasMany(Section::class, 'faculty_id');
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter FacultyRoomSchemaTest`
Expected: PASS (3 tests). Then run `php artisan test` — full suite still green (73 + 3 = 76 passed).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_12_100001_create_rooms_table.php database/migrations/2026_07_12_100002_add_faculty_and_room_to_sections_table.php app/Models/Room.php app/Models/Section.php app/Models/User.php tests/Unit/FacultyRoomSchemaTest.php
git commit -m "feat: rooms table and faculty/room links on sections"
```

---

### Task 2: Conflict validation service wired into SectionController

**Files:**
- Create: `app/Services/SectionScheduleService.php`
- Modify: `app/Http/Controllers/Api/Admin/SectionController.php` (full replacement below)
- Modify: `app/Http/Controllers/Api/Admin/ProgramController.php` (`subjects()`)
- Test: `tests/Feature/Api/SectionConflictTest.php`

**Interfaces:**
- Consumes: Task 1 (`Room::isPhysical()`, `Section::faculty()/roomEntity()/facultyName()/roomLabel()`), `EnrollmentException`.
- Produces: `SectionScheduleService::assertNoConflicts(array $attributes, ?Section $ignore = null): void` (throws `EnrollmentException` 409). `POST/PUT /api/admin/sections` accept optional nullable `faculty_id` (must be a faculty-role user) and `room_id`; their JSON `section` object gains `faculty_name` and `room_label`. `GET /api/admin/programs/{program}/subjects` section payloads gain `faculty_id`, `room_id`, `faculty_name`, `room_label`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Api/SectionConflictTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $prof;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->prof = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Cruz']);
        $this->room = Room::create(['name' => 'Rm 301', 'type' => 'physical']);
    }

    /** A valid POST /api/admin/sections payload; override what each test needs. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subject_id' => Subject::factory()->create()->id,
            'block_label' => 'A',
            'days' => ['M', 'W'],
            'start_time' => '08:00',
            'end_time' => '09:30',
            'room' => 'Legacy Room',
            'professor' => 'Legacy Prof',
            'capacity' => 40,
            'school_year' => '2026-2027',
        ], $overrides);
    }

    public function test_same_faculty_overlap_is_rejected(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'start_time' => '09:00', 'end_time' => '10:30']));

        $response->assertStatus(409);
        $this->assertStringContainsString('Prof. Cruz', $response->json('message'));
    }

    public function test_physical_room_overlap_is_rejected(): void
    {
        Section::factory()->create(['room_id' => $this->room->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['room_id' => $this->room->id]));

        $response->assertStatus(409);
        $this->assertStringContainsString('Rm 301', $response->json('message'));
    }

    public function test_virtual_room_overlap_is_allowed(): void
    {
        $meet = Room::create(['name' => 'Google Meet A', 'type' => 'virtual']);
        Section::factory()->create(['room_id' => $meet->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['room_id' => $meet->id]))
            ->assertStatus(201);
    }

    public function test_disjoint_days_do_not_conflict(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'days' => ['T', 'Th']]))
            ->assertStatus(201);
    }

    public function test_disjoint_times_do_not_conflict(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'start_time' => '09:30', 'end_time' => '11:00']))
            ->assertStatus(201);
    }

    public function test_different_school_year_does_not_conflict(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30', 'school_year' => '2025-2026']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id]))
            ->assertStatus(201);
    }

    public function test_update_does_not_conflict_with_itself(): void
    {
        $section = Section::factory()->create(['faculty_id' => $this->prof->id, 'room_id' => $this->room->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/sections/{$section->id}", ['capacity' => 45])
            ->assertOk()
            ->assertJsonPath('section.capacity', 45);
    }

    public function test_legacy_sections_without_fks_never_conflict(): void
    {
        Section::factory()->create(['days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Same String', 'professor' => 'Same String']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['room' => 'Same String', 'professor' => 'Same String']))
            ->assertStatus(201);
    }

    public function test_faculty_id_must_reference_a_faculty_user(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $student->id]))
            ->assertStatus(422);
    }

    public function test_store_returns_faculty_name_and_room_label(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'room_id' => $this->room->id]));

        $response->assertStatus(201)
            ->assertJsonPath('section.faculty_name', 'Prof. Cruz')
            ->assertJsonPath('section.room_label', 'Rm 301');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter SectionConflictTest`
Expected: FAIL — conflict tests get 201 instead of 409; `faculty_name` missing from payload.

- [ ] **Step 3: Create the service**

`app/Services/SectionScheduleService.php`:

```php
<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\Room;
use App\Models\Section;

class SectionScheduleService
{
    /**
     * Reject the given section attributes if they double-book a professor or a
     * physical room. $attributes must contain days, start_time, end_time,
     * school_year and may contain faculty_id / room_id. Pass the section being
     * edited as $ignore so it does not conflict with itself.
     *
     * @throws EnrollmentException 409 on conflict
     */
    public function assertNoConflicts(array $attributes, ?Section $ignore = null): void
    {
        $facultyId = $attributes['faculty_id'] ?? null;
        $roomId = $attributes['room_id'] ?? null;

        $room = $roomId ? Room::find($roomId) : null;
        $checkRoom = $room !== null && $room->isPhysical();

        if (! $facultyId && ! $checkRoom) {
            return;
        }

        $candidates = Section::query()
            ->where('school_year', $attributes['school_year'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
            ->where(function ($q) use ($facultyId, $roomId, $checkRoom) {
                if ($facultyId) {
                    $q->orWhere('faculty_id', $facultyId);
                }
                if ($checkRoom) {
                    $q->orWhere('room_id', $roomId);
                }
            })
            ->with(['subject', 'faculty'])
            ->get();

        foreach ($candidates as $other) {
            if (! $this->overlaps($attributes, $other)) {
                continue;
            }

            $slot = sprintf('%s %s–%s', implode('/', $other->days), $other->start_time, $other->end_time);

            if ($facultyId && (int) $other->faculty_id === (int) $facultyId) {
                throw new EnrollmentException(sprintf(
                    '%s is already scheduled for %s Block %s (%s).',
                    $other->faculty?->name ?? 'This professor', $other->subject->code, $other->block_label, $slot
                ));
            }

            if ($checkRoom && (int) $other->room_id === (int) $roomId) {
                throw new EnrollmentException(sprintf(
                    'Room %s is already booked for %s Block %s (%s).',
                    $room->name, $other->subject->code, $other->block_label, $slot
                ));
            }
        }
    }

    private function overlaps(array $attributes, Section $other): bool
    {
        if (count(array_intersect($attributes['days'], $other->days)) === 0) {
            return false;
        }

        return $attributes['start_time'] < $other->end_time
            && $other->start_time < $attributes['end_time'];
    }
}
```

- [ ] **Step 4: Wire the controller**

Replace `app/Http/Controllers/Api/Admin/SectionController.php` entirely with:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Section;
use App\Services\SectionScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function __construct(private SectionScheduleService $scheduler)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $this->scheduler->assertNoConflicts($data);
        } catch (EnrollmentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        $section = Section::create($data);

        AuditLog::record('Curriculum Updated', "Section {$section->block_label} added to subject #{$section->subject_id}.", 'Section', $section->id);

        return response()->json(['section' => $this->payload($section)], 201);
    }

    public function update(Request $request, Section $section): JsonResponse
    {
        $data = $this->validated($request, $section);
        $merged = array_merge(
            $section->only(['days', 'start_time', 'end_time', 'school_year', 'faculty_id', 'room_id']),
            $data
        );

        try {
            $this->scheduler->assertNoConflicts($merged, $section);
        } catch (EnrollmentException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        $section->update($data);

        AuditLog::record('Curriculum Updated', "Section #{$section->id} updated.", 'Section', $section->id);

        return response()->json(['section' => $this->payload($section->fresh())]);
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

    private function payload(Section $section): array
    {
        return array_merge($section->toArray(), [
            'faculty_name' => $section->facultyName(),
            'room_label' => $section->roomLabel(),
        ]);
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
            'faculty_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('role', 'faculty')],
            'room_id' => ['sometimes', 'nullable', 'integer', Rule::exists('rooms', 'id')],
        ]);
    }
}
```

In `app/Http/Controllers/Api/Admin/ProgramController.php`, change `subjects()` so the eager load and section payload include the new fields:

```php
    public function subjects(Program $program): JsonResponse
    {
        return response()->json([
            'subjects' => $program->subjects()->with(['prerequisites', 'sections.faculty', 'sections.roomEntity'])
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
                        'faculty_id' => $sec->faculty_id, 'room_id' => $sec->room_id,
                        'faculty_name' => $sec->facultyName(), 'room_label' => $sec->roomLabel(),
                    ]),
                ]),
        ]);
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter SectionConflictTest`
Expected: PASS (10 tests). Then `php artisan test` — full suite green (86 passed).

- [ ] **Step 6: Commit**

```bash
git add app/Services/SectionScheduleService.php app/Http/Controllers/Api/Admin/SectionController.php app/Http/Controllers/Api/Admin/ProgramController.php tests/Feature/Api/SectionConflictTest.php
git commit -m "feat: schedule conflict validation for faculty and room assignments"
```

---

### Task 3: Faculty & Rooms admin API

**Files:**
- Create: `app/Http/Controllers/Api/Admin/FacultyController.php`
- Create: `app/Http/Controllers/Api/Admin/RoomController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/FacultyRoomApiTest.php`

**Interfaces:**
- Consumes: Task 1 (`Room`, `User::taughtSections()`, `Section::roomLabel()`), `EnrollmentService::currentTerm()`.
- Produces (all in the `role:admin` group):
  - `GET /api/admin/faculty` → `{faculty: [{id, name, login_id, sections_count}]}`
  - `POST /api/admin/faculty` `{name, login_id}` → 201 `{faculty: {id, name, login_id, sections_count: 0}}` (role forced to `faculty`, password `password123`)
  - `GET /api/admin/faculty/{user}/schedule` → `{schedule: [{id, subject_code, subject_title, block_label, days, start_time, end_time, room_label, online}]}`; 422 if the target user is not faculty
  - `GET /api/admin/rooms` → `{rooms: [{id, name, type}]}`
  - `POST /api/admin/rooms` `{name, type}` → 201 `{room: {...}}`

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Api/FacultyRoomApiTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyRoomApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_and_list_rooms(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/rooms', ['name' => 'Rm 101', 'type' => 'physical'])
            ->assertStatus(201)
            ->assertJsonPath('room.name', 'Rm 101');

        $this->actingAs($this->admin)->getJson('/api/admin/rooms')
            ->assertOk()
            ->assertJsonPath('rooms.0.name', 'Rm 101');

        $this->assertDatabaseHas('audit_logs', ['action' => 'Room Created']);
    }

    public function test_duplicate_room_name_is_rejected(): void
    {
        Room::create(['name' => 'Rm 101', 'type' => 'physical']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/rooms', ['name' => 'Rm 101', 'type' => 'physical'])
            ->assertStatus(422);
    }

    public function test_invalid_room_type_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/rooms', ['name' => 'Rm 102', 'type' => 'hologram'])
            ->assertStatus(422);
    }

    public function test_admin_can_create_and_list_faculty(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/faculty', ['name' => 'Prof. Liza Ramos', 'login_id' => 'faculty09'])
            ->assertStatus(201)
            ->assertJsonPath('faculty.name', 'Prof. Liza Ramos')
            ->assertJsonPath('faculty.sections_count', 0);

        $this->assertDatabaseHas('users', ['login_id' => 'faculty09', 'role' => 'faculty']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Faculty Created']);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/faculty');
        $response->assertOk();
        $this->assertContains('faculty09', array_column($response->json('faculty'), 'login_id'));
    }

    public function test_duplicate_faculty_login_id_is_rejected(): void
    {
        User::factory()->create(['login_id' => 'faculty09']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/faculty', ['name' => 'Prof. Dup', 'login_id' => 'faculty09'])
            ->assertStatus(422);
    }

    public function test_schedule_endpoint_returns_current_year_sections_only(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $current = Section::factory()->create(['faculty_id' => $prof->id, 'school_year' => '2026-2027']);
        Section::factory()->create(['faculty_id' => $prof->id, 'school_year' => '2025-2026', 'days' => ['T']]);

        $response = $this->actingAs($this->admin)->getJson("/api/admin/faculty/{$prof->id}/schedule");

        $response->assertOk();
        $this->assertCount(1, $response->json('schedule'));
        $this->assertSame($current->id, $response->json('schedule.0.id'));
    }

    public function test_schedule_endpoint_rejects_non_faculty_target(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->admin)
            ->getJson("/api/admin/faculty/{$student->id}/schedule")
            ->assertStatus(422);
    }

    public function test_students_cannot_use_faculty_or_room_endpoints(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->getJson('/api/admin/faculty')->assertForbidden();
        $this->actingAs($student)->postJson('/api/admin/rooms', ['name' => 'X', 'type' => 'physical'])->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter FacultyRoomApiTest`
Expected: FAIL — 404 on the new routes.

- [ ] **Step 3: Create controllers and routes**

`app/Http/Controllers/Api/Admin/FacultyController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Section;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FacultyController extends Controller
{
    public function index(EnrollmentService $enrollments): JsonResponse
    {
        $year = $enrollments->currentTerm()['school_year'];

        return response()->json([
            'faculty' => User::where('role', 'faculty')->orderBy('name')
                ->withCount(['taughtSections as sections_count' => fn ($q) => $q->where('school_year', $year)])
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id, 'name' => $u->name, 'login_id' => $u->login_id,
                    'sections_count' => $u->sections_count,
                ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,login_id'],
        ]);

        $faculty = User::create([
            'name' => $data['name'],
            'login_id' => $data['login_id'],
            'email' => $data['login_id'].'@faculty.aitsa.test',
            'password' => Hash::make('password123'),
            'role' => 'faculty',
        ]);

        AuditLog::record('Faculty Created', "Faculty account created for {$faculty->name} ({$faculty->login_id}).", 'User', $faculty->id);

        return response()->json(['faculty' => [
            'id' => $faculty->id, 'name' => $faculty->name, 'login_id' => $faculty->login_id, 'sections_count' => 0,
        ]], 201);
    }

    public function schedule(User $user, EnrollmentService $enrollments): JsonResponse
    {
        if ($user->role !== 'faculty') {
            return response()->json(['message' => 'That user is not a faculty member.'], 422);
        }

        $year = $enrollments->currentTerm()['school_year'];

        return response()->json([
            'schedule' => $user->taughtSections()->where('school_year', $year)
                ->with(['subject', 'roomEntity'])->orderBy('start_time')->get()
                ->map(fn (Section $s) => [
                    'id' => $s->id,
                    'subject_code' => $s->subject->code,
                    'subject_title' => $s->subject->title,
                    'block_label' => $s->block_label,
                    'days' => $s->days,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'room_label' => $s->roomLabel(),
                    'online' => $s->roomEntity !== null && ! $s->roomEntity->isPhysical(),
                ])->values(),
        ]);
    }
}
```

`app/Http/Controllers/Api/Admin/RoomController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['rooms' => Room::orderBy('name')->get(['id', 'name', 'type'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:rooms,name'],
            'type' => ['required', 'in:physical,virtual'],
        ]);

        $room = Room::create($data);

        AuditLog::record('Room Created', "Room {$room->name} ({$room->type}) added.", 'Room', $room->id);

        return response()->json(['room' => $room], 201);
    }
}
```

In `routes/api.php`, add the imports:

```php
use App\Http\Controllers\Api\Admin\FacultyController;
use App\Http\Controllers\Api\Admin\RoomController;
```

and add inside the admin group (after the sections routes):

```php
    Route::get('/faculty', [FacultyController::class, 'index']);
    Route::post('/faculty', [FacultyController::class, 'store']);
    Route::get('/faculty/{user}/schedule', [FacultyController::class, 'schedule']);
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter FacultyRoomApiTest`
Expected: PASS (8 tests). Then `php artisan test` — full suite green (94 passed).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Admin/FacultyController.php app/Http/Controllers/Api/Admin/RoomController.php routes/api.php tests/Feature/Api/FacultyRoomApiTest.php
git commit -m "feat: faculty and room admin API"
```

---

### Task 4: Seeder — demo faculty, rooms, and conflict-free backfill

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/SeederFacultyTest.php`

**Interfaces:**
- Consumes: Task 1 models; `Section::overlaps()`; existing production guard (`if (app()->environment('production')) return;`) already present before the demo students.
- Produces: demo users `faculty01` (Prof. Liza Ramos) / `faculty02` (Prof. Marco Dizon), password `password123`; `rooms` rows for every distinct legacy room string (physical) plus virtual `Google Meet A`; every section gets `room_id`; faculty spread greedily over sections without double-booking (sections that would clash with both professors stay unassigned).

- [ ] **Step 1: Write the failing test**

`tests/Feature/SeederFacultyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederFacultyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_links_rooms_and_faculty_without_double_booking(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['login_id' => 'faculty01', 'role' => 'faculty']);
        $this->assertDatabaseHas('users', ['login_id' => 'faculty02', 'role' => 'faculty']);
        $this->assertDatabaseHas('rooms', ['name' => 'Google Meet A', 'type' => 'virtual']);

        $this->assertSame(0, Section::whereNull('room_id')->count(), 'every seeded section should link a room');
        $this->assertTrue(Section::whereNotNull('faculty_id')->exists(), 'some sections should be assigned to faculty');

        foreach (User::where('role', 'faculty')->get() as $prof) {
            $sections = $prof->taughtSections()->get();
            foreach ($sections as $a) {
                foreach ($sections as $b) {
                    if ($a->id >= $b->id || $a->school_year !== $b->school_year) {
                        continue;
                    }
                    $this->assertFalse($a->overlaps($b), "Seeder double-booked {$prof->login_id} (sections {$a->id} and {$b->id}).");
                }
            }
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter SeederFacultyTest`
Expected: FAIL — no `faculty01` user seeded.

- [ ] **Step 3: Extend the seeder**

In `database/seeders/DatabaseSeeder.php`, after the irregular demo student's `StudentGrade::firstOrCreate(...)` block (the last statement of `run()`), add:

```php
        // 8. Faculty & room loading demo data (still inside the non-production guard above).
        $facultyOne = User::firstOrCreate(
            ['login_id' => 'faculty01'],
            ['name' => 'Prof. Liza Ramos', 'email' => 'faculty01@faculty.aitsa.test',
             'password' => Hash::make('password123'), 'role' => 'faculty']
        );
        $facultyTwo = User::firstOrCreate(
            ['login_id' => 'faculty02'],
            ['name' => 'Prof. Marco Dizon', 'email' => 'faculty02@faculty.aitsa.test',
             'password' => Hash::make('password123'), 'role' => 'faculty']
        );

        foreach (\App\Models\Section::query()->distinct()->pluck('room') as $roomName) {
            \App\Models\Room::firstOrCreate(['name' => $roomName], ['type' => 'physical']);
        }
        \App\Models\Room::firstOrCreate(['name' => 'Google Meet A'], ['type' => 'virtual']);

        // Link every section to its room entity; spread faculty greedily without double-booking.
        $roomsByName = \App\Models\Room::pluck('id', 'name');
        $assigned = [$facultyOne->id => [], $facultyTwo->id => []];

        foreach (\App\Models\Section::orderBy('id')->get() as $section) {
            $section->room_id = $roomsByName[$section->room] ?? null;

            if ($section->faculty_id === null) {
                foreach ([$facultyOne->id, $facultyTwo->id] as $facultyId) {
                    $clash = collect($assigned[$facultyId])->contains(
                        fn ($s) => $s->school_year === $section->school_year && $s->overlaps($section)
                    );
                    if (! $clash) {
                        $section->faculty_id = $facultyId;
                        $assigned[$facultyId][] = $section;
                        break;
                    }
                }
            }

            $section->save();
        }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter SeederFacultyTest`
Expected: PASS (1 test). Then `php artisan test` — full suite green (95 passed).

- [ ] **Step 5: Verify a real seed run**

Run: `php artisan migrate:fresh --seed`
Expected: no errors (this resets the local dev database — expected and fine).

- [ ] **Step 6: Commit**

```bash
git add database/seeders/DatabaseSeeder.php tests/Feature/SeederFacultyTest.php
git commit -m "feat: seed demo faculty, rooms, and conflict-free section links"
```

---

### Task 5: Curriculum island — dropdowns, inline add, Faculty Loading view

**Files:**
- Modify: `resources/js/curriculum-app.jsx`
- Modify: `resources/js/curriculum/SubjectRow.jsx`
- Modify: `resources/js/curriculum/SectionEditor.jsx` (full replacement below)
- Create: `resources/js/curriculum/FacultyLoading.jsx`

**Interfaces:**
- Consumes: Task 2/3 endpoints. Section payloads now carry `faculty_id`, `room_id`, `faculty_name`, `room_label`.
- Produces: `SectionEditor({ subject, schoolYear, faculty, rooms, onChanged, onListsChanged })`; `FacultyLoading({ faculty })`; SubjectRow passes `faculty`, `rooms`, `onListsChanged` through.

- [ ] **Step 1: Replace SectionEditor**

Replace `resources/js/curriculum/SectionEditor.jsx` entirely with:

```jsx
import React, { useState } from 'react';
import api from '../lib/api';

const EMPTY = { block_label: 'A', days: ['M', 'W'], start_time: '08:00', end_time: '09:30', room: 'TBA', professor: 'TBA', capacity: 40, faculty_id: null, room_id: null };
const DAY_OPTIONS = ['M', 'T', 'W', 'Th', 'F', 'Sat', 'Sun'];

export default function SectionEditor({ subject, schoolYear, faculty, rooms, onChanged, onListsChanged }) {
    const [draft, setDraft] = useState(null); // null | {..section fields, id?}
    const [error, setError] = useState(null);
    const [newFaculty, setNewFaculty] = useState(null); // null | {name, login_id}
    const [newRoom, setNewRoom] = useState(null); // null | {name, type}

    const save = () => {
        setError(null);
        const payload = {
            ...draft, subject_id: subject.id, school_year: schoolYear, capacity: Number(draft.capacity),
            faculty_id: draft.faculty_id ? Number(draft.faculty_id) : null,
            room_id: draft.room_id ? Number(draft.room_id) : null,
        };
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

    const addFaculty = () => {
        setError(null);
        api.post('/admin/faculty', newFaculty)
            .then((res) => {
                setNewFaculty(null);
                setDraft((d) => ({ ...d, faculty_id: res.data.faculty.id }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not add faculty.'));
    };

    const addRoom = () => {
        setError(null);
        api.post('/admin/rooms', newRoom)
            .then((res) => {
                setNewRoom(null);
                setDraft((d) => ({ ...d, room_id: res.data.room.id }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not add room.'));
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
                        <span className="font-semibold">Block {s.block_label}</span> · {s.days.join('/')} {s.start_time}–{s.end_time} · {s.room_label ?? s.room} · {s.faculty_name ?? s.professor}
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
                        <input type="number" value={draft.capacity} onChange={(e) => setDraft({ ...draft, capacity: e.target.value })}
                            placeholder="Cap" className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                    </div>
                    <div className="flex flex-wrap gap-2 items-center">
                        <select value={draft.room_id ?? ''} onChange={(e) => setDraft({ ...draft, room_id: e.target.value || null })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <option value="">— room unassigned —</option>
                            {rooms.map((r) => <option key={r.id} value={r.id}>{r.name}{r.type === 'virtual' ? ' (online)' : ''}</option>)}
                        </select>
                        <button onClick={() => setNewRoom(newRoom ? null : { name: '', type: 'physical' })}
                            className="text-brandGreen font-semibold hover:underline">+ Add room</button>
                        <select value={draft.faculty_id ?? ''} onChange={(e) => setDraft({ ...draft, faculty_id: e.target.value || null })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <option value="">— professor unassigned —</option>
                            {faculty.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                        </select>
                        <button onClick={() => setNewFaculty(newFaculty ? null : { name: '', login_id: '' })}
                            className="text-brandGreen font-semibold hover:underline">+ Add faculty</button>
                    </div>
                    {newRoom && (
                        <div className="flex flex-wrap gap-2 items-center">
                            <input value={newRoom.name} onChange={(e) => setNewRoom({ ...newRoom, name: e.target.value })}
                                placeholder="Room name" className="w-32 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <select value={newRoom.type} onChange={(e) => setNewRoom({ ...newRoom, type: e.target.value })}
                                className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                                <option value="physical">Physical</option><option value="virtual">Virtual (online)</option>
                            </select>
                            <button onClick={addRoom} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save Room</button>
                        </div>
                    )}
                    {newFaculty && (
                        <div className="flex flex-wrap gap-2 items-center">
                            <input value={newFaculty.name} onChange={(e) => setNewFaculty({ ...newFaculty, name: e.target.value })}
                                placeholder="Professor name" className="w-40 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <input value={newFaculty.login_id} onChange={(e) => setNewFaculty({ ...newFaculty, login_id: e.target.value })}
                                placeholder="Login ID" className="w-28 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <button onClick={addFaculty} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save Faculty</button>
                        </div>
                    )}
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

(Note: the legacy `room`/`professor` free-text inputs are gone from the form; `EMPTY` sends the strings as `'TBA'` to satisfy the API's required legacy fields, and edits of existing sections keep whatever strings they already carry.)

- [ ] **Step 2: Create FacultyLoading**

`resources/js/curriculum/FacultyLoading.jsx`:

```jsx
import React, { useState } from 'react';
import api from '../lib/api';

function weeklyHours(s) {
    const [sh, sm] = s.start_time.split(':').map(Number);
    const [eh, em] = s.end_time.split(':').map(Number);
    return (((eh * 60 + em) - (sh * 60 + sm)) / 60) * s.days.length;
}

export default function FacultyLoading({ faculty }) {
    const [openId, setOpenId] = useState(null);
    const [schedules, setSchedules] = useState({});

    const toggle = (id) => {
        if (openId === id) { setOpenId(null); return; }
        setOpenId(id);
        if (!schedules[id]) {
            api.get(`/admin/faculty/${id}/schedule`).then((res) => setSchedules((s) => ({ ...s, [id]: res.data.schedule })));
        }
    };

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-3">Faculty Loading</h2>
            {faculty.length === 0 && (
                <p className="text-sm text-slate-400">No faculty accounts yet — add one from any section editor.</p>
            )}
            {faculty.map((f) => {
                const sched = schedules[f.id] ?? [];
                const totalHours = sched.reduce((sum, s) => sum + weeklyHours(s), 0);
                return (
                    <div key={f.id} className="border-b border-slate-100 dark:border-slate-800 py-2">
                        <button onClick={() => toggle(f.id)} className="w-full flex justify-between items-center text-sm">
                            <span className="font-semibold text-brandNavy dark:text-slate-200">
                                {f.name} <span className="text-xs text-slate-400 font-mono">({f.login_id})</span>
                            </span>
                            <span className="text-xs text-slate-400">
                                {f.sections_count} section(s){openId === f.id && sched.length > 0 ? ` · ${totalHours.toFixed(1)} hrs/week` : ''}
                            </span>
                        </button>
                        {openId === f.id && (
                            <table className="w-full text-xs mt-2">
                                <thead>
                                    <tr className="text-left text-slate-400">
                                        <th className="py-1 font-semibold">Subject</th><th className="font-semibold">Block</th>
                                        <th className="font-semibold">Days</th><th className="font-semibold">Time</th><th className="font-semibold">Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sched.map((s) => (
                                        <tr key={s.id} className="border-t border-slate-50 dark:border-slate-800">
                                            <td className="py-1.5">{s.subject_code} — {s.subject_title}</td>
                                            <td>{s.block_label}</td>
                                            <td>{s.days.join('/')}</td>
                                            <td>{s.start_time}–{s.end_time}</td>
                                            <td>
                                                {s.room_label}
                                                {s.online && <span className="ml-1 px-1.5 py-0.5 rounded bg-brandGold/15 text-brandGold font-bold text-[10px] uppercase">Online</span>}
                                            </td>
                                        </tr>
                                    ))}
                                    {sched.length === 0 && (
                                        <tr><td colSpan={5} className="py-2 text-slate-400">No sections loaded this school year.</td></tr>
                                    )}
                                </tbody>
                            </table>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
```

- [ ] **Step 3: Thread props through SubjectRow**

In `resources/js/curriculum/SubjectRow.jsx`, change the signature line to:

```jsx
export default function SubjectRow({ subject, allSubjects, schoolYear, faculty, rooms, onChanged, onListsChanged }) {
```

and the SectionEditor render (last line of JSX) to:

```jsx
            {open && <SectionEditor subject={subject} schoolYear={schoolYear} faculty={faculty} rooms={rooms}
                onChanged={onChanged} onListsChanged={onListsChanged} />}
```

- [ ] **Step 4: Wire curriculum-app**

In `resources/js/curriculum-app.jsx`:

1. Add the import:

```jsx
import FacultyLoading from './curriculum/FacultyLoading';
```

2. Add state next to the other `useState` calls:

```jsx
    const [faculty, setFaculty] = useState([]);
    const [rooms, setRooms] = useState([]);
    const [view, setView] = useState('curriculum'); // 'curriculum' | 'loading'
```

3. Add a list loader and call it on mount and when switching to the loading view (place after the programs `useEffect`):

```jsx
    const loadLists = useCallback(() => {
        api.get('/admin/faculty').then((res) => setFaculty(res.data.faculty));
        api.get('/admin/rooms').then((res) => setRooms(res.data.rooms));
    }, []);

    useEffect(loadLists, [loadLists]);
    useEffect(() => { if (view === 'loading') loadLists(); }, [view, loadLists]);
```

4. In the header row, immediately before the Change of Matriculation toggle button, add the view switcher:

```jsx
                <div className="flex gap-2">
                    <button onClick={() => setView('curriculum')}
                        className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                            ${view === 'curriculum' ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        Curriculum
                    </button>
                    <button onClick={() => setView('loading')}
                        className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                            ${view === 'loading' ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        Faculty Loading
                    </button>
                </div>
```

5. Wrap the existing `{active && (...)}` panel so it only shows in curriculum view, and add the loading view. Change `{active && (` to:

```jsx
            {view === 'loading' && <FacultyLoading faculty={faculty} />}

            {view === 'curriculum' && active && (
```

6. Update the SubjectRow usage to pass the new props:

```jsx
                        <SubjectRow key={subject.id} subject={subject} allSubjects={subjects}
                            schoolYear={SCHOOL_YEAR} faculty={faculty} rooms={rooms}
                            onChanged={loadSubjects} onListsChanged={loadLists} />
```

- [ ] **Step 5: Build and run the suite**

Run: `npm run build` — success, no errors.
Run: `php artisan test` — full suite still green (95 passed; this task adds no PHP).

- [ ] **Step 6: Commit**

```bash
git add resources/js/curriculum-app.jsx resources/js/curriculum/SubjectRow.jsx resources/js/curriculum/SectionEditor.jsx resources/js/curriculum/FacultyLoading.jsx
git commit -m "feat: faculty and room assignment UI with loading view"
```

---

### Task 6: Faculty schedule page, login redirect, notif bell, final verification

**Files:**
- Create: `resources/views/faculty/schedule.blade.php`
- Modify: `routes/web.php` (new `role:faculty` group after the chair group)
- Modify: `app/Http/Controllers/AuthController.php` (`handleRoleRedirection`)
- Modify: `resources/views/partials/notif-script.blade.php` (faculty branch)
- Test: `tests/Feature/FacultySchedulePageTest.php`

**Interfaces:**
- Consumes: Task 1 (`User::taughtSections()`, `Section::roomLabel()`, `Room::isPhysical()`), `Setting::get('school_year', '2026-2027')`.
- Produces: route `faculty.schedule` (`GET /faculty/schedule`, middleware `role:faculty`); faculty logins land there; faculty notif-bell branch.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/FacultySchedulePageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultySchedulePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_sees_own_sections_only(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $other = User::factory()->create(['role' => 'faculty']);
        $mine = Subject::factory()->create(['code' => 'MINE101']);
        $theirs = Subject::factory()->create(['code' => 'THEIRS101']);
        Section::factory()->create(['subject_id' => $mine->id, 'faculty_id' => $prof->id]);
        Section::factory()->create(['subject_id' => $theirs->id, 'faculty_id' => $other->id, 'days' => ['T']]);

        $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()
            ->assertSee('MINE101')
            ->assertDontSee('THEIRS101');
    }

    public function test_faculty_page_shows_empty_state_without_load(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()
            ->assertSee('No teaching load');
    }

    public function test_students_cannot_view_faculty_schedule(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/faculty/schedule')->assertForbidden();
    }

    public function test_faculty_cannot_access_admin_api(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($prof)->getJson('/api/admin/programs')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter FacultySchedulePageTest`
Expected: FAIL — 404 on `/faculty/schedule`.

- [ ] **Step 3: Add the route**

In `routes/web.php`, directly after the line `}); // end role:chair`, add:

```php
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
```

- [ ] **Step 4: Create the Blade view**

`resources/views/faculty/schedule.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Teaching Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
                    }
                }
            }
        }
    </script>
    <script>
        function updateThemeIcon() {
            var icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="min-h-screen flex flex-col">

    <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover">
            <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">My Teaching Schedule</h2>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.notif-bell')
            <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
            </button>
            @include('partials.profile-menu', ['roleLabel' => 'Faculty'])
        </div>
    </header>

    <main class="flex-1 p-6 lg:p-10 max-w-5xl w-full mx-auto space-y-6">
        <div>
            <h1 class="text-3xl font-black text-brandNavy dark:text-white">Weekly Teaching Schedule</h1>
            <p class="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                {{ Auth::user()->name }} — A.Y. {{ \App\Models\Setting::get('school_year', '2026-2027') }}
            </p>
        </div>

        @php
            $dayNames = ['M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
            $byDay = [];
            foreach ($sections as $section) {
                foreach ($section->days as $day) {
                    $byDay[$day][] = $section;
                }
            }
        @endphp

        @if ($sections->isEmpty())
            <div class="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-10 text-center">
                <i class="fa-solid fa-chalkboard-user text-3xl text-brandNavy/20 dark:text-slate-600 mb-3"></i>
                <p class="text-sm text-brandNavy/60 dark:text-slate-400">No teaching load assigned yet for this school year.</p>
            </div>
        @else
            @foreach ($dayNames as $key => $label)
                @if (!empty($byDay[$key]))
                    <div class="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                            <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                                <i class="fa-solid fa-calendar-day mr-2 text-brandGreen"></i>{{ $label }}
                            </span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($byDay[$key] as $section)
                                <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <div>
                                        <span class="font-bold font-mono">{{ $section->subject->code }}</span>
                                        <span class="text-brandNavy/70 dark:text-slate-400">— {{ $section->subject->title }}</span>
                                        <span class="ml-2 text-xs text-brandNavy/50 dark:text-slate-500">Block {{ $section->block_label }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="font-semibold">{{ $section->start_time }}–{{ $section->end_time }}</span>
                                        <span class="text-brandNavy/60 dark:text-slate-400">{{ $section->roomLabel() }}</span>
                                        @if ($section->roomEntity && ! $section->roomEntity->isPhysical())
                                            <span class="px-1.5 py-0.5 rounded bg-brandGold/15 text-brandGold font-bold text-[10px] uppercase">Online</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </main>
</div>

@include('partials.notif-script')
</body>
</html>
```

- [ ] **Step 5: Login redirect and notif bell**

In `app/Http/Controllers/AuthController.php`, inside `handleRoleRedirection`'s `switch`, add after the `chair` case:

```php
            case 'faculty':
                $targetRoute = 'faculty.schedule';
                break;
```

In `resources/views/partials/notif-script.blade.php`, the chair branch ends with the `$pendingChangeCount` block followed by a closing `}`. Immediately after that closing brace (i.e., extending the `elseif` chain before the final `@endphp` region's closing `}`), add:

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

(The file currently ends the chair branch with `}` then `@endphp` region code — the new `} elseif` replaces that closing `}`, and the branch supplies its own final `}`.)

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter FacultySchedulePageTest`
Expected: PASS (4 tests).

- [ ] **Step 7: Full verification**

Run: `php artisan view:clear && php artisan view:cache` — no Blade syntax errors.
Run: `php artisan test` — all PASS (expect 99 passed).
Run: `npm run build` — success.
Run: `php artisan migrate:fresh --seed` — no errors (resets local dev DB; expected).

- [ ] **Step 8: Commit**

```bash
git add resources/views/faculty/schedule.blade.php routes/web.php app/Http/Controllers/AuthController.php resources/views/partials/notif-script.blade.php tests/Feature/FacultySchedulePageTest.php
git commit -m "feat: faculty schedule page, login redirect, and notif bell"
```

---

## Demo walkthrough (manual, after all tasks)

1. Log in as `admin01` → `/admin/curriculum` → open a subject's sections → assign Prof. Liza Ramos and a room to a section; try assigning her to an overlapping section → red conflict banner.
2. Switch to the "Faculty Loading" view → expand a professor → weekly load table with hours.
3. Log in as `faculty01` (password `password123`) → lands on `/faculty/schedule` → weekly day-grouped schedule, Online badge on virtual-room sections, bell shows the load summary.
4. `/admin/audit` shows `Faculty Created` / `Room Created` / `Curriculum Updated` entries.
