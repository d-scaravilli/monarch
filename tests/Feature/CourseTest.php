<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guards the mixed-chart data restructuring (weeklyAttendanceTrend()
     * now returns present/absent/rate per week instead of a bare
     * percentage) — the course page must still render cleanly with real
     * attendance data behind it.
     */
    public function test_course_show_page_renders_with_attendance_data(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $course = Course::factory()->create(['type' => 'corso']);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id, 'date' => now()]);
        Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id, 'present' => true]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Andamento presenze del corso');
    }

    /**
     * Extracts the trend chart's x-axis categories string from the raw
     * page source — the lessons table further down the same page lists
     * every lesson regardless of chart eligibility, so a bare page-wide
     * assertSee/assertDontSee would false-positive on it. Only the chart
     * script block reflects what actually entered weeklyAttendanceTrend().
     */
    private function chartCategories(string $html): string
    {
        preg_match('/xaxis:\s*\{\s*categories:\s*JSON\.parse\(\'(.*?)\'\)/', $html, $matches);

        return $matches[1] ?? '';
    }

    /**
     * The trend chart's x-axis must be built from the real dates of
     * lessons actually held for this course — not a fixed weekly bucket
     * — so each held lesson's date must appear in the chart categories.
     */
    public function test_attendance_trend_chart_uses_real_lesson_dates(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $course = Course::factory()->create(['type' => 'corso']);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'enrollment_date' => now()->subMonths(3)]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id, 'date' => now()->subDays(3)]);
        Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id, 'present' => true]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $this->assertStringContainsString($lesson->date->translatedFormat('d M'), $this->chartCategories($response->getContent()));
    }

    /**
     * A lesson marked as cancelled must be excluded from both the
     * average presence rate and the trend chart categories — it may
     * still show up (distinctly) further down in the lessons table.
     */
    public function test_cancelled_lessons_are_excluded_from_average_and_trend(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $course = Course::factory()->create(['type' => 'corso']);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'enrollment_date' => now()->subMonths(3)]);

        $heldLesson = Lesson::factory()->create(['course_id' => $course->id, 'date' => now()->subDays(5)]);
        Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $heldLesson->id, 'present' => true]);

        $cancelledLesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'date' => now()->subDays(2),
            'cancelled' => true,
        ]);
        Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $cancelledLesson->id, 'present' => false]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $categories = $this->chartCategories($response->getContent());
        $this->assertStringContainsString($heldLesson->date->translatedFormat('d M'), $categories);
        $this->assertStringNotContainsString($cancelledLesson->date->translatedFormat('d M'), $categories);
        // The average is 100% (only the held lesson counts, and it's a
        // presence) — if the cancelled absence leaked in, it would drop.
        $response->assertSee('series: [100]', false);
        // The lessons table below still lists it, distinctly marked.
        $response->assertSee('Annullata');
    }

    /**
     * A lesson with no registered attendance at all (nobody ever opened
     * it to toggle presence) must not count as an empty/zero data point
     * in the chart categories.
     */
    public function test_lesson_without_registered_attendance_is_excluded_from_trend(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $course = Course::factory()->create(['type' => 'corso']);
        Enrollment::factory()->create(['course_id' => $course->id, 'enrollment_date' => now()->subMonths(3)]);
        $unregisteredLesson = Lesson::factory()->create(['course_id' => $course->id, 'date' => now()->subDays(1)]);

        $response = $this->actingAs($admin)->get(route('courses.show', $course));

        $response->assertOk();
        $this->assertStringNotContainsString($unregisteredLesson->date->translatedFormat('d M'), $this->chartCategories($response->getContent()));
    }
}
