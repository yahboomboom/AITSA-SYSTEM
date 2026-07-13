# Real Payments (PayMongo Sandbox) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Students pay a real, computed balance through PayMongo's test-mode hosted checkout; every payment is a ledger row; a verified full payment auto-approves the cashier clearance; the cashier configures fees and discounts on a new billing page.

**Architecture:** `FeeAssessmentService` computes the balance (planned units × tuition rate − discount + misc fee − settled payments). `PayMongoService` wraps the Checkout Sessions API (create + retrieve). `PaymentService` orchestrates checkout (Pending ledger row → redirect to hosted page) and verification (retrieve session → settle row → approve cashier clearance when fully paid). Payment is trusted **only** after server-side verification — never from the redirect alone. A new `/cashier/billing` Blade page owns fees, discount types, and per-student discount assignment.

**Tech Stack:** Laravel 12, PHPUnit (RefreshDatabase, SQLite `:memory:`, `Http::fake`), Blade + CDN Tailwind, PayMongo Checkout Sessions API (test mode). Guzzle already in vendor.

## Global Constraints

- Fee settings keys: `tuition_per_unit` (default `'300'`), `misc_fee` (default `'1500'`) via `Setting::get/put`.
- Discount percent applies to **tuition only**; misc fee always fully payable. `discount_types.percent` is 0–100.
- Checkout is **full balance only**; PayMongo amounts are integer **centavos**.
- Ledger statuses used: `Pending`, `Settled`, `Cancelled`, `Failed`. Gateway rows have `gateway = 'paymongo'`, null `processed_by`.
- Never touch `clearances.cashier_status` except in `PaymentService` verification (and the existing cashier walk-in flow, untouched).
- Audit actions: `Payment Settled`, `Cashier Cleared (Gateway)`, `Fees Updated`, `Discount Type Created`, `Discount Type Deleted`, `Discount Assigned`.
- Brand colors: brandNavy `#0B3C5D`, brandGreen `#1D7A46`, brandGold `#E2A700`; dark-mode variants required. Notif fields flow through existing `escNotif()` automatically.
- Run tests with `php artisan test` (optionally `--filter Name`). Suite currently at 118 passed.
- TDD: write tests, see them fail, implement, see them pass. Commit at the end of each task.
- No real network in tests: `phpunit.xml` sets a dummy `PAYMONGO_SECRET_KEY`; every PayMongo call is faked with `Http::fake()`.

---

### Task 1: Schema — discount types, gateway columns, models, factories

**Files:**
- Create: `database/migrations/2026_07_13_100001_create_discount_types_table.php`
- Create: `database/migrations/2026_07_13_100002_add_gateway_columns_to_transaction_ledgers.php`
- Create: `database/migrations/2026_07_13_100003_add_discount_type_id_to_users_table.php`
- Create: `app/Models/DiscountType.php`
- Create: `database/factories/DiscountTypeFactory.php`
- Create: `database/factories/TransactionLedgerFactory.php`
- Modify: `app/Models/TransactionLedger.php` (fillable + casts)
- Modify: `app/Models/User.php` (fillable + `discountType()` relation)
- Modify: `database/seeders/ProgramSeeder.php` (fee settings defaults)
- Modify: `database/seeders/DatabaseSeeder.php` (demo discount type)
- Test: `tests/Unit/DiscountTypeTest.php`

**Interfaces:**
- Consumes: existing `User`, `TransactionLedger`, `Setting` models.
- Produces: `DiscountType` model (`name`, `percent`; `students(): HasMany`); `User::discountType(): BelongsTo` + fillable `discount_type_id`; `TransactionLedger` fillable gains `gateway`, `checkout_session_id`, `paid_at` (datetime cast); `TransactionLedgerFactory` defaults (student user, `PMG-…` reference, 5000.00, `Pending`, gateway `paymongo`, fake session id, null processed_by); `DiscountTypeFactory` (unique name, percent 25).

- [ ] **Step 1: Write the failing test**

`tests/Unit/DiscountTypeTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\DiscountType;
use App\Models\TransactionLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_type_assignment_and_null_on_delete(): void
    {
        $type = DiscountType::factory()->create(['name' => 'Academic Scholar', 'percent' => 50]);
        $student = User::factory()->create(['role' => 'student', 'discount_type_id' => $type->id]);

        $this->assertTrue($student->discountType->is($type));
        $this->assertTrue($type->students()->whereKey($student->id)->exists());

        $type->delete();
        $this->assertNull($student->fresh()->discount_type_id);
    }

    public function test_gateway_ledger_row_defaults(): void
    {
        $row = TransactionLedger::factory()->create();

        $this->assertSame('paymongo', $row->gateway);
        $this->assertSame('Pending', $row->status);
        $this->assertNull($row->processed_by);
        $this->assertNull($row->paid_at);

        $row->update(['status' => 'Settled', 'paid_at' => now()]);
        $this->assertNotNull($row->fresh()->paid_at);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter DiscountTypeTest`
Expected: FAIL — `Class "App\Models\DiscountType" not found`.

- [ ] **Step 3: Create migrations, model, factories, relations**

`database/migrations/2026_07_13_100001_create_discount_types_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('percent'); // 0-100, applies to tuition only
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_types');
    }
};
```

`database/migrations/2026_07_13_100002_add_gateway_columns_to_transaction_ledgers.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_ledgers', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('status');
            $table->string('checkout_session_id')->nullable()->index()->after('gateway');
            $table->timestamp('paid_at')->nullable()->after('checkout_session_id');
            $table->unsignedBigInteger('processed_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_ledgers', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'checkout_session_id', 'paid_at']);
        });
    }
};
```

`database/migrations/2026_07_13_100003_add_discount_type_id_to_users_table.php`:

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
            $table->foreignId('discount_type_id')->nullable()->constrained('discount_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_type_id');
        });
    }
};
```

`app/Models/DiscountType.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'percent'];

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'discount_type_id');
    }
}
```

`database/factories/DiscountTypeFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true) . ' Discount',
            'percent' => 25,
        ];
    }
}
```

`database/factories/TransactionLedgerFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionLedgerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'reference_no' => 'PMG-' . strtoupper(Str::random(10)),
            'amount' => 5000.00,
            'status' => 'Pending',
            'gateway' => 'paymongo',
            'checkout_session_id' => 'cs_' . Str::random(24),
            'paid_at' => null,
            'processed_by' => null,
            'remarks' => null,
        ];
    }
}
```

In `app/Models/TransactionLedger.php`, replace the `$fillable` array and add casts:

```php
    protected $fillable = [
        'user_id',
        'reference_no',
        'amount',
        'status',
        'gateway',
        'checkout_session_id',
        'paid_at',
        'processed_by',
        'remarks'
    ];

    protected $casts = ['paid_at' => 'datetime'];
```

In `app/Models/User.php`:
1. In `$fillable`, after the line `'role',` add a line `'discount_type_id',`.
2. Add the import `use Illuminate\Database\Eloquent\Relations\BelongsTo;` if not present (check the existing imports at the top of the file).
3. Directly after the `documentSubmissions()` method, add:

```php
    public function discountType(): BelongsTo
    {
        return $this->belongsTo(DiscountType::class);
    }
```

In `database/seeders/ProgramSeeder.php`, directly after these existing lines:

```php
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
```

add:

```php
        Setting::put('tuition_per_unit', '300');
        Setting::put('misc_fee', '1500');
```

In `database/seeders/DatabaseSeeder.php`, the file ends with:

```php
            $section->save();
        }
    }
}
```

Change that ending to:

```php
            $section->save();
        }

        // Demo discount type for the cashier billing page
        \App\Models\DiscountType::firstOrCreate(['name' => 'Academic Scholar'], ['percent' => 50]);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter DiscountTypeTest`
Expected: PASS (2 tests). Then `php artisan test` — full suite green (120 passed).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_13_100001_create_discount_types_table.php database/migrations/2026_07_13_100002_add_gateway_columns_to_transaction_ledgers.php database/migrations/2026_07_13_100003_add_discount_type_id_to_users_table.php app/Models/DiscountType.php database/factories/DiscountTypeFactory.php database/factories/TransactionLedgerFactory.php app/Models/TransactionLedger.php app/Models/User.php database/seeders/ProgramSeeder.php database/seeders/DatabaseSeeder.php tests/Unit/DiscountTypeTest.php
git commit -m "feat: discount types and gateway ledger schema"
```

---

### Task 2: FeeAssessmentService

**Files:**
- Create: `app/Services/FeeAssessmentService.php`
- Test: `tests/Unit/FeeAssessmentServiceTest.php`

**Interfaces:**
- Consumes: `EnrollmentService::activeEnrollment/blockFor/catalogueFor`, `Setting::get`, `User::discountType/program/isIrregularStudent`, `TransactionLedger`.
- Produces: `FeeAssessmentService::breakdownFor(User $user): array` with keys `units` (int), `rate` (int), `tuition` (float), `discount_name` (?string), `discount_percent` (int), `discount_amount` (float), `misc` (int), `assessment` (float), `paid` (float), `balance` (float, ≥0, 2dp), `fully_paid` (bool).

- [ ] **Step 1: Write the failing tests**

`tests/Unit/FeeAssessmentServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\DiscountType;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\FeeAssessmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year'],
            $overrides
        ));
    }

    private function makeBlockSection(int $units = 3): Section
    {
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => $units]);

        return Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
    }

    public function test_regular_student_assessed_on_block_load(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(3);
        $this->makeBlockSection(2);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame(5, $b['units']);
        $this->assertSame(300, $b['rate']);
        $this->assertEqualsWithDelta(1500.0, (float) $b['tuition'], 0.001);
        $this->assertEqualsWithDelta(3000.0, (float) $b['assessment'], 0.001); // 1500 tuition + 1500 misc
        $this->assertEqualsWithDelta(3000.0, (float) $b['balance'], 0.001);
        $this->assertFalse($b['fully_paid']);
    }

    public function test_enrolled_student_assessed_on_enrolled_load(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(3); // block says 3 units...
        $enrolledSection = $this->makeBlockSection(4);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id, 'status' => 'enrolled',
            'school_year' => '2026-2027', 'semester' => 1,
        ]);
        $enrollment->sections()->attach($enrolledSection->id);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame(4, $b['units']); // ...but the actual enrollment wins
    }

    public function test_irregular_student_assessed_on_eligible_subjects(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $program = Program::where('code', 'BSOA')->first();
        $failed = Subject::factory()->for($program)->create(['semester' => 1, 'units' => 3, 'code' => 'FAIL1']);
        Subject::factory()->for($program)->create(['semester' => 1, 'units' => 2, 'code' => 'ELIG1']);
        $student->grades()->create(['subject_code' => 'FAIL1', 'status' => 'Failed']);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        // FAIL1 (retake, 3u) + ELIG1 (2u) are both eligible
        $this->assertSame(5, $b['units']);
    }

    public function test_discount_applies_to_tuition_only(): void
    {
        $this->seed(ProgramSeeder::class);
        $type = DiscountType::factory()->create(['name' => 'Academic Scholar', 'percent' => 50]);
        $student = $this->makeStudent(['discount_type_id' => $type->id]);
        $this->makeBlockSection(10); // 3000 tuition

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame('Academic Scholar', $b['discount_name']);
        $this->assertSame(50, $b['discount_percent']);
        $this->assertEqualsWithDelta(1500.0, (float) $b['discount_amount'], 0.001);
        // 3000 - 1500 + 1500 misc = 3000; misc untouched by the discount
        $this->assertEqualsWithDelta(3000.0, (float) $b['assessment'], 0.001);
    }

    public function test_settled_payments_reduce_balance_and_full_payment_flags(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(5); // assessment 1500 + 1500 = 3000
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 3000.00,
        ]);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Pending', 'amount' => 999.00, // ignored
        ]);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertEqualsWithDelta(3000.0, (float) $b['paid'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $b['balance'], 0.001);
        $this->assertTrue($b['fully_paid']);
    }

    public function test_overpayment_floors_balance_at_zero(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(3);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 99999.00,
        ]);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertEqualsWithDelta(0.0, (float) $b['balance'], 0.001);
    }

    public function test_student_without_program_owes_misc_only(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent(['major' => null]);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame(0, $b['units']);
        $this->assertEqualsWithDelta(1500.0, (float) $b['assessment'], 0.001);
        $this->assertFalse($b['fully_paid']);
    }
}
```

Note: `User::grades()` is the existing HasMany to `StudentGrade` (used by `isIrregularStudent()`). Creating a grade via the relation with only `subject_code` + `status` is valid — both are fillable and `final_grade` is nullable (verified against `2026_07_09_000001_create_student_grades_table.php`).

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter FeeAssessmentServiceTest`
Expected: FAIL — `Class "App\Services\FeeAssessmentService" not found`.

- [ ] **Step 3: Implement the service**

`app/Services/FeeAssessmentService.php`:

```php
<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Setting;
use App\Models\TransactionLedger;
use App\Models\User;

class FeeAssessmentService
{
    public function __construct(private EnrollmentService $enrollment)
    {
    }

    /**
     * Full money picture for a student:
     * units, rate, tuition, discount_name, discount_percent, discount_amount,
     * misc, assessment, paid, balance, fully_paid.
     */
    public function breakdownFor(User $user): array
    {
        $rate = (int) Setting::get('tuition_per_unit', '300');
        $misc = (int) Setting::get('misc_fee', '1500');

        $units = $this->plannedUnits($user);
        $tuition = round($units * $rate, 2);

        $discountType = $user->discountType;
        $percent = $discountType->percent ?? 0;
        $discountAmount = round($tuition * $percent / 100, 2);

        $assessment = round($tuition - $discountAmount + $misc, 2);
        $paid = round((float) TransactionLedger::where('user_id', $user->id)
            ->where('status', 'Settled')->sum('amount'), 2);
        $balance = round(max($assessment - $paid, 0), 2);

        return [
            'units' => $units,
            'rate' => $rate,
            'tuition' => $tuition,
            'discount_name' => $discountType->name ?? null,
            'discount_percent' => $percent,
            'discount_amount' => $discountAmount,
            'misc' => $misc,
            'assessment' => $assessment,
            'paid' => $paid,
            'balance' => $balance,
            'fully_paid' => $balance == 0.0 && $assessment > 0,
        ];
    }

    /**
     * Units the student is set to take this term: their committed enrollment
     * if one exists, otherwise the regular block plan, otherwise the
     * irregular student's eligible subjects.
     */
    private function plannedUnits(User $user): int
    {
        $active = $this->enrollment->activeEnrollment($user);
        if ($active && $active->status !== 'rejected') {
            return (int) $active->sections()->with('subject')->get()
                ->sum(fn (Section $s) => $s->subject->units);
        }

        if (! $user->program()) {
            return 0;
        }

        if (! $user->isIrregularStudent()) {
            $block = $this->enrollment->blockFor($user);

            return $block
                ? (int) $block['sections']->sum(fn (Section $s) => $s->subject->units)
                : 0;
        }

        return (int) $this->enrollment->catalogueFor($user)
            ->where('eligible', true)
            ->sum('units');
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter FeeAssessmentServiceTest`
Expected: PASS (7 tests). Then `php artisan test` — full suite green (127 passed).

- [ ] **Step 5: Commit**

```bash
git add app/Services/FeeAssessmentService.php tests/Unit/FeeAssessmentServiceTest.php
git commit -m "feat: fee assessment service with discounts"
```

---

### Task 3: PayMongoService + configuration

**Files:**
- Create: `app/Services/PayMongoService.php`
- Create: `app/Exceptions/PaymentGatewayException.php`
- Modify: `config/services.php` (paymongo block)
- Modify: `phpunit.xml` (dummy test key)
- Modify: `.env.example` (document the key)
- Test: `tests/Unit/PayMongoServiceTest.php`

**Interfaces:**
- Consumes: `config('services.paymongo.secret')`, `config('services.paymongo.base_url')`; routes `ledger.payment.return` / `ledger.payment.cancel` **by name** (defined in Task 4 — until then tests must register throwaway routes with those names, shown below).
- Produces: `PayMongoService::createCheckoutSession(User $user, int $amountCentavos, string $description): array{id: string, checkout_url: string}`; `retrieveCheckoutSession(string $id): array` (the raw `data` object); `sessionIsPaid(array $session): bool`; `PaymentGatewayException` (extends `RuntimeException`, message safe to flash).

- [ ] **Step 1: Write the failing tests**

`tests/Unit/PayMongoServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Exceptions\PaymentGatewayException;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PayMongoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Task 4 defines the real routes; the service only needs the names to exist.
        if (! Route::has('ledger.payment.return')) {
            Route::get('/ledger/payment/return', fn () => 'ok')->name('ledger.payment.return');
            Route::get('/ledger/payment/cancel', fn () => 'ok')->name('ledger.payment.cancel');
        }
    }

    public function test_create_checkout_session_posts_line_item_and_returns_url(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
                ],
            ], 200),
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $session = app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');

        $this->assertSame('cs_test_123', $session['id']);
        $this->assertSame('https://checkout.paymongo.com/cs_test_123', $session['checkout_url']);
        Http::assertSent(function ($request) {
            $attrs = $request->data()['data']['attributes'];

            return str_contains($request->url(), '/checkout_sessions')
                && $attrs['line_items'][0]['amount'] === 300000
                && $attrs['line_items'][0]['currency'] === 'PHP'
                && $attrs['payment_method_types'] === ['gcash', 'card', 'paymaya'];
        });
    }

    public function test_api_failure_throws_gateway_exception(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $user = User::factory()->create(['role' => 'student']);

        $this->expectException(PaymentGatewayException::class);
        app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');
    }

    public function test_missing_key_throws_gateway_exception(): void
    {
        config(['services.paymongo.secret' => null]);
        $user = User::factory()->create(['role' => 'student']);

        $this->expectException(PaymentGatewayException::class);
        app(PayMongoService::class)->createCheckoutSession($user, 300000, 'AITSA Tuition');
    }

    public function test_session_is_paid_inspects_payments(): void
    {
        $service = app(PayMongoService::class);

        $paid = ['attributes' => ['payments' => [['attributes' => ['status' => 'paid']]]]];
        $unpaid = ['attributes' => ['payments' => []]];
        $failed = ['attributes' => ['payments' => [['attributes' => ['status' => 'failed']]]]];

        $this->assertTrue($service->sessionIsPaid($paid));
        $this->assertFalse($service->sessionIsPaid($unpaid));
        $this->assertFalse($service->sessionIsPaid($failed));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter PayMongoServiceTest`
Expected: FAIL — `Class "App\Services\PayMongoService" not found`.

- [ ] **Step 3: Implement config, exception, service**

In `config/services.php`, after the `'slack' => [...],` block, add:

```php
    'paymongo' => [
        'secret' => env('PAYMONGO_SECRET_KEY'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
    ],
```

In `phpunit.xml`, after the line `<env name="NIGHTWATCH_ENABLED" value="false"/>`, add:

```xml
        <env name="PAYMONGO_SECRET_KEY" value="sk_test_dummy"/>
```

In `.env.example`, append at the end of the file:

```
PAYMONGO_SECRET_KEY=
```

`app/Exceptions/PaymentGatewayException.php`:

```php
<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentGatewayException extends RuntimeException
{
}
```

`app/Services/PayMongoService.php`:

```php
<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PayMongoService
{
    /** @return array{id: string, checkout_url: string} */
    public function createCheckoutSession(User $user, int $amountCentavos, string $description): array
    {
        $response = $this->client()->post('/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'line_items' => [[
                        'name' => $description,
                        'amount' => $amountCentavos,
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => ['gcash', 'card', 'paymaya'],
                    'description' => $description,
                    'success_url' => route('ledger.payment.return'),
                    'cancel_url' => route('ledger.payment.cancel'),
                    'metadata' => ['user_id' => (string) $user->id, 'login_id' => (string) ($user->login_id ?? '')],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Payment gateway is unavailable — please try again or pay at the cashier window.');
        }

        return [
            'id' => (string) $response->json('data.id'),
            'checkout_url' => (string) $response->json('data.attributes.checkout_url'),
        ];
    }

    /** Raw `data` object of the checkout session. */
    public function retrieveCheckoutSession(string $id): array
    {
        $response = $this->client()->get('/checkout_sessions/' . $id);

        if ($response->failed()) {
            throw new PaymentGatewayException('Could not reach the payment gateway to verify. Please try again.');
        }

        return (array) $response->json('data');
    }

    public function sessionIsPaid(array $session): bool
    {
        foreach ($session['attributes']['payments'] ?? [] as $payment) {
            if (($payment['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return false;
    }

    private function client(): PendingRequest
    {
        $secret = config('services.paymongo.secret');
        if (! $secret) {
            throw new PaymentGatewayException('Payment gateway is not configured. Please pay at the cashier window.');
        }

        return Http::baseUrl((string) config('services.paymongo.base_url'))
            ->withBasicAuth($secret, '')
            ->acceptJson()
            ->timeout(15);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter PayMongoServiceTest`
Expected: PASS (4 tests). Then `php artisan test` — full suite green (131 passed).

- [ ] **Step 5: Commit**

```bash
git add app/Services/PayMongoService.php app/Exceptions/PaymentGatewayException.php config/services.php phpunit.xml .env.example tests/Unit/PayMongoServiceTest.php
git commit -m "feat: paymongo checkout session client"
```

---

### Task 4: PaymentService + checkout/return/cancel/verify routes (mock-pay removed)

**Files:**
- Create: `app/Services/PaymentService.php`
- Modify: `routes/web.php` (imports; `GET /ledger` route ~line 137; replace `POST /ledger/mock-pay` with the four new routes)
- Test: `tests/Feature/PaymentCheckoutTest.php`
- Test: `tests/Feature/PaymentReturnTest.php`

**Interfaces:**
- Consumes: Tasks 1–3 (`TransactionLedger` gateway columns, `FeeAssessmentService::breakdownFor`, `PayMongoService`, `PaymentGatewayException`).
- Produces: `PaymentService::startCheckout(User $user, float $balance): string` (returns hosted checkout URL; creates the Pending row; cancels stale Pending rows; marks the row `Failed` and rethrows on gateway error); `PaymentService::verifyLatestPending(User $user): array{ok: bool, message: string}` (settles + auto-approves cashier when fully paid; idempotent). Routes: `POST /ledger/checkout` (`ledger.checkout`), `GET /ledger/payment/return` (`ledger.payment.return`), `GET /ledger/payment/cancel` (`ledger.payment.cancel`), `POST /ledger/verify` (`ledger.verify`). `GET /ledger` view data: `$clearance`, `$breakdown`, `$history` (student's ledger rows, newest first), `$hasPendingGateway` (bool). `ledger.mockPay` no longer exists.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/PaymentCheckoutTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithBalance(): User
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);

        return $student; // balance: 5*300 + 1500 = 3000.00
    }

    private function fakeCheckoutCreated(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => ['id' => 'cs_test_abc', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_abc']],
            ], 200),
        ]);
    }

    public function test_checkout_creates_pending_row_and_redirects_to_gateway(): void
    {
        $this->fakeCheckoutCreated();
        $student = $this->studentWithBalance();

        $response = $this->actingAs($student)->post('/ledger/checkout');

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_abc');
        $row = TransactionLedger::where('user_id', $student->id)->first();
        $this->assertSame('Pending', $row->status);
        $this->assertSame('paymongo', $row->gateway);
        $this->assertSame('cs_test_abc', $row->checkout_session_id);
        $this->assertEqualsWithDelta(3000.0, (float) $row->amount, 0.001);
        Http::assertSent(fn ($r) => $r->data()['data']['attributes']['line_items'][0]['amount'] === 300000);
    }

    public function test_zero_balance_blocks_checkout(): void
    {
        Http::fake();
        $student = $this->studentWithBalance();
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Settled', 'amount' => 3000.00]);

        $response = $this->actingAs($student)->from('/ledger')->post('/ledger/checkout');

        $response->assertRedirect('/ledger');
        $response->assertSessionHas('error');
        $this->assertSame(1, TransactionLedger::count()); // no new row
        Http::assertNothingSent();
    }

    public function test_new_checkout_cancels_stale_pending_rows(): void
    {
        $this->fakeCheckoutCreated();
        $student = $this->studentWithBalance();
        $stale = TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Pending']);

        $this->actingAs($student)->post('/ledger/checkout');

        $this->assertSame('Cancelled', $stale->fresh()->status);
    }

    public function test_gateway_failure_marks_row_failed_with_error_flash(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $student = $this->studentWithBalance();

        $response = $this->actingAs($student)->from('/ledger')->post('/ledger/checkout');

        $response->assertRedirect('/ledger');
        $response->assertSessionHas('error');
        $this->assertSame('Failed', TransactionLedger::where('user_id', $student->id)->first()->status);
    }

    public function test_guest_is_redirected(): void
    {
        Http::fake();
        $this->post('/ledger/checkout')->assertRedirect();
        Http::assertNothingSent();
    }
}
```

`tests/Feature/PaymentReturnTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private TransactionLedger $row;

    private function setUpPendingPayment(): void
    {
        $this->seed(ProgramSeeder::class);
        $this->student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
        Clearance::create([
            'user_id' => $this->student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);
        $this->row = TransactionLedger::factory()->create([
            'user_id' => $this->student->id, 'status' => 'Pending',
            'amount' => 3000.00, 'checkout_session_id' => 'cs_test_abc',
        ]);
    }

    private function fakeSession(bool $paid): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_test_abc' => Http::response([
                'data' => [
                    'id' => 'cs_test_abc',
                    'attributes' => ['payments' => $paid ? [['attributes' => ['status' => 'paid']]] : []],
                ],
            ], 200),
        ]);
    }

    public function test_paid_session_settles_row_and_approves_cashier(): void
    {
        $this->setUpPendingPayment();
        $this->fakeSession(true);

        $response = $this->actingAs($this->student)->get('/ledger/payment/return');

        $response->assertRedirect(route('ledger'));
        $response->assertSessionHas('success');
        $row = $this->row->fresh();
        $this->assertSame('Settled', $row->status);
        $this->assertNotNull($row->paid_at);
        $this->assertSame('Approved', Clearance::where('user_id', $this->student->id)->first()->cashier_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Payment Settled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Cashier Cleared (Gateway)']);
    }

    public function test_unpaid_session_keeps_row_pending(): void
    {
        $this->setUpPendingPayment();
        $this->fakeSession(false);

        $response = $this->actingAs($this->student)->get('/ledger/payment/return');

        $response->assertRedirect(route('ledger'));
        $response->assertSessionHas('error');
        $this->assertSame('Pending', $this->row->fresh()->status);
        $this->assertSame('Pending', Clearance::where('user_id', $this->student->id)->first()->cashier_status);
    }

    public function test_cancel_route_cancels_pending_row(): void
    {
        $this->setUpPendingPayment();

        $response = $this->actingAs($this->student)->get('/ledger/payment/cancel');

        $response->assertRedirect(route('ledger'));
        $this->assertSame('Cancelled', $this->row->fresh()->status);
    }

    public function test_verify_route_settles_like_return(): void
    {
        $this->setUpPendingPayment();
        $this->fakeSession(true);

        $this->actingAs($this->student)->post('/ledger/verify')->assertRedirect(route('ledger'));

        $this->assertSame('Settled', $this->row->fresh()->status);
    }

    public function test_return_with_no_pending_row_is_a_noop(): void
    {
        $this->setUpPendingPayment();
        $this->row->update(['status' => 'Settled', 'paid_at' => now()]);
        Http::fake();

        $response = $this->actingAs($this->student)->get('/ledger/payment/return');

        $response->assertRedirect(route('ledger'));
        Http::assertNothingSent();
        $this->assertSame(1, TransactionLedger::count());
    }

    public function test_partial_settlement_does_not_approve_cashier(): void
    {
        $this->setUpPendingPayment();
        // Pretend the pending checkout was for less than the full balance
        $this->row->update(['amount' => 100.00]);
        $this->fakeSession(true);

        $this->actingAs($this->student)->get('/ledger/payment/return');

        $this->assertSame('Settled', $this->row->fresh()->status);
        $this->assertSame('Pending', Clearance::where('user_id', $this->student->id)->first()->cashier_status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'Cashier Cleared (Gateway)']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter "PaymentCheckoutTest|PaymentReturnTest"`
Expected: FAIL — `POST /ledger/checkout` returns 405/404 (route missing).

- [ ] **Step 3: Implement PaymentService**

`app/Services/PaymentService.php`:

```php
<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\TransactionLedger;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PayMongoService $gateway,
        private FeeAssessmentService $fees,
    ) {
    }

    /**
     * Start a full-balance checkout. Returns the hosted checkout URL.
     * Cancels stale Pending rows first; on gateway failure the fresh row
     * is marked Failed and the exception is rethrown for the route to flash.
     */
    public function startCheckout(User $user, float $balance): string
    {
        TransactionLedger::where('user_id', $user->id)
            ->where('gateway', 'paymongo')->where('status', 'Pending')
            ->update(['status' => 'Cancelled']);

        $row = TransactionLedger::create([
            'user_id' => $user->id,
            'reference_no' => 'PMG-' . strtoupper(Str::random(10)),
            'amount' => $balance,
            'status' => 'Pending',
            'gateway' => 'paymongo',
            'remarks' => 'Online payment via PayMongo checkout.',
        ]);

        try {
            $session = $this->gateway->createCheckoutSession(
                $user,
                (int) round($balance * 100),
                'AITSA Tuition & Fees — ' . ($user->login_id ?? $user->name)
            );
        } catch (PaymentGatewayException $e) {
            $row->update(['status' => 'Failed']);
            throw $e;
        }

        $row->update(['checkout_session_id' => $session['id']]);

        return $session['checkout_url'];
    }

    /**
     * Verify the student's latest Pending gateway payment against PayMongo.
     * Settles the row and auto-approves the cashier clearance when the
     * balance reaches zero. Safe to call repeatedly.
     *
     * @return array{ok: bool, message: string}
     */
    public function verifyLatestPending(User $user): array
    {
        $row = TransactionLedger::where('user_id', $user->id)
            ->where('gateway', 'paymongo')->where('status', 'Pending')
            ->latest()->first();

        if (! $row || ! $row->checkout_session_id) {
            return ['ok' => false, 'message' => 'No pending online payment to verify.'];
        }

        $session = $this->gateway->retrieveCheckoutSession($row->checkout_session_id);
        if (! $this->gateway->sessionIsPaid($session)) {
            return ['ok' => false, 'message' => 'The gateway has not confirmed this payment yet. If you completed payment, try Verify Payment again in a moment.'];
        }

        $row->update(['status' => 'Settled', 'paid_at' => now()]);
        AuditLog::record('Payment Settled', 'Online payment of ₱' . number_format((float) $row->amount, 2) . ' settled via PayMongo for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . '). Ref ' . $row->reference_no . '.', 'TransactionLedger', $row->id);

        $message = 'Payment received — ₱' . number_format((float) $row->amount, 2) . ' settled.';

        if ($this->fees->breakdownFor($user)['fully_paid']) {
            $clearance = Clearance::where('user_id', $user->id)->first();
            if ($clearance && $clearance->cashier_status !== 'Approved') {
                $clearance->update(['cashier_status' => 'Approved']);
                AuditLog::record('Cashier Cleared (Gateway)', 'Cashier clearance auto-approved for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . ') after gateway-verified full payment.', 'Clearance', $clearance->id);
                $message .= ' Your cashier clearance has been approved.';
            }
        }

        return ['ok' => true, 'message' => $message];
    }

    public function cancelLatestPending(User $user): void
    {
        TransactionLedger::where('user_id', $user->id)
            ->where('gateway', 'paymongo')->where('status', 'Pending')
            ->latest()->first()?->update(['status' => 'Cancelled']);
    }
}
```

- [ ] **Step 4: Rewrite the ledger routes**

In `routes/web.php`, add to the imports block (alphabetical, after `use App\Models\StudentGrade;`):

```php
use App\Models\TransactionLedger;
```

and after `use App\Services\MatriculationChangeService;`:

```php
use App\Services\FeeAssessmentService;
use App\Services\PaymentService;
```

and after the `use App\Http\Controllers\AuthController;` line:

```php
use App\Exceptions\PaymentGatewayException;
```

Replace the whole `GET /ledger` route:

```php
    Route::get('/ledger', function () { 
        $user = Auth::user();
        $clearance = Clearance::where('user_id', $user?->id)->first();
        return view('payment', compact('clearance')); 
    })->name('ledger');
```

with:

```php
    Route::get('/ledger', function (FeeAssessmentService $fees) {
        $user = Auth::user();
        $clearance = Clearance::where('user_id', $user?->id)->first();
        $breakdown = $fees->breakdownFor($user);
        $history = TransactionLedger::where('user_id', $user->id)->latest()->get();
        $hasPendingGateway = $history->contains(fn ($row) => $row->gateway === 'paymongo' && $row->status === 'Pending');

        return view('payment', compact('clearance', 'breakdown', 'history', 'hasPendingGateway'));
    })->name('ledger');
```

Replace the whole `POST /ledger/mock-pay` route (the `ledger.mockPay` block) with:

```php
    Route::post('/ledger/checkout', function (FeeAssessmentService $fees, PaymentService $payments) {
        $user = Auth::user();
        $breakdown = $fees->breakdownFor($user);

        if ($breakdown['balance'] <= 0) {
            return redirect()->route('ledger')->with('error', 'You have no outstanding balance to pay.');
        }

        try {
            $url = $payments->startCheckout($user, (float) $breakdown['balance']);
        } catch (PaymentGatewayException $e) {
            return redirect()->route('ledger')->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    })->name('ledger.checkout');

    Route::get('/ledger/payment/return', function (PaymentService $payments) {
        try {
            $result = $payments->verifyLatestPending(Auth::user());
        } catch (PaymentGatewayException $e) {
            return redirect()->route('ledger')->with('error', $e->getMessage());
        }

        return redirect()->route('ledger')->with($result['ok'] ? 'success' : 'error', $result['message']);
    })->name('ledger.payment.return');

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
    })->name('ledger.verify');
```

Note: the `payment` view still references `route('ledger.mockPay')` at this point — Task 6 rewrites the view. To keep the suite green during this task, make the minimal view edit now: in `resources/views/payment.blade.php`, change the form line

```blade
                <form id="gatewayForm" action="{{ route('ledger.mockPay') }}" method="POST" class="grid grid-cols-2 gap-4">
```

to

```blade
                <form id="gatewayForm" action="{{ route('ledger.checkout') }}" method="POST" class="grid grid-cols-2 gap-4">
```

(The modal itself is removed in Task 6.)

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter "PaymentCheckoutTest|PaymentReturnTest"`
Expected: PASS (11 tests). Then `php artisan test` — full suite green (142 passed).

- [ ] **Step 6: Commit**

```bash
git add app/Services/PaymentService.php routes/web.php resources/views/payment.blade.php tests/Feature/PaymentCheckoutTest.php tests/Feature/PaymentReturnTest.php
git commit -m "feat: paymongo checkout, verification, and cashier auto-clearance"
```

---

### Task 5: Cashier billing page (fees, discount types, assignment)

**Files:**
- Modify: `routes/web.php` (routes inside the `role:cashier` group, after `cashier.accounts`)
- Create: `resources/views/cashier/billing.blade.php`
- Modify: `resources/views/cashier/dashboard.blade.php`, `resources/views/cashier/transactions.blade.php`, `resources/views/cashier/accounts.blade.php` (nav link)
- Test: `tests/Feature/CashierBillingTest.php`

**Interfaces:**
- Consumes: Task 1 (`DiscountType`), `Setting::get/put`, `AuditLog::record`.
- Produces: routes `cashier.billing` (GET), `cashier.billing.fees`, `cashier.billing.discounts`, `cashier.billing.discounts.delete`, `cashier.billing.assign`. View data: `$tuitionPerUnit`, `$miscFee`, `$discountTypes` (withCount students), `$students` (role student, ordered by name, with discountType).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/CashierBillingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\DiscountType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->create(['role' => 'cashier']);
    }

    public function test_billing_page_renders_with_fees_and_discounts(): void
    {
        DiscountType::factory()->create(['name' => 'Academic Scholar', 'percent' => 50]);
        User::factory()->create(['role' => 'student', 'name' => 'Test Student']);

        $response = $this->actingAs($this->cashier)->get('/cashier/billing');

        $response->assertOk()
            ->assertSee('Billing Configuration')
            ->assertSee('Academic Scholar')
            ->assertSee('Test Student');
    }

    public function test_cashier_can_update_fees(): void
    {
        $this->actingAs($this->cashier)
            ->post('/cashier/billing/fees', ['tuition_per_unit' => 450, 'misc_fee' => 2000])
            ->assertRedirect(route('cashier.billing'));

        $this->assertSame('450', Setting::get('tuition_per_unit'));
        $this->assertSame('2000', Setting::get('misc_fee'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Fees Updated']);
    }

    public function test_negative_fees_are_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->from('/cashier/billing')
            ->post('/cashier/billing/fees', ['tuition_per_unit' => -5, 'misc_fee' => 2000])
            ->assertSessionHasErrors('tuition_per_unit');
    }

    public function test_cashier_can_create_and_delete_discount_types(): void
    {
        $this->actingAs($this->cashier)
            ->post('/cashier/billing/discounts', ['name' => 'Sibling Discount', 'percent' => 10])
            ->assertRedirect(route('cashier.billing'));

        $type = DiscountType::where('name', 'Sibling Discount')->first();
        $this->assertNotNull($type);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Type Created']);

        $student = User::factory()->create(['role' => 'student', 'discount_type_id' => $type->id]);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/discounts/{$type->id}/delete")
            ->assertRedirect(route('cashier.billing'));

        $this->assertNull(DiscountType::find($type->id));
        $this->assertNull($student->fresh()->discount_type_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Type Deleted']);
    }

    public function test_invalid_percent_and_duplicate_name_are_rejected(): void
    {
        DiscountType::factory()->create(['name' => 'Academic Scholar']);

        $this->actingAs($this->cashier)->from('/cashier/billing')
            ->post('/cashier/billing/discounts', ['name' => 'Overkill', 'percent' => 150])
            ->assertSessionHasErrors('percent');

        $this->actingAs($this->cashier)->from('/cashier/billing')
            ->post('/cashier/billing/discounts', ['name' => 'Academic Scholar', 'percent' => 20])
            ->assertSessionHasErrors('name');
    }

    public function test_cashier_can_assign_and_clear_a_student_discount(): void
    {
        $type = DiscountType::factory()->create();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/students/{$student->id}/discount", ['discount_type_id' => $type->id])
            ->assertRedirect(route('cashier.billing'));
        $this->assertSame($type->id, $student->fresh()->discount_type_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Discount Assigned']);

        $this->actingAs($this->cashier)
            ->post("/cashier/billing/students/{$student->id}/discount", ['discount_type_id' => null])
            ->assertRedirect(route('cashier.billing'));
        $this->assertNull($student->fresh()->discount_type_id);
    }

    public function test_non_cashier_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/cashier/billing')->assertForbidden();
        $this->actingAs($student)->post('/cashier/billing/fees', ['tuition_per_unit' => 1, 'misc_fee' => 1])->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter CashierBillingTest`
Expected: FAIL — `GET /cashier/billing` 404.

- [ ] **Step 3: Add the routes**

In `routes/web.php`, add to the imports block (alphabetical, after `use App\Models\Clearance;` / before `use App\Models\DocumentSubmission;`):

```php
use App\Models\DiscountType;
```

Inside the `role:cashier` group, directly after the line

```php
    Route::get('/cashier/accounts', [AuthController::class, 'showCashierAccounts'])->name('cashier.accounts');
```

add:

```php
    // Billing configuration: fee rates, discount types, student assignment
    Route::get('/cashier/billing', function () {
        return view('cashier.billing', [
            'tuitionPerUnit' => (int) \App\Models\Setting::get('tuition_per_unit', '300'),
            'miscFee' => (int) \App\Models\Setting::get('misc_fee', '1500'),
            'discountTypes' => DiscountType::withCount('students')->orderBy('name')->get(),
            'students' => User::where('role', 'student')->with('discountType')->orderBy('name')->get(),
        ]);
    })->name('cashier.billing');

    Route::post('/cashier/billing/fees', function (Request $request) {
        $request->validate([
            'tuition_per_unit' => ['required', 'integer', 'min:0'],
            'misc_fee' => ['required', 'integer', 'min:0'],
        ]);

        \App\Models\Setting::put('tuition_per_unit', (string) $request->integer('tuition_per_unit'));
        \App\Models\Setting::put('misc_fee', (string) $request->integer('misc_fee'));
        AuditLog::record('Fees Updated', 'Cashier set tuition to ₱' . $request->integer('tuition_per_unit') . '/unit and misc fee to ₱' . $request->integer('misc_fee') . '.', 'Setting', null);

        return redirect()->route('cashier.billing')->with('success', 'Fee rates updated.');
    })->name('cashier.billing.fees');

    Route::post('/cashier/billing/discounts', function (Request $request) {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:discount_types,name'],
            'percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $type = DiscountType::create($request->only('name', 'percent'));
        AuditLog::record('Discount Type Created', 'Cashier created discount type "' . $type->name . '" (' . $type->percent . '%).', 'DiscountType', $type->id);

        return redirect()->route('cashier.billing')->with('success', 'Discount type added.');
    })->name('cashier.billing.discounts');

    Route::post('/cashier/billing/discounts/{type}/delete', function (DiscountType $type) {
        AuditLog::record('Discount Type Deleted', 'Cashier deleted discount type "' . $type->name . '" (' . $type->percent . '%).', 'DiscountType', $type->id);
        $type->delete();

        return redirect()->route('cashier.billing')->with('success', 'Discount type removed.');
    })->name('cashier.billing.discounts.delete');

    Route::post('/cashier/billing/students/{user}/discount', function (Request $request, User $user) {
        abort_unless($user->role === 'student', 404);
        $request->validate(['discount_type_id' => ['nullable', 'exists:discount_types,id']]);

        $user->update(['discount_type_id' => $request->input('discount_type_id') ?: null]);
        $label = $user->discountType->name ?? 'none';
        AuditLog::record('Discount Assigned', 'Cashier set discount for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . ') to ' . $label . '.', 'User', $user->id);

        return redirect()->route('cashier.billing')->with('success', 'Student discount updated.');
    })->name('cashier.billing.assign');
```

- [ ] **Step 4: Create the billing view and nav links**

`resources/views/cashier/billing.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Billing Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E'
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
            const isDark = document.documentElement.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <h1 class="text-base font-black text-brandNavy dark:text-white">AITSA <span class="text-xs text-brandGreen font-mono px-1.5 py-0.5 bg-brandGreen/10 rounded ml-1">Staff</span></h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Management</p>

                <a href="{{ route('cashier.dashboard') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium">
                    <span>Cashier Hub</span>
                </a>

                <a href="{{ route('cashier.transactions') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium">
                    <span>Transactions</span>
                </a>

                <a href="{{ route('cashier.accounts') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium">
                    <span>Student Accounts</span>
                </a>

                <a href="{{ route('cashier.billing') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-brandGreen text-brandGreen dark:text-emerald-400 font-bold">
                    <span>Billing Setup</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 flex-shrink-0 z-10 transition-colors duration-300">
                <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Cashier Operations</span>
                <div class="flex items-center gap-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Cashier Staff'])
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                <div>
                    <h1 class="text-xl font-bold text-brandNavy dark:text-white">Billing Configuration</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400">Fee rates, discount types, and per-student discount assignment.</p>
                </div>

                @if(session('success'))
                    <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded-lg bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                        <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $errors->first() }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    {{-- FEE RATES --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                        <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                            <h2 class="text-sm font-bold text-brandNavy dark:text-white"><i class="fa-solid fa-coins mr-2 text-brandGold"></i>Fee Rates</h2>
                        </div>
                        <form action="{{ route('cashier.billing.fees') }}" method="POST" class="p-5 space-y-4 text-xs">
                            @csrf
                            <div>
                                <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Tuition per unit (₱)</label>
                                <input type="number" name="tuition_per_unit" min="0" required value="{{ $tuitionPerUnit }}"
                                    class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                            </div>
                            <div>
                                <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Miscellaneous fee per term (₱)</label>
                                <input type="number" name="misc_fee" min="0" required value="{{ $miscFee }}"
                                    class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                            </div>
                            <button type="submit" class="px-5 py-2.5 bg-brandNavy hover:bg-brandGreen text-white font-black rounded text-[11px] uppercase tracking-wider transition-colors">
                                Save Rates
                            </button>
                        </form>
                    </div>

                    {{-- DISCOUNT TYPES --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                        <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                            <h2 class="text-sm font-bold text-brandNavy dark:text-white"><i class="fa-solid fa-percent mr-2 text-brandGreen"></i>Discount Types <span class="font-normal text-brandNavy/40 dark:text-slate-500">(applies to tuition only)</span></h2>
                        </div>
                        <div class="p-5 space-y-4 text-xs">
                            <form action="{{ route('cashier.billing.discounts') }}" method="POST" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <div class="flex-1 min-w-32">
                                    <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Name</label>
                                    <input type="text" name="name" required maxlength="100" placeholder="e.g. Academic Scholar"
                                        class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                                </div>
                                <div class="w-20">
                                    <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">%</label>
                                    <input type="number" name="percent" min="1" max="100" required
                                        class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                                </div>
                                <button type="submit" class="px-4 py-2.5 bg-brandGreen hover:bg-emerald-600 text-white font-black rounded text-[11px] uppercase tracking-wider transition-colors">
                                    Add
                                </button>
                            </form>

                            <div class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($discountTypes as $type)
                                    <div class="py-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-brandNavy dark:text-slate-200">{{ $type->name }}</span>
                                            <span class="text-brandGreen font-black ml-2">{{ $type->percent }}%</span>
                                            <span class="text-brandNavy/40 dark:text-slate-500 ml-2">{{ $type->students_count }} student(s)</span>
                                        </div>
                                        <form action="{{ route('cashier.billing.discounts.delete', $type) }}" method="POST"
                                              onsubmit="return confirm('Remove {{ $type->name }}? Students with this discount will lose it.');">
                                            @csrf
                                            <button type="submit" class="w-7 h-7 rounded bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white transition-colors">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="py-3 text-brandNavy/40 dark:text-slate-500">No discount types yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STUDENT DISCOUNT ASSIGNMENT --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                    <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-brandNavy dark:text-white"><i class="fa-solid fa-user-tag mr-2 text-brandNavy dark:text-slate-300"></i>Student Discounts</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-3.5 px-5">Student</th>
                                    <th class="py-3.5 px-5">Program / Year</th>
                                    <th class="py-3.5 px-5">Discount</th>
                                    <th class="py-3.5 px-5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @forelse($students as $student)
                                    <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="py-3.5 px-5">
                                            <span class="font-bold text-brandNavy dark:text-white block">{{ $student->name }}</span>
                                            <span class="font-mono text-brandNavy/60 dark:text-slate-400">{{ $student->login_id ?? '—' }}</span>
                                        </td>
                                        <td class="py-3.5 px-5 text-brandNavy/60 dark:text-slate-400">{{ $student->major ?? '—' }} · {{ $student->year_level ?? '—' }}</td>
                                        <td class="py-3.5 px-5" colspan="2">
                                            <form action="{{ route('cashier.billing.assign', $student) }}" method="POST" class="flex items-center justify-between gap-2">
                                                @csrf
                                                <select name="discount_type_id"
                                                    class="bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen">
                                                    <option value="">— None —</option>
                                                    @foreach($discountTypes as $type)
                                                        <option value="{{ $type->id }}" @selected($student->discount_type_id === $type->id)>{{ $type->name }} ({{ $type->percent }}%)</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="px-4 py-2 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-brandNavy/40 dark:text-slate-500 font-medium">No student accounts found.</td>
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

In each of `resources/views/cashier/dashboard.blade.php`, `resources/views/cashier/transactions.blade.php`, and `resources/views/cashier/accounts.blade.php`, find the nav block ending with:

```blade
                <a href="{{ route('cashier.accounts') }}"
```

and after that whole `<a>...</a>` element (3 lines, ending `</a>`), add:

```blade
                <a href="{{ route('cashier.billing') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is('cashier.billing') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }}">
                    <span>Billing Setup</span>
                </a>
```

(The exact surrounding markup may differ slightly per file — match each file's own nav-link pattern.)

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter CashierBillingTest`
Expected: PASS (7 tests). Then `php artisan test` — full suite green (149 passed).
Run: `php artisan view:clear && php artisan view:cache` — no Blade syntax errors.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/cashier/billing.blade.php resources/views/cashier/dashboard.blade.php resources/views/cashier/transactions.blade.php resources/views/cashier/accounts.blade.php tests/Feature/CashierBillingTest.php
git commit -m "feat: cashier billing page for fees and discounts"
```

---

### Task 6: Student ledger page, notif bell, final verification

**Files:**
- Modify: `resources/views/payment.blade.php` (real breakdown, pay/verify buttons, history, modal removed)
- Modify: `resources/views/partials/notif-script.blade.php` (student + cashier branches)
- Test: `tests/Feature/LedgerPageTest.php`

**Interfaces:**
- Consumes: Task 4's view data (`$breakdown`, `$history`, `$hasPendingGateway`, `$clearance`) and routes (`ledger.checkout`, `ledger.verify`); `TransactionLedger` model.
- Produces: the final student-facing payments UI and bell entries.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/LedgerPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_shows_breakdown_and_own_history_only(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'reference_no' => 'PMG-MINE123456', 'status' => 'Settled', 'paid_at' => now(),
        ]);
        TransactionLedger::factory()->create(['reference_no' => 'PMG-OTHERS7890']);

        $response = $this->actingAs($student)->get('/ledger');

        $response->assertOk()
            ->assertSee('Assessment Breakdown')
            ->assertSee('Miscellaneous Fee')
            ->assertSee('PMG-MINE123456')
            ->assertDontSee('PMG-OTHERS7890')
            ->assertDontSee('mockPay');
    }

    public function test_pending_gateway_row_shows_verify_button(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Pending']);

        $this->actingAs($student)->get('/ledger')
            ->assertOk()
            ->assertSee('Verify Payment');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter LedgerPageTest`
Expected: FAIL — page has no "Assessment Breakdown" section.

- [ ] **Step 3: Rewrite the ledger page body**

All edits in `resources/views/payment.blade.php`:

**(a) Error flash banner** — directly after the `@if(session('success')) ... @endif` block (~line 92), add:

```blade
                @if(session('error'))
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                        <i class="fa-solid fa-circle-xmark mr-2"></i>{{ session('error') }}
                    </div>
                @endif
```

**(b) Balance card** — replace the whole `{{-- BALANCE OVERVIEW CARD --}}` div (from its opening `<div class="bg-white dark:bg-panelDark border border-brandNavy/10 ...">` through its matching closing `</div>` just before `{{-- CLEARANCE STATUS SUMMARY --}}`) with:

```blade
                {{-- BALANCE OVERVIEW CARD --}}
                @php $settled = ($breakdown['balance'] ?? 0) <= 0; @endphp
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center">
                        <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                            <i class="fa-solid fa-wallet mr-2"></i>Account Balance
                        </span>
                        <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
                    </div>
                    <div class="p-6 lg:p-8 flex flex-col md:flex-row items-start justify-between gap-6">
                        <div class="space-y-2 text-center md:text-left">
                            @if($settled)
                                <p class="text-[10px] font-bold text-brandGreen uppercase tracking-widest">Outstanding Balance</p>
                                <h3 class="text-4xl font-black text-brandGreen">₱ 0.00</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">Your account has been fully settled with the Accounting Office.</p>
                            @else
                                <p class="text-[10px] font-bold text-brandGold uppercase tracking-widest">Outstanding Balance</p>
                                <h3 class="text-4xl font-black text-brandGold dark:text-amber-400">₱ {{ number_format($breakdown['balance'], 2) }}</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">Settle your balance to clear the cashier hold before enrollment.</p>
                            @endif

                            {{-- ASSESSMENT BREAKDOWN --}}
                            <div class="mt-4 bg-lightBg dark:bg-slate-900/40 border border-brandNavy/5 dark:border-slate-800 rounded-xl p-4 text-xs space-y-1.5 w-full md:w-80">
                                <p class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Assessment Breakdown</p>
                                <div class="flex justify-between">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Tuition ({{ $breakdown['units'] }} units × ₱{{ number_format($breakdown['rate']) }})</span>
                                    <span class="font-bold text-brandNavy dark:text-slate-200">₱ {{ number_format($breakdown['tuition'], 2) }}</span>
                                </div>
                                @if($breakdown['discount_amount'] > 0)
                                    <div class="flex justify-between text-brandGreen">
                                        <span>{{ $breakdown['discount_name'] }} (−{{ $breakdown['discount_percent'] }}% tuition)</span>
                                        <span class="font-bold">− ₱ {{ number_format($breakdown['discount_amount'], 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Miscellaneous Fee</span>
                                    <span class="font-bold text-brandNavy dark:text-slate-200">₱ {{ number_format($breakdown['misc'], 2) }}</span>
                                </div>
                                <div class="flex justify-between pt-1.5 border-t border-brandNavy/10 dark:border-slate-800">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Total Assessment</span>
                                    <span class="font-bold text-brandNavy dark:text-slate-200">₱ {{ number_format($breakdown['assessment'], 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-brandNavy/60 dark:text-slate-400">Payments Made</span>
                                    <span class="font-bold text-brandGreen">− ₱ {{ number_format($breakdown['paid'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 w-full md:w-auto">
                            @if($settled)
                                <button disabled class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-lightBg text-brandNavy/40 dark:bg-slate-800 dark:text-slate-500 font-bold rounded-xl text-xs uppercase tracking-wider cursor-not-allowed border border-brandNavy/10 dark:border-slate-700">
                                    <i class="fa-solid fa-circle-check"></i>Account Settled
                                </button>
                            @else
                                <form action="{{ route('ledger.checkout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md hover:shadow-brandGreen/25 hover:-translate-y-0.5 active:translate-y-0">
                                        <i class="fa-solid fa-credit-card"></i>Pay ₱ {{ number_format($breakdown['balance'], 2) }} via PayMongo
                                    </button>
                                </form>
                            @endif
                            @if($hasPendingGateway)
                                <form action="{{ route('ledger.verify') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-brandGold/10 hover:bg-brandGold text-brandGold hover:text-white border border-brandGold/30 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors">
                                        <i class="fa-solid fa-rotate"></i>Verify Payment
                                    </button>
                                </form>
                                <p class="text-[10px] text-brandNavy/50 dark:text-slate-500 text-center max-w-48">Finished paying on the gateway but the balance did not update? Verify here.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- PAYMENT HISTORY --}}
                @if(isset($history) && $history->isNotEmpty())
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <div class="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                            <span class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                                <i class="fa-solid fa-clock-rotate-left mr-2"></i>Payment History
                            </span>
                        </div>
                        <div class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                            @foreach($history as $row)
                                <div class="px-6 py-3.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <div>
                                        <span class="font-mono font-bold text-brandNavy dark:text-slate-200 block">{{ $row->reference_no }}</span>
                                        <span class="text-brandNavy/50 dark:text-slate-500">{{ $row->gateway === 'paymongo' ? 'PayMongo (online)' : 'Cashier window' }} · {{ $row->created_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="font-black text-brandNavy dark:text-slate-200">₱ {{ number_format($row->amount, 2) }}</span>
                                        @if($row->status === 'Settled')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Settled</span>
                                        @elseif($row->status === 'Pending')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                                        @elseif($row->status === 'Failed')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Failed</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-slate-500/10 text-slate-500 border border-slate-500/20 uppercase tracking-wider">{{ $row->status }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
```

**(c) Remove the modal and its script** — delete the whole `{{-- PAYMENT GATEWAY MODAL --}}` div (`id="gatewayModal"` through its closing `</div>`), and in the `<script>` block at the bottom delete `openPaymentGatewayModal()`, `closePaymentGatewayModal()`, `submitMockPayment()`, and the `window.addEventListener('DOMContentLoaded', ...)` triggerPay block — leaving only the theme functions in the head. If the bottom `<script>` block becomes empty, remove it entirely.

- [ ] **Step 4: Notif bell branches**

In `resources/views/partials/notif-script.blade.php`, in the **student** branch, directly after the `$latestDocument` block's closing `}` (still inside the `if ($cl)` block), add:

```php
            $latestPayment = \App\Models\TransactionLedger::where('user_id', $authId)->where('gateway', 'paymongo')->latest()->first();
            if ($latestPayment) {
                if ($latestPayment->status === 'Settled') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-money-check-dollar', 'color' => '#1D7A46', 'title' => 'Payment Received', 'desc' => '₱' . number_format((float) $latestPayment->amount, 2) . ' settled via PayMongo. Ref ' . $latestPayment->reference_no . '.', 'time' => 'Finance update'];
                } elseif ($latestPayment->status === 'Pending') {
                    $notifs[] = ['id' => $nid++, 'icon' => 'fa-hourglass-half', 'color' => '#E2A700', 'title' => 'Payment Awaiting Verification', 'desc' => 'Use Verify Payment on your Ledger page to confirm your online payment.', 'time' => 'Action needed'];
                }
            }
```

In the **cashier** branch, directly after the existing block:

```php
        $pendingPayments = Clearance::where('cashier_status', 'Pending')->count();
        $settledPayments = Clearance::where('cashier_status', 'Approved')->count();
```

add:

```php
        $onlineToday = \App\Models\TransactionLedger::where('gateway', 'paymongo')->where('status', 'Settled')->whereDate('paid_at', today())->count();
        if ($onlineToday > 0) {
            $notifs[] = ['id' => $nid++, 'icon' => 'fa-money-bill-wave', 'color' => '#1D7A46', 'title' => 'Online Payments Received',
                         'desc' => $onlineToday . ' online payment(s) settled via PayMongo today.', 'time' => 'Finance update'];
        }
```

- [ ] **Step 5: Full verification**

Run: `php artisan test --filter LedgerPageTest` — PASS (2 tests).
Run: `php artisan view:clear && php artisan view:cache` — no Blade syntax errors.
Run: `php artisan test` — all PASS (151 passed).
Run: `php artisan migrate:fresh --seed` — no errors (resets local dev DB; expected).

- [ ] **Step 6: Commit**

```bash
git add resources/views/payment.blade.php resources/views/partials/notif-script.blade.php tests/Feature/LedgerPageTest.php
git commit -m "feat: real ledger page with paymongo checkout and history"
```

---

## Demo walkthrough (manual, after all tasks)

One-time setup: create a free PayMongo account → Developers → copy the **test** secret key → add `PAYMONGO_SECRET_KEY=sk_test_…` to `.env` (`php artisan config:clear` after). Internet required.

1. Log in as `cashier01` / `password123` → Billing Setup → confirm rates (₱300/unit, ₱1,500 misc); assign "Academic Scholar" to a student if desired.
2. Log in as `2300410` / `password` → Ledger & Payments → see the real breakdown → "Pay ₱X via PayMongo" → complete on the hosted test page (card `4343 4343 4343 4345`, any future expiry/CVC, or test GCash).
3. On return: green flash, balance ₱0.00, history row Settled — and the Clearance page's cashier signature is Approved.
4. Cashier's Transactions page shows the online payment row; the bell shows "Online Payments Received".
