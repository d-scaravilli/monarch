<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\Module;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MessageTest extends TestCase
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

    /**
     * The "module:palestra" middleware requires either the admin role
     * (bypasses everything) or an explicit module_user grant — separate
     * from being a course member/instructor — so every non-admin actor
     * in these tests needs it to even reach the route.
     */
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

    public function test_admin_can_message_a_single_member(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $response = $this->actingAs($admin)->post(route('messages.store'), [
            'recipient_mode' => 'single',
            'recipient_id' => $member->id,
            'subject' => 'Ciao',
            'body' => 'Messaggio di prova',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', ['sender_id' => $admin->id, 'subject' => 'Ciao']);
        $this->assertDatabaseHas('message_recipients', ['user_id' => $member->id]);
        Notification::assertSentTo($member, NewMessageNotification::class);
    }

    public function test_admin_can_message_all_enrollees_of_a_course_at_once(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::factory()->create();
        $enrolled = collect([$this->makeMember(), $this->makeMember(), $this->makeMember()]);
        foreach ($enrolled as $u) {
            Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $u->id]);
        }
        $notEnrolled = $this->makeMember();

        $this->actingAs($admin)->post(route('messages.store'), [
            'recipient_mode' => 'course',
            'course_id' => $course->id,
            'subject' => 'A tutto il corso',
            'body' => 'Ciao a tutti',
        ])->assertRedirect();

        $message = Message::first();
        $this->assertSame(3, $message->recipients()->count());
        foreach ($enrolled as $u) {
            $this->assertDatabaseHas('message_recipients', ['message_id' => $message->id, 'user_id' => $u->id]);
        }
        $this->assertDatabaseMissing('message_recipients', ['message_id' => $message->id, 'user_id' => $notEnrolled->id]);
    }

    public function test_instructor_can_only_message_students_of_their_own_course(): void
    {
        $instructor = $this->makeInstructor();

        $ownCourse = Course::factory()->create();
        $ownCourse->instructors()->attach($instructor);
        $ownStudent = $this->makeMember();
        Enrollment::factory()->create(['course_id' => $ownCourse->id, 'user_id' => $ownStudent->id]);

        $otherCourse = Course::factory()->create();
        $strangerStudent = $this->makeMember();
        Enrollment::factory()->create(['course_id' => $otherCourse->id, 'user_id' => $strangerStudent->id]);

        // Can message their own student.
        $this->actingAs($instructor)->post(route('messages.store'), [
            'recipient_mode' => 'single',
            'recipient_id' => $ownStudent->id,
            'subject' => 'Ciao',
            'body' => 'Prova',
        ])->assertRedirect();
        $this->assertDatabaseHas('message_recipients', ['user_id' => $ownStudent->id]);

        // Cannot message a student of a course they don't teach — the
        // recipient pool excludes them entirely, so nothing valid is left.
        $this->actingAs($instructor)->post(route('messages.store'), [
            'recipient_mode' => 'single',
            'recipient_id' => $strangerStudent->id,
            'subject' => 'Ciao',
            'body' => 'Prova',
        ])->assertStatus(422);
        $this->assertDatabaseMissing('message_recipients', ['user_id' => $strangerStudent->id]);
    }

    public function test_instructor_cannot_send_to_a_course_they_do_not_teach(): void
    {
        $instructor = $this->makeInstructor();

        $otherCourse = Course::factory()->create();
        $enrolled = $this->makeMember();
        Enrollment::factory()->create(['course_id' => $otherCourse->id, 'user_id' => $enrolled->id]);

        $this->actingAs($instructor)->post(route('messages.store'), [
            'recipient_mode' => 'course',
            'course_id' => $otherCourse->id,
            'subject' => 'Ciao',
            'body' => 'Prova',
        ])->assertForbidden();
    }

    public function test_member_cannot_compose_messages(): void
    {
        $member = $this->makeMember();
        $other = $this->makeMember();

        $this->actingAs($member)->post(route('messages.store'), [
            'recipient_mode' => 'single',
            'recipient_id' => $other->id,
            'subject' => 'Ciao',
            'body' => 'Prova',
        ])->assertForbidden();
    }

    public function test_opening_a_message_marks_it_read_and_sender_sees_the_timestamp(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $message = Message::create(['sender_id' => $admin->id, 'subject' => 'Ciao', 'body' => 'Prova']);
        $recipient = $message->recipients()->create(['user_id' => $member->id]);

        $this->assertNull($recipient->fresh()->read_at);

        $this->actingAs($member)->get(route('messages.show', $message))->assertOk();

        $this->assertNotNull($recipient->fresh()->read_at);

        // The sender's view shows the read receipt.
        $response = $this->actingAs($admin)->get(route('messages.show', $message));
        $response->assertOk();
        $response->assertSee($member->name);
    }

    public function test_a_user_who_is_not_sender_or_recipient_cannot_view_the_message(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $outsider = $this->makeMember();

        $message = Message::create(['sender_id' => $admin->id, 'subject' => 'Ciao', 'body' => 'Prova']);
        $message->recipients()->create(['user_id' => $member->id]);

        $this->actingAs($outsider)->get(route('messages.show', $message))->assertForbidden();
    }
}
