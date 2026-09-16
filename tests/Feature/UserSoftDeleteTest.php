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
