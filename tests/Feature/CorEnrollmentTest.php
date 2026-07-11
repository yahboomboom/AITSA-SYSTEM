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
