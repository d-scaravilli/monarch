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
}
