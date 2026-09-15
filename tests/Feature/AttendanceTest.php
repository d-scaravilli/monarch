<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);

        $this->module = Module::create([
            'slug' => 'palestra',
            'name' => 'Palestra',
            'icon' => 'fire',
            'color' => 'orange',
            'is_active' => true,
        ]);
    }

    private function makeMember(): User
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->modules()->attach($this->module);

        return $user;
    }

    private function makeInstructor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');
        $user->modules()->attach($this->module);

        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /**
     * The toggle/notes roster is the operational tool admin and assigned
     * instructors use to register presence — a member must never see it,
     * not even read-only, even though they can view the lesson page
     * itself (for the summary panels).
     */
    public function test_member_does_not_see_the_attendance_management_table(): void
    {
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $lesson = Lesson::create(['course_id' => $course->id, 'date' => now()]);
        Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $member->id]);

        $response = $this->actingAs($member)->get(route('courses.lessons.attendance.edit', [$course, $lesson]));

        $response->assertOk();
        $response->assertDontSee('Segna tutti presenti');
        $response->assertDontSee('wire:key', false);
    }

    public function test_admin_sees_the_attendance_management_table(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create();
        $lesson = Lesson::create(['course_id' => $course->id, 'date' => now()]);
        $member = $this->makeMember();
        Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $member->id]);

        $response = $this->actingAs($admin)->get(route('courses.lessons.attendance.edit', [$course, $lesson]));

        $response->assertOk();
        $response->assertSee('Segna tutti presenti');
        $response->assertSee($member->name);
    }

    public function test_assigned_instructor_sees_the_attendance_management_table(): void
    {
        $instructor = $this->makeInstructor();
        $course = Course::factory()->create();
        $course->instructors()->attach($instructor);
        $lesson = Lesson::create(['course_id' => $course->id, 'date' => now()]);
        $member = $this->makeMember();
        Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $member->id]);

        $response = $this->actingAs($instructor)->get(route('courses.lessons.attendance.edit', [$course, $lesson]));

        $response->assertOk();
        $response->assertSee('Segna tutti presenti');
        $response->assertSee($member->name);
    }

    /**
     * An instructor not assigned to this course is still allowed to open
     * the lesson page if enrolled or it's an evento, but they're not a
     * manager here either — same hidden table as a plain member.
     */
    public function test_unassigned_instructor_does_not_see_the_attendance_management_table(): void
    {
        $instructor = $this->makeInstructor();
        $course = Course::factory()->create(['type' => 'evento']);
        $lesson = Lesson::create(['course_id' => $course->id, 'date' => now()]);

        $response = $this->actingAs($instructor)->get(route('courses.lessons.attendance.edit', [$course, $lesson]));

        $response->assertOk();
        $response->assertDontSee('Segna tutti presenti');
    }

    public function test_the_lesson_link_in_the_member_attendance_table_points_to_the_lesson_page(): void
    {
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $lesson = Lesson::create(['course_id' => $course->id, 'date' => now()->subDay()]);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $member->id, 'enrollment_date' => now()->subMonth()]);
        $enrollment->attendances()->create(['lesson_id' => $lesson->id, 'present' => true]);

        $response = $this->actingAs($member)->get(route('member.area'));

        $response->assertOk();
        $response->assertSee(route('courses.lessons.attendance.edit', [$course, $lesson]), false);
    }
}
