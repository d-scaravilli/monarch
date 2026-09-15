<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\Module;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_opening_a_conversation_marks_its_messages_read_and_sender_sees_the_timestamp(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $message = Message::create(['sender_id' => $admin->id, 'subject' => 'Ciao', 'body' => 'Prova']);
        $recipient = $message->recipients()->create(['user_id' => $member->id]);

        $this->assertNull($recipient->fresh()->read_at);

        // The member opens the conversation with the admin (the sender).
        $this->actingAs($member)->get(route('messages.show', $admin))->assertOk();

        $this->assertNotNull($recipient->fresh()->read_at);

        // The admin opens the conversation with the member and sees the read receipt.
        $response = $this->actingAs($admin)->get(route('messages.show', $member));
        $response->assertOk();
        $response->assertSee('Letto il');
    }

    public function test_a_user_with_no_shared_messages_cannot_view_a_conversation(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $outsider = $this->makeMember();

        $message = Message::create(['sender_id' => $admin->id, 'subject' => 'Ciao', 'body' => 'Prova']);
        $message->recipients()->create(['user_id' => $member->id]);

        // The outsider never exchanged anything with the admin, so that
        // "conversation" doesn't exist for them.
        $this->actingAs($outsider)->get(route('messages.show', $admin))->assertForbidden();
    }

    /**
     * Reproduces the reported bug exactly: a message/message_recipients
     * row is left pointing at a user who is now soft-deleted — this is
     * deliberately done with a raw DB update (bypassing User::deleting(),
     * see the cascade-cleanup test below) to simulate data that predates
     * this fix, or any future code path that soft-deletes a user without
     * going through Eloquent. Opening Messaggi used to crash with a 500
     * (->id on a null counterpart inside conversations(), since a
     * soft-deleted user is excluded from the default belongsTo query);
     * it must now render fine, showing the real name with an "(eliminato)"
     * tag instead of erroring.
     */
    public function test_messages_page_does_not_error_when_a_conversation_partner_is_deleted(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $sent = Message::create(['sender_id' => $admin->id, 'subject' => 'Ciao', 'body' => 'Prova']);
        $sent->recipients()->create(['user_id' => $member->id]);

        DB::table('users')->where('id', $member->id)->update(['deleted_at' => now()]);
        $this->assertTrue($member->fresh()->trashed());

        $response = $this->actingAs($admin)->get(route('messages.index'));

        $response->assertOk();
        $response->assertSee($member->name);
        $response->assertSee('(eliminato)');
    }

    /**
     * Deleting a user must cascade-clean their messages — both sent
     * messages (and everyone's recipient rows for them) and the rows
     * where they themselves were a recipient — because the DB-level FK
     * cascadeOnDelete() only fires on a real SQL DELETE, never on the
     * soft-delete UPDATE this app actually performs.
     */
    public function test_deleting_a_user_removes_their_sent_messages_and_recipient_rows(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $otherMember = $this->makeMember();

        $sentByMember = Message::create(['sender_id' => $member->id, 'subject' => 'Da eliminare', 'body' => 'x']);
        $sentByMember->recipients()->create(['user_id' => $admin->id]);
        $sentByMember->recipients()->create(['user_id' => $otherMember->id]);

        $sentToMember = Message::create(['sender_id' => $admin->id, 'subject' => 'Verso il membro', 'body' => 'x']);
        $recipientRow = $sentToMember->recipients()->create(['user_id' => $member->id]);

        $member->delete();

        $this->assertDatabaseMissing('messages', ['id' => $sentByMember->id]);
        $this->assertDatabaseMissing('message_recipients', ['message_id' => $sentByMember->id]);
        $this->assertDatabaseMissing('message_recipients', ['id' => $recipientRow->id]);
        // The message an admin sent to someone else survives — only the
        // deleted user's own recipient row for it is gone.
        $this->assertDatabaseHas('messages', ['id' => $sentToMember->id]);
    }

    public function test_admin_can_delete_any_message(): void
    {
        $admin = $this->makeAdmin();
        $instructor = $this->makeInstructor();

        $message = Message::create(['sender_id' => $instructor->id, 'subject' => 'Ciao', 'body' => 'x']);
        $message->recipients()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->delete(route('messages.destroy', $message))->assertRedirect();

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_instructor_can_delete_their_own_message_but_not_someone_elses(): void
    {
        $instructorA = $this->makeInstructor();
        $instructorB = $this->makeInstructor();
        $member = $this->makeMember();

        $ownMessage = Message::create(['sender_id' => $instructorA->id, 'subject' => 'Mio', 'body' => 'x']);
        $ownMessage->recipients()->create(['user_id' => $member->id]);

        $othersMessage = Message::create(['sender_id' => $instructorB->id, 'subject' => 'Altrui', 'body' => 'x']);
        $othersMessage->recipients()->create(['user_id' => $instructorA->id]);

        $this->actingAs($instructorA)->delete(route('messages.destroy', $othersMessage))->assertForbidden();
        $this->assertDatabaseHas('messages', ['id' => $othersMessage->id]);

        $this->actingAs($instructorA)->delete(route('messages.destroy', $ownMessage))->assertRedirect();
        $this->assertDatabaseMissing('messages', ['id' => $ownMessage->id]);
    }

    public function test_thread_and_conversation_list_are_ordered_newest_first(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $older = Message::create(['sender_id' => $admin->id, 'subject' => 'Primo', 'body' => 'Primo messaggio']);
        $older->recipients()->create(['user_id' => $member->id]);
        $older->forceFill(['created_at' => now()->subDay()])->save();

        $newer = Message::create(['sender_id' => $admin->id, 'subject' => 'Secondo', 'body' => 'Secondo messaggio']);
        $newer->recipients()->create(['user_id' => $member->id]);

        $response = $this->actingAs($admin)->get(route('messages.show', $member));

        // The newer message's subject must appear before the older
        // one's in the rendered HTML (thread ordered newest-first).
        $html = $response->getContent();
        $this->assertTrue(strpos($html, 'Secondo') < strpos($html, 'Primo'));
    }
}
