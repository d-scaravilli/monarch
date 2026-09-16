<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Goal;
use App\Models\Lesson;
use App\Models\MemberNote;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoalTest extends TestCase
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

    private function makeGoal(Enrollment $enrollment, array $overrides = []): Goal
    {
        return Goal::create(array_merge([
            'enrollment_id' => $enrollment->id,
            'title' => 'Obiettivo di prova',
            'status' => 'in_progress',
            'created_by' => $enrollment->user_id,
        ], $overrides));
    }

    public function test_admin_can_create_a_goal_for_an_enrollment(): void
    {
        $admin = $this->makeAdmin();
        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($admin)->post(route('enrollments.goals.store', $enrollment), [
            'title' => 'Prima cintura',
            'starting_point' => 'Nessuna esperienza',
            'target' => 'Cintura gialla',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('goals', [
            'enrollment_id' => $enrollment->id,
            'title' => 'Prima cintura',
            'status' => 'in_progress',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * Reproduces the reported flow bug: a goal must be plannable with
     * zero notes ever having existed for this enrollment — creating one
     * must never require going through a note first.
     */
    public function test_a_goal_can_be_created_with_no_notes_existing_at_all(): void
    {
        $admin = $this->makeAdmin();
        $enrollment = Enrollment::factory()->create();

        $this->assertSame(0, MemberNote::count());

        $this->actingAs($admin)->post(route('enrollments.goals.store', $enrollment), [
            'title' => 'Obiettivo senza note',
        ])->assertRedirect();

        $this->assertSame(0, MemberNote::count());
        $this->assertDatabaseHas('goals', ['title' => 'Obiettivo senza note']);
    }

    /**
     * The "Nuovo obiettivo" button on the scheda iscritto's own Progressi
     * tab (not just the separate dedicated Progressi page) must be
     * reachable directly — this used to be missing entirely there.
     */
    public function test_new_goal_button_is_reachable_from_the_member_profile_progressi_tab(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);

        $response = $this->actingAs($admin)->get(route('members.show', ['member' => $member, 'tab' => 'progressi']));

        $response->assertOk();
        $response->assertSee('Nuovo obiettivo');
    }

    public function test_assigned_instructor_can_create_and_complete_a_goal(): void
    {
        $instructor = $this->makeInstructor();
        $course = Course::factory()->create();
        $course->instructors()->attach($instructor);
        $enrollment = Enrollment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($instructor)->post(route('enrollments.goals.store', $enrollment), [
            'title' => 'Obiettivo test',
        ])->assertRedirect();

        $goal = Goal::first();
        $this->assertNotNull($goal);

        $response = $this->actingAs($instructor)->put(route('goals.update', $goal), [
            'title' => $goal->title,
            'starting_point' => '',
            'target' => '',
            'status' => 'completed',
            'completion_description' => 'Raggiunto con successo',
        ]);

        $response->assertRedirect();
        $goal->refresh();
        $this->assertTrue($goal->isCompleted());
        $this->assertNotNull($goal->completed_at);
        $this->assertSame('Raggiunto con successo', $goal->completion_description);
    }

    public function test_instructor_not_assigned_to_the_course_cannot_manage_its_goals(): void
    {
        $instructor = $this->makeInstructor();
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($instructor)->post(route('enrollments.goals.store', $enrollment), [
            'title' => 'Non autorizzato',
        ])->assertForbidden();
    }

    public function test_member_cannot_create_or_update_goals(): void
    {
        $member = $this->makeMember();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)->post(route('enrollments.goals.store', $enrollment), [
            'title' => 'Vietato',
        ])->assertForbidden();

        $goal = $this->makeGoal($enrollment, ['created_by' => $member->id]);

        $this->actingAs($member)->put(route('goals.update', $goal), [
            'title' => 'Modificato',
            'status' => 'in_progress',
        ])->assertForbidden();
    }

    /**
     * Reaching the dedicated per-member page (opened from Progressi) as
     * the member themselves must show their goals read-only: no "nuovo
     * obiettivo" / "modifica" affordances anywhere on the page.
     */
    public function test_member_sees_their_own_goals_read_only_on_the_progress_page(): void
    {
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $this->makeGoal($enrollment, ['title' => 'Obiettivo visibile', 'created_by' => $member->id]);

        $response = $this->actingAs($member)->get(route('progress.show', $member));

        $response->assertOk();
        $response->assertSee('Obiettivo visibile');
        $response->assertDontSee('Nuovo obiettivo');
    }

    public function test_admin_sees_manage_controls_on_the_progress_page(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $this->makeGoal($enrollment, ['title' => 'Obiettivo gestibile', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('progress.show', $member));

        $response->assertOk();
        $response->assertSee('Obiettivo gestibile');
        $response->assertSee('Nuovo obiettivo');
    }

    public function test_a_note_can_be_linked_to_an_in_progress_goal(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $goal = $this->makeGoal($enrollment, ['created_by' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($admin)->post(route('courses.lessons.notes.store', [$course, $lesson]), [
            'user_id' => $member->id,
            'type' => 'progresso',
            'description' => 'Ottimo lavoro sul goal',
            'goal_id' => $goal->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('member_notes', [
            'user_id' => $member->id,
            'goal_id' => $goal->id,
            'description' => 'Ottimo lavoro sul goal',
        ]);
    }

    /**
     * A note stays valid without a goal — the field is entirely optional.
     */
    public function test_a_note_without_a_goal_is_still_valid(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->post(route('courses.lessons.notes.store', [$course, $lesson]), [
            'user_id' => $member->id,
            'type' => 'altro',
            'description' => 'Appunto generico',
        ])->assertRedirect();

        $this->assertDatabaseHas('member_notes', [
            'user_id' => $member->id,
            'goal_id' => null,
            'description' => 'Appunto generico',
        ]);
    }

    /**
     * A goal belonging to someone else's enrollment (or a different
     * course) must never be linkable, even if its id is posted directly
     * — the server re-checks ownership rather than trusting the input.
     */
    public function test_a_note_cannot_be_linked_to_a_goal_from_a_different_enrollment(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $otherMember = $this->makeMember();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $otherEnrollment = Enrollment::factory()->create(['user_id' => $otherMember->id]);
        $foreignGoal = $this->makeGoal($otherEnrollment, ['created_by' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->post(route('courses.lessons.notes.store', [$course, $lesson]), [
            'user_id' => $member->id,
            'type' => 'altro',
            'description' => 'Tentativo di collegamento errato',
            'goal_id' => $foreignGoal->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('member_notes', [
            'user_id' => $member->id,
            'goal_id' => null,
            'description' => 'Tentativo di collegamento errato',
        ]);
    }

    /**
     * The two-column layout must never show the same note twice: a note
     * linked to a goal renders only inside that goal's card, a free note
     * renders only in the "Note libere" column.
     */
    public function test_a_goal_linked_note_does_not_also_appear_among_free_notes(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $goal = $this->makeGoal($enrollment, ['created_by' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        MemberNote::create([
            'user_id' => $member->id,
            'lesson_id' => $lesson->id,
            'goal_id' => $goal->id,
            'created_by' => $admin->id,
            'type' => 'progresso',
            'description' => 'Nota collegata univoca xyz123',
        ]);
        MemberNote::create([
            'user_id' => $member->id,
            'lesson_id' => $lesson->id,
            'goal_id' => null,
            'created_by' => $admin->id,
            'type' => 'altro',
            'description' => 'Nota libera univoca abc789',
        ]);

        $response = $this->actingAs($admin)->get(route('progress.show', $member));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'Nota collegata univoca xyz123'));
        $this->assertSame(1, substr_count($html, 'Nota libera univoca abc789'));
    }

    /**
     * A free note tied to one enrollment's course must not leak into a
     * different enrollment's "Note libere" column when the member has
     * more than one enrollment.
     */
    public function test_a_free_note_appears_only_once_even_with_multiple_enrollments(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $courseA = Course::factory()->create();
        $courseB = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $courseA->id]);
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $courseB->id]);
        $lessonA = Lesson::factory()->create(['course_id' => $courseA->id]);

        MemberNote::create([
            'user_id' => $member->id,
            'lesson_id' => $lessonA->id,
            'created_by' => $admin->id,
            'type' => 'altro',
            'description' => 'Nota solo per corso A',
        ]);

        $response = $this->actingAs($admin)->get(route('progress.show', $member));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Nota solo per corso A'));
    }

    /**
     * Deleting a goal must not take its linked notes down with it — the
     * goal_id column nullOnDelete()s, so they simply become free notes.
     */
    public function test_deleting_a_goal_unlinks_its_notes_instead_of_deleting_them(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $goal = $this->makeGoal($enrollment, ['created_by' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $note = MemberNote::create([
            'user_id' => $member->id,
            'lesson_id' => $lesson->id,
            'goal_id' => $goal->id,
            'created_by' => $admin->id,
            'type' => 'progresso',
            'description' => 'Nota da scollegare',
        ]);

        $this->actingAs($admin)->delete(route('goals.destroy', $goal))->assertRedirect();

        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
        $this->assertDatabaseHas('member_notes', ['id' => $note->id, 'goal_id' => null]);
    }

    public function test_member_cannot_delete_a_goal(): void
    {
        $member = $this->makeMember();
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id]);
        $goal = $this->makeGoal($enrollment, ['created_by' => $member->id]);

        $this->actingAs($member)->delete(route('goals.destroy', $goal))->assertForbidden();
        $this->assertDatabaseHas('goals', ['id' => $goal->id]);
    }

    public function test_admin_can_delete_a_note(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $note = MemberNote::create([
            'user_id' => $member->id,
            'lesson_id' => $lesson->id,
            'created_by' => $admin->id,
            'type' => 'altro',
            'description' => 'Nota da eliminare',
        ]);

        $this->actingAs($admin)->delete(route('notes.destroy', $note))->assertRedirect();

        $this->assertDatabaseMissing('member_notes', ['id' => $note->id]);
    }

    public function test_member_cannot_delete_a_note(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $member->id, 'course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $note = MemberNote::create([
            'user_id' => $member->id,
            'lesson_id' => $lesson->id,
            'created_by' => $admin->id,
            'type' => 'altro',
            'description' => 'Nota protetta',
        ]);

        $this->actingAs($member)->delete(route('notes.destroy', $note))->assertForbidden();
        $this->assertDatabaseHas('member_notes', ['id' => $note->id]);
    }
}
