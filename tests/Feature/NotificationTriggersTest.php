<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Notifications\NoteAddedNotification;
use App\Notifications\PaymentRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        Module::create([
            'slug' => 'palestra',
            'name' => 'Palestra',
            'icon' => 'fire',
            'color' => 'orange',
            'is_active' => true,
        ]);
    }

    public function test_registering_a_payment_notifies_the_enrolled_member(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');
        $enrollment = Enrollment::factory()->create(['user_id' => $member->id]);

        $this->actingAs($admin)->post(route('enrollments.payments.store', $enrollment), [
            'amount' => 50,
            'method' => 'contanti',
            'date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($member, PaymentRegisteredNotification::class);
    }

    public function test_adding_a_note_notifies_the_member_it_is_about(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');
        $course = Course::factory()->create();
        Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $member->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin)->post(route('courses.lessons.notes.store', [$course, $lesson]), [
            'user_id' => $member->id,
            'type' => 'progresso',
            'description' => 'Ottimo lavoro oggi.',
        ])->assertRedirect();

        Notification::assertSentTo($member, NoteAddedNotification::class);
    }
}
