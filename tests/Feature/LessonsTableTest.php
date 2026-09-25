<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LessonsTableTest extends TestCase
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
     * The lessons table used to default to oldest-first, which reads
     * backwards on a page most people open to check what just happened —
     * it must default to most-recent-first instead.
     */
    public function test_lessons_table_defaults_to_most_recent_first(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create();
        $older = Lesson::create(['course_id' => $course->id, 'date' => now()->subDays(10)]);
        $newer = Lesson::create(['course_id' => $course->id, 'date' => now()->subDays(1)]);

        $this->actingAs($admin);

        Livewire::test('lessons-table', ['courseId' => $course->id])
            ->set('allDates', true)
            ->assertSeeInOrder([$newer->date->translatedFormat('d M Y'), $older->date->translatedFormat('d M Y')]);
    }

    public function test_a_cancelled_lesson_shows_a_distinct_badge_instead_of_counts(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create();
        Lesson::create(['course_id' => $course->id, 'date' => now(), 'cancelled' => true, 'cancellation_reason' => 'Maltempo']);

        $this->actingAs($admin);

        Livewire::test('lessons-table', ['courseId' => $course->id])
            ->set('allDates', true)
            ->assertSee('Annullata');
    }

    /**
     * The general lessons page shows course lessons and event lessons in
     * two separate tabs, never mixed in one list — and the course filter
     * only offers courses of the active tab's type.
     */
    public function test_general_lessons_page_separates_corsi_and_eventi(): void
    {
        $admin = $this->makeAdmin();
        $corso = Course::factory()->create(['type' => 'corso', 'discipline_id' => Discipline::factory()->create(['name' => 'Karate'])->id]);
        $evento = Course::factory()->evento()->create(['title' => 'Stage estivo']);
        Lesson::create(['course_id' => $corso->id, 'date' => now()]);
        Lesson::create(['course_id' => $evento->id, 'date' => now()]);

        $this->actingAs($admin);

        Livewire::test('lessons-table')
            ->assertSee('Karate')
            ->assertDontSee('Stage estivo')
            ->set('type', 'evento')
            ->assertSee('Stage estivo')
            ->assertDontSee('Karate');
    }

    /**
     * Landing on the lessons page filtered on an evento (e.g. right after
     * adding a lesson to it) must open the Eventi tab, not an empty Corsi one.
     */
    public function test_filtering_on_an_evento_opens_the_eventi_tab(): void
    {
        $admin = $this->makeAdmin();
        $evento = Course::factory()->evento()->create(['title' => 'Stage estivo']);
        Lesson::create(['course_id' => $evento->id, 'date' => now()]);

        $this->actingAs($admin);

        Livewire::test('lessons-table', ['courseId' => $evento->id])
            ->assertSet('type', 'evento')
            ->assertSee('Stage estivo');
    }
}
