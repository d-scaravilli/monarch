<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Discipline;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberAttendanceTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Lessons of courses and of events live in two separate lists: each
     * instance of the table only ever shows its own type.
     */
    public function test_corso_and_evento_lessons_are_listed_separately(): void
    {
        Role::create(['name' => 'member']);
        $member = User::factory()->create();
        $member->assignRole('member');

        $corso = Course::factory()->create(['type' => 'corso', 'discipline_id' => Discipline::factory()->create(['name' => 'Karate'])->id]);
        $evento = Course::factory()->evento()->create(['title' => 'Stage estivo']);

        foreach ([$corso, $evento] as $course) {
            $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id, 'enrollment_date' => now()->subMonth()]);
            $lesson = Lesson::factory()->create(['course_id' => $course->id, 'date' => now()->subDay()]);
            Attendance::factory()->create(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id, 'present' => true]);
        }

        $this->actingAs($member);

        Livewire::test('member-attendance-table', ['memberId' => $member->id, 'type' => 'corso'])
            ->assertSee('Karate')
            ->assertDontSee('Stage estivo');

        Livewire::test('member-attendance-table', ['memberId' => $member->id, 'type' => 'evento'])
            ->assertSee('Stage estivo')
            ->assertDontSee('Karate');
    }

    /**
     * "La mia area" shows the two lists under distinct headings.
     */
    public function test_member_area_shows_separate_sections_for_corsi_and_eventi(): void
    {
        Role::create(['name' => 'member']);
        $member = User::factory()->create();
        $member->assignRole('member');
        $member->modules()->attach(Module::create(['slug' => 'palestra', 'name' => 'Palestra', 'icon' => 'fire', 'color' => 'orange', 'is_active' => true]));

        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => Course::factory()->create(['type' => 'corso'])->id]);
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => Course::factory()->evento()->create()->id]);

        $this->actingAs($member)->get(route('member.area'))
            ->assertOk()
            ->assertSeeInOrder(['Lezioni dei corsi', 'Lezioni degli eventi']);
    }
}
