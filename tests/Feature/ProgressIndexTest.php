<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Discipline;
use App\Models\Enrollment;
use App\Models\Goal;
use App\Models\MemberNote;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgressIndexTest extends TestCase
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

    private function makeMember(?string $name = null): User
    {
        $user = User::factory()->create($name ? ['name' => $name] : []);
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

    public function test_the_compact_goals_list_shows_members_with_at_least_one_goal_and_their_course(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember('Iscritto Con Obiettivo');
        $discipline = Discipline::factory()->create(['name' => 'Karate']);
        $course = Course::factory()->create(['discipline_id' => $discipline->id]);
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        Goal::create(['enrollment_id' => $enrollment->id, 'title' => 'Test', 'status' => 'in_progress', 'created_by' => $admin->id]);

        // A member with only a note (no goal) must not appear in the
        // compact list, only in the existing card grid below it.
        $noteOnlyMember = $this->makeMember('Iscritto Solo Note');
        MemberNote::create([
            'user_id' => $noteOnlyMember->id,
            'created_by' => $admin->id,
            'type' => 'altro',
            'description' => 'Nota qualsiasi',
        ]);

        $response = $this->actingAs($admin)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Con obiettivi in corso', 'Iscritto Con Obiettivo', 'Karate']);
        $response->assertSee('Iscritto Solo Note');
    }

    public function test_a_member_with_no_goals_does_not_appear_in_the_compact_list(): void
    {
        $admin = $this->makeAdmin();
        $this->makeMember('Nessun Obiettivo');

        $response = $this->actingAs($admin)->get(route('progress.index'));

        $response->assertOk();
        $response->assertDontSee('Con obiettivi in corso');
    }

    public function test_clicking_a_compact_list_entry_links_to_the_member_profile_progressi_tab(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id]);
        Goal::create(['enrollment_id' => $enrollment->id, 'title' => 'Test', 'status' => 'in_progress', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee(route('members.show', ['member' => $member, 'tab' => 'progressi']), false);
    }

    /**
     * An instructor's compact list must stay scoped to their own
     * courses, same as the rest of Progressi/Team.
     */
    public function test_instructor_only_sees_goals_from_their_own_courses_in_the_compact_list(): void
    {
        $instructor = $this->makeInstructor();
        $ownCourse = Course::factory()->create();
        $ownCourse->instructors()->attach($instructor);
        $ownStudent = $this->makeMember('Studente Proprio');
        $ownEnrollment = Enrollment::factory()->create(['user_id' => $ownStudent->id, 'course_id' => $ownCourse->id]);
        Goal::create(['enrollment_id' => $ownEnrollment->id, 'title' => 'Test', 'status' => 'in_progress', 'created_by' => $instructor->id]);

        $otherCourse = Course::factory()->create();
        $strangerStudent = $this->makeMember('Studente Altrui');
        $strangerEnrollment = Enrollment::factory()->create(['user_id' => $strangerStudent->id, 'course_id' => $otherCourse->id]);
        Goal::create(['enrollment_id' => $strangerEnrollment->id, 'title' => 'Test', 'status' => 'in_progress', 'created_by' => $instructor->id]);

        $response = $this->actingAs($instructor)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee('Studente Proprio');
        $response->assertDontSee('Studente Altrui');
    }
}
