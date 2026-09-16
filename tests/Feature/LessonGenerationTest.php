<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LessonGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * Reproduces exactly what was reported: creating a course — even one
     * with recurring weekly schedules, which is what "Genera lezioni"
     * later reads to know which weekdays to fill in — must never create
     * any Lesson row by itself. Lessons only ever come from the explicit
     * "Genera lezioni" action, and only within the range given there.
     */
    public function test_creating_a_course_with_schedules_does_not_generate_any_lessons(): void
    {
        $admin = $this->makeAdmin();
        $discipline = Discipline::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'discipline_id' => $discipline->id,
            'room_id' => $room->id,
            'type' => 'corso',
            'year' => '2025/2026',
            'annual_cost' => 300,
            'monthly_cost' => 30,
            'schedules' => [
                ['weekday' => 1, 'start_time' => '18:00', 'end_time' => '19:00'],
            ],
        ]);

        $response->assertRedirect();
        $course = Course::firstOrFail();

        $this->assertSame(1, $course->schedules()->count());
        $this->assertSame(0, Lesson::count());
    }

    /**
     * The explicit "Genera lezioni" action, called with a specific range,
     * must create lessons only inside that range — never beyond it.
     */
    public function test_generating_lessons_only_creates_them_within_the_given_range(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create(['type' => 'corso']);
        $course->schedules()->create(['weekday' => 1, 'start_time' => '18:00', 'end_time' => '19:00']); // Monday

        $this->assertSame(0, Lesson::count());

        $response = $this->actingAs($admin)->post(route('lessons.generate.store'), [
            'course_id' => $course->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]);

        $response->assertRedirect(route('courses.show', $course));

        $lessons = Lesson::where('course_id', $course->id)->get();
        $this->assertTrue($lessons->isNotEmpty());
        foreach ($lessons as $lesson) {
            $this->assertTrue($lesson->date->between('2026-01-01', '2026-01-31'), 'Lesson date must fall within the requested range.');
            $this->assertSame(1, $lesson->date->dayOfWeek, 'Lesson must fall on the configured weekday (Monday).');
        }
    }

    /**
     * The "Genera lezioni" form no longer pre-fills a wide default end
     * date (it used to default 9 months out) — both dates must now be
     * required, explicit input.
     */
    public function test_generate_lessons_requires_an_explicit_end_date(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create(['type' => 'corso']);
        $course->schedules()->create(['weekday' => 1, 'start_time' => '18:00', 'end_time' => '19:00']);

        $response = $this->actingAs($admin)->post(route('lessons.generate.store'), [
            'course_id' => $course->id,
            'start_date' => '2026-01-01',
        ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertSame(0, Lesson::count());
    }
}
