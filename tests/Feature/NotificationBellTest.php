<?php

namespace Tests\Feature;

use App\Models\MemberNote;
use App\Models\User;
use App\Notifications\NoteAddedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'member']);
    }

    public function test_notification_is_stored_in_database_for_the_bell(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $note = MemberNote::create([
            'user_id' => $member->id,
            'created_by' => $member->id,
            'type' => 'altro',
            'description' => 'Prova',
        ]);

        $member->notify(new NoteAddedNotification($note));

        $this->assertSame(1, $member->notifications()->count());
        $this->assertSame(1, $member->unreadNotifications()->count());
    }

    public function test_opening_the_bell_marks_all_notifications_read(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $note = MemberNote::create([
            'user_id' => $member->id,
            'created_by' => $member->id,
            'type' => 'altro',
            'description' => 'Prova',
        ]);
        $member->notify(new NoteAddedNotification($note));
        $member->notify(new NoteAddedNotification($note));

        $this->assertSame(2, $member->unreadNotifications()->count());

        $this->actingAs($member)->post(route('notifications.read'))->assertOk();

        $this->assertSame(0, $member->unreadNotifications()->count());
    }

    public function test_bell_is_hidden_for_admin_but_shown_for_member(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');

        $adminResponse = $this->actingAs($admin)->get(route('settings.edit'));
        $adminResponse->assertDontSee(route('notifications.read'), false);

        $memberResponse = $this->actingAs($member)->get(route('settings.edit'));
        $memberResponse->assertSee(route('notifications.read'), false);
    }
}
