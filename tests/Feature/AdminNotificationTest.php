<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        Module::create([
            'slug' => 'amministrazione',
            'name' => 'Amministrazione',
            'icon' => 'shield-check',
            'color' => 'blue',
            'is_active' => true,
        ]);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_sees_every_notification_in_the_system(): void
    {
        $admin = $this->makeAdmin();
        $member = User::factory()->create(['name' => 'Iscritto Notificato']);

        $member->notify(new PaymentRegisteredNotification(
            Payment::factory()->create([
                'enrollment_id' => Enrollment::factory()->create(['user_id' => $member->id])->id,
                'amount' => 50,
            ])
        ));

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('Iscritto Notificato');
        $response->assertSee('Pagamento registrato');
    }

    public function test_admin_can_delete_a_single_notification(): void
    {
        $admin = $this->makeAdmin();
        $member = User::factory()->create();

        $member->notify(new PaymentRegisteredNotification(
            Payment::factory()->create([
                'enrollment_id' => Enrollment::factory()->create(['user_id' => $member->id])->id,
            ])
        ));

        $notification = DatabaseNotification::first();
        $this->assertNotNull($notification);

        $this->actingAs($admin)->delete(route('admin.notifications.destroy', $notification))->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_non_admin_cannot_view_the_notifications_page(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('admin.notifications.index'))->assertForbidden();
    }
}
