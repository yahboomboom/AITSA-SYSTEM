<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdmissionWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Edge cases around admission withdrawals that the first review flagged:
 * cross-term reinstates, Reports, admin counts, bulk input, the N-day rule
 * and the forgot-password path.
 */
class AdmissionWithdrawalEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private function reservedStudent(int $daysAgo = 20, array $attrs = []): User
    {
        $student = User::factory()->create(array_merge([
            'role' => 'student', 'is_reserved' => true, 'program_key' => 'bsoa',
        ], $attrs));
        $this->travel(-$daysAgo)->days();
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);
        $this->travelBack();

        return $student;
    }

    private function service(): AdmissionWithdrawalService
    {
        return app(AdmissionWithdrawalService::class);
    }

    public function test_reinstating_in_a_later_term_creates_an_approved_current_term_clearance(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent();
        $this->service()->withdraw($student, 'no_show', null, $registrar);

        Setting::put('semester', '2');
        $this->service()->reinstate($student->fresh(), $registrar);

        $clearance = Clearance::where('user_id', $student->id)
            ->where('school_year', '2026-2027')->where('semester', 2)->first();
        $this->assertNotNull($clearance);
        $this->assertSame('Approved', $clearance->admission_status);
    }

    public function test_reports_mark_withdrawn_rows_and_leave_them_out_of_the_pending_counts(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->reservedStudent(20, ['name' => 'Active, Ann']);
        $gone = $this->reservedStudent(20, ['name' => 'Gone, Gus']);
        $this->service()->withdraw($gone, 'no_show', null, $registrar);

        $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()])
            ->get('/registrar/reports')
            ->assertOk()
            ->assertSee('Gone, Gus')
            ->assertSee('&quot;total&quot;:1', false)
            ->assertSee('&quot;registrarPending&quot;:1', false)
            ->assertSee('&quot;withdrawn&quot;:1', false)
            ->assertSee('&quot;isWithdrawn&quot;:true', false);
    }

    public function test_admin_active_user_count_excludes_withdrawn(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'student']);
        User::factory()->create(['role' => 'withdrawn']);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertSee('&quot;totalActiveUsers&quot;:2', false);
    }

    public function test_bulk_withdraw_reports_ids_it_could_not_find(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent();

        $this->actingAs($registrar)->post('/registrar/no-shows/withdraw', [
            'ids' => [$student->id, 999999], 'reason' => 'no_show',
        ])->assertSessionHas('success', fn ($msg) => str_contains($msg, '1 record could not be found'));

        $this->actingAs($registrar)->post('/registrar/no-shows/withdraw', [
            'ids' => [999998], 'reason' => 'no_show',
        ])->assertSessionHas('error', fn ($msg) => str_contains($msg, 'could not be found'));
    }

    public function test_withdraw_enforces_the_no_show_threshold(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $recent = $this->reservedStudent(5);

        $this->assertFalse($this->service()->withdraw($recent, 'no_show', null, $registrar));
        $this->assertSame('student', $recent->fresh()->role);
    }

    public function test_forgot_password_does_nothing_for_a_withdrawn_account(): void
    {
        Notification::fake();
        $withdrawn = User::factory()->create(['role' => 'withdrawn', 'login_id' => '2026-00888', 'email' => 'gone@example.com']);

        $this->post('/forgot-password', ['login_id' => '2026-00888'])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertNull($withdrawn->fresh()->password_reset_requested_at);
    }
}
