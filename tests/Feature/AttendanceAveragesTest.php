<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Presence averages never mix courses and events: an evento's presences
 * stay inside the evento itself and never enter an aggregate rate.
 */
class AttendanceAveragesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);
    }

    /**
     * One present lesson in a corso and one absent lesson in an evento,
     * both held today, for the same member.
     */
    private function memberWithCorsoPresenceAndEventoAbsence(): User
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        foreach ([[Course::factory()->create(['type' => 'corso']), true], [Course::factory()->evento()->create(), false]] as [$course, $present]) {
            $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id, 'enrollment_date' => now()->subMonth()]);
            $lesson = Lesson::factory()->create(['course_id' => $course->id, 'date' => now()->startOfWeek()]);
            Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id, 'present' => $present]);
        }

        return $member;
    }

    public function test_dashboard_aggregates_ignore_evento_lessons(): void
    {
        $this->memberWithCorsoPresenceAndEventoAbsence();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('palestra.dashboard'));

        $response->assertOk();
        $response->assertViewHas('weekAttendanceRate', 100);
        $response->assertViewHas('topAbsent', fn ($rows) => $rows->isEmpty());
        $response->assertViewHas('weeklyAttendanceTrend', fn (array $trend) => array_column($trend, 'absent') === [0]);
    }

    public function test_member_area_overall_rate_ignores_evento_lessons(): void
    {
        $member = $this->memberWithCorsoPresenceAndEventoAbsence();
        $member->modules()->attach(Module::create(['slug' => 'palestra', 'name' => 'Palestra', 'icon' => 'fire', 'color' => 'orange', 'is_active' => true]));

        $this->actingAs($member)->get(route('member.area'))
            ->assertOk()
            ->assertSeeInOrder(['100%', 'presenze corsi'])
            ->assertDontSee('50%');
    }
}
