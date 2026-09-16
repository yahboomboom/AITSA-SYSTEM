<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_initialize_for_creates_a_row_scoped_to_the_given_term(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $clearance = Clearance::initializeFor($user->id, '2026-2027', 1, ['chair_status' => 'Pending']);

        $this->assertSame('2026-2027', $clearance->school_year);
        $this->assertSame(1, $clearance->semester);
    }

    public function test_initialize_for_creates_a_separate_row_for_a_different_term(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Department::factory()->create(['is_active' => true]);

        $first = Clearance::initializeFor($user->id, '2026-2027', 1);
        $second = Clearance::initializeFor($user->id, '2026-2027', 2);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Clearance::where('user_id', $user->id)->count());
    }

    public function test_initialize_for_is_idempotent_within_the_same_term(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $first = Clearance::initializeFor($user->id, '2026-2027', 1);
        $second = Clearance::initializeFor($user->id, '2026-2027', 1);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Clearance::where('user_id', $user->id)->count());
    }

    public function test_current_for_resolves_the_row_matching_the_setting_term(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        Setting::clearCache();

        $user = User::factory()->create(['role' => 'student']);
        Clearance::initializeFor($user->id, '2026-2027', 1);
        $current = Clearance::initializeFor($user->id, '2026-2027', 2);

        $resolved = Clearance::currentFor($user);

        $this->assertSame($current->id, $resolved->id);
    }

    public function test_current_for_returns_null_when_the_student_has_no_clearance_for_the_current_term(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->assertNull(Clearance::currentFor($user));
    }
}
