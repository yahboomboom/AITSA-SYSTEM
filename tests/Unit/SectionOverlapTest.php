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
